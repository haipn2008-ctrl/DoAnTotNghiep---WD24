<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractTemplate;
use App\Models\Setting;
use App\Services\ClientNotificationService;
use App\Services\ContractAppendixService;
use App\Services\ContractRateResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContractAppendixController extends Controller
{
    public function __construct(
        private readonly ContractAppendixService $appendices,
        private readonly ContractRateResolver $pricing,
    ) {}

    public function create(Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless(
            $this->appendices->canCreateDraft($contract),
            409,
            'Hợp đồng không ở trạng thái có thể lập phụ lục hoặc đang có phụ lục chưa xử lý xong.'
        );

        $clauseOptions = $this->titleOptions();
        $currentRates = $this->pricing->forPeriod($contract, today());
        $contentDefaults = $this->contentDefaults($contract, $currentRates);

        return view('admin.contracts.appendices.form', compact('contract', 'clauseOptions', 'currentRates', 'contentDefaults'));
    }

    public function store(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $appendix = $this->appendices->createDraft($contract, $this->validated($request), $request->user());

        return redirect()->route('admin.contract-appendices.show', $appendix)
            ->with('success', 'Đã tạo bản nháp phụ lục. Hãy kiểm tra trước khi gửi khách.');
    }

    public function show(ContractAppendix $appendix)
    {
        Gate::authorize('manageLifecycle', $appendix->contract);
        $appendix->load(['contract.room', 'contract.tenant', 'creator', 'sender', 'responder', 'parent']);

        return view('admin.contracts.appendices.show', compact('appendix'));
    }

    public function edit(ContractAppendix $appendix)
    {
        Gate::authorize('manageLifecycle', $appendix->contract);
        abort_unless($appendix->status === ContractAppendix::STATUS_DRAFT, 409, 'Chỉ phụ lục nháp mới có thể sửa.');
        $contract = $appendix->contract;
        $clauseOptions = $this->titleOptions();
        $currentRates = $this->pricing->forPeriod($contract, $appendix->effective_from);
        $contentDefaults = $this->contentDefaults($contract, $currentRates);

        return view('admin.contracts.appendices.form', compact('contract', 'appendix', 'clauseOptions', 'currentRates', 'contentDefaults'));
    }

    public function update(Request $request, ContractAppendix $appendix)
    {
        Gate::authorize('manageLifecycle', $appendix->contract);
        $appendix = $this->appendices->updateDraft($appendix, $this->validated($request));

        return redirect()->route('admin.contract-appendices.show', $appendix)
            ->with('success', 'Đã cập nhật bản nháp phụ lục.');
    }

    public function send(Request $request, ContractAppendix $appendix)
    {
        Gate::authorize('manageLifecycle', $appendix->contract);
        $appendix = $this->appendices->send($appendix, $request->user());
        app(ClientNotificationService::class)->appendix(
            $appendix,
            'Có phụ lục hợp đồng cần xác nhận',
            'Ban quản lý đã gửi phụ lục '.$appendix->code.'. Vui lòng kiểm tra nội dung và chấp nhận hoặc từ chối.'
        );

        return back()->with('success', 'Đã gửi phụ lục cho khách thuê đại diện xác nhận.');
    }

    public function revise(Request $request, ContractAppendix $appendix)
    {
        Gate::authorize('manageLifecycle', $appendix->contract);
        $revised = $this->appendices->revise($appendix, $request->user());

        return redirect()->route('admin.contract-appendices.edit', $revised)
            ->with('success', 'Đã tạo bản sửa đổi từ phản hồi của khách.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', Rule::in(array_values($this->titleOptions()))],
            'legal_basis' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string', 'min:20', 'max:30000'],
            'effective_from' => ['required', 'date'],
            'price_adjustments' => ['nullable', 'array'],
            'price_adjustments.electric_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'price_adjustments.water_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'price_adjustments.internet_fee' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'price_adjustments.service_fee' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $fields = ContractAppendix::priceFieldsForTitle((string) $request->input('title'));
            if ($fields === []) {
                return;
            }

            $submitted = collect($request->input('price_adjustments', []))
                ->only($fields)
                ->filter(fn ($value) => $value !== null && $value !== '');
            if ($submitted->isEmpty()) {
                $validator->errors()->add('price_adjustments', 'Vui lòng nhập ít nhất một đơn giá mới cho phụ lục.');
            }
            if (count($fields) === 1 && ! $submitted->has($fields[0])) {
                $validator->errors()->add("price_adjustments.{$fields[0]}", 'Vui lòng nhập đơn giá mới.');
            }
        });

        $data = $validator->validate();
        $fields = ContractAppendix::priceFieldsForTitle($data['title']);
        $data['price_adjustments'] = $fields === []
            ? []
            : collect($data['price_adjustments'] ?? [])->only($fields)->all();

        return $data;
    }

    private function titleOptions(): array
    {
        $priceTitles = array_keys(ContractAppendix::PRICE_TITLE_FIELDS);

        return array_merge(ContractTemplate::CLAUSE_LABELS, array_combine($priceTitles, $priceTitles));
    }

    private function contentDefaults(Contract $contract, object $rates): array
    {
        $template = $contract->template ?: ContractTemplate::activeOrCreate();
        $invoiceDay = str_pad((string) (Setting::currentOrCreate()->invoice_day ?: 5), 2, '0', STR_PAD_LEFT);
        $defaults = [];

        foreach (ContractTemplate::CLAUSE_LABELS as $key => $title) {
            $defaults[$title] = str_replace(':invoice_day', $invoiceDay, $template->clause($key));
        }

        foreach (ContractAppendix::PRICE_TITLE_FIELDS as $title => $fields) {
            $lines = collect($fields)->map(function (string $field) use ($rates): string {
                $label = ContractAppendix::PRICE_FIELD_LABELS[$field];
                $unit = ContractAppendix::PRICE_FIELD_UNITS[$field];

                return '- '.$label.': '.number_format((float) ($rates->{$field} ?? 0), 0, ',', '.').' '.$unit;
            })->implode("\n");

            $defaults[$title] = "Các đơn giá hiện đang áp dụng cho hợp đồng:\n{$lines}\n\nHai bên thống nhất điều chỉnh đơn giá nêu trên kể từ ngày phụ lục có hiệu lực. Đơn giá mới được xác định tại bảng điều chỉnh kèm theo phụ lục này.";
        }

        return $defaults;
    }
}
