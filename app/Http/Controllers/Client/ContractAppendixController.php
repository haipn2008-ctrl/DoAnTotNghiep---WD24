<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ContractAppendix;
use App\Services\AdminNotificationService;
use App\Services\ContractAppendixService;
use Illuminate\Http\Request;

class ContractAppendixController extends Controller
{
    public function __construct(private readonly ContractAppendixService $appendices) {}

    public function show(Request $request, ContractAppendix $appendix)
    {
        $appendix = $this->owned($request, $appendix)->load(['contract.room', 'contract.tenant', 'sender']);

        return view('client.contracts.appendices.show', compact('appendix'));
    }

    public function accept(Request $request, ContractAppendix $appendix)
    {
        $request->validate(['confirmation' => ['accepted']]);
        $appendix = $this->appendices->accept($this->owned($request, $appendix), $request->user());
        app(AdminNotificationService::class)->appendixResponded($appendix, true);

        return redirect()->route('client.contract-appendices.show', $appendix)
            ->with('success', 'Bạn đã chấp nhận phụ lục. Nội dung phụ lục đã được ghi nhận.');
    }

    public function reject(Request $request, ContractAppendix $appendix)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $appendix = $this->appendices->reject(
            $this->owned($request, $appendix),
            $request->user(),
            $data['rejection_reason']
        );
        app(AdminNotificationService::class)->appendixResponded($appendix, false);

        return redirect()->route('client.contract-appendices.show', $appendix)
            ->with('success', 'Đã gửi lý do từ chối cho ban quản lý xem xét.');
    }

    private function owned(Request $request, ContractAppendix $appendix): ContractAppendix
    {
        return ContractAppendix::query()
            ->whereKey($appendix->id)
            ->where('status', '!=', ContractAppendix::STATUS_DRAFT)
            ->whereHas('contract', fn ($query) => $query->managedBy($request->user()))
            ->firstOrFail();
    }
}
