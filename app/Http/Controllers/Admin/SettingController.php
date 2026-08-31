<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    private static array $types = [
        'fees' => [
            'field' => null,
            'label' => 'Phí dịch vụ và lịch thu tiền',
            'unit' => '',
            'description' => 'Quản lý tập trung đơn giá điện, nước, Internet, dịch vụ và lịch phát hành hóa đơn.',
        ],
        'property-payment' => [
            'field' => null,
            'label' => 'Thông tin nhà trọ và thanh toán',
            'unit' => '',
            'description' => 'Thông tin tài sản, chủ nhà và tài khoản nhận tiền dùng trên hợp đồng, hóa đơn và VietQR.',
        ],
        'electricity' => [
            'field' => 'electric_price',
            'label' => 'Đơn giá điện',
            'unit' => 'VNĐ/kWh',
            'description' => 'Giá điện dùng để tính tiền điện theo kWh.',
        ],
        'water' => [
            'field' => 'water_price',
            'label' => 'Đơn giá nước',
            'unit' => 'VNĐ/m³',
            'description' => 'Giá nước dùng để tính theo số m³ tiêu thụ thực tế trên đồng hồ của phòng.',
        ],
        'internet' => [
            'field' => 'internet_fee',
            'label' => 'Phí internet',
            'unit' => 'VNĐ/tháng',
            'description' => 'Phí cố định internet mỗi tháng.',
        ],
        'service' => [
            'field' => 'service_fee',
            'label' => 'Phí dịch vụ',
            'unit' => 'VNĐ/tháng',
            'description' => 'Phí dịch vụ chung tính vào hóa đơn.',
        ],
        'bank' => [
            'field' => null,
            'label' => 'Tài khoản nhận thanh toán',
            'unit' => '',
            'description' => 'Thông tin dùng để tạo mã VietQR có sẵn số tiền và nội dung hóa đơn.',
        ],
        'property' => [
            'field' => null,
            'label' => 'Thông tin tài sản và chủ nhà',
            'unit' => '',
            'description' => 'Dùng để chụp snapshot khi tạo hợp đồng và in đúng dữ liệu lịch sử.',
        ],
    ];

    public function edit(string $type)
    {
        if (! array_key_exists($type, self::$types)) {
            abort(404);
        }

        $setting = $this->setting();
        $typeData = self::$types[$type];
        $currentValue = $typeData['field'] ? $setting->{$typeData['field']} : null;
        $feeSchedules = $type === 'fees'
            ? FeeSchedule::query()->latest('effective_from')->get()
            : collect();

        return view('admin.settings.edit', compact('setting', 'type', 'typeData', 'currentValue', 'feeSchedules'));
    }

    public function update(Request $request, string $type)
    {
        if (! array_key_exists($type, self::$types)) {
            abort(404);
        }

        $typeData = self::$types[$type];
        $setting = $this->setting();

        if ($type === 'fees') {
            $data = $request->validate($this->feeRules());
            $this->storeFeeSchedule($setting, $data);
        } elseif ($type === 'property-payment') {
            $data = $request->validate(array_merge($this->propertyRules(), $this->bankRules()));
            $setting->update($data);
        } elseif ($type === 'property') {
            $data = $request->validate($this->propertyRules());
            $setting->update($data);
        } elseif ($type === 'bank') {
            $data = $request->validate($this->bankRules());
            $setting->update($data);
        } else {
            $data = $request->validate([
                $typeData['field'] => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            ]);
            $this->storeLegacyFeeChange($setting, $typeData['field'], $data[$typeData['field']]);
        }

        return redirect()
            ->route('admin.settings.edit', ['type' => $type])
            ->with('success', 'Đã cập nhật '.mb_strtolower($typeData['label']).' thành công.');
    }

    private function setting(): Setting
    {
        return Setting::currentOrCreate([
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);
    }

    private function feeRules(): array
    {
        $priceRule = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'];

        return [
            'electric_price' => $priceRule,
            'water_price' => $priceRule,
            'internet_fee' => $priceRule,
            'service_fee' => $priceRule,
            'invoice_day' => ['required', 'integer', 'between:1,31'],
            'payment_due_days' => ['required', 'integer', 'between:1,90'],
            'fee_effective_from' => [
                'bail',
                'required',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $minimumMonth = now()->addMonthNoOverflow()->startOfMonth();
                    $selectedMonth = Carbon::createFromFormat('!Y-m', $value)->startOfMonth();

                    if ($selectedMonth->lt($minimumMonth)) {
                        $fail('Chỉ được chọn tháng trong tương lai, từ tháng '.$minimumMonth->format('m/Y').' trở đi.');
                    }
                },
            ],
        ];
    }

    private function storeFeeSchedule(Setting $setting, array $data): void
    {
        $effectiveFrom = Carbon::createFromFormat('!Y-m', $data['fee_effective_from'])->startOfMonth();
        $priceFields = ['electric_price', 'water_price', 'internet_fee', 'service_fee'];

        DB::transaction(function () use ($setting, $data, $effectiveFrom, $priceFields): void {
            $effectiveDate = $effectiveFrom->toDateString();
            $schedule = FeeSchedule::query()
                ->where('effective_from', $effectiveDate)
                ->lockForUpdate()
                ->first();

            if ($schedule?->invoices()->exists()) {
                throw ValidationException::withMessages([
                    'fee_effective_from' => 'Bảng giá của tháng này đã được dùng để phát hành hóa đơn và không thể sửa.',
                ]);
            }

            FeeSchedule::query()->updateOrCreate(
                ['effective_from' => $effectiveDate],
                collect($data)->only($priceFields)->all()
            );

            $settingPayload = collect($data)->only(['invoice_day', 'payment_due_days'])->all();
            $currentSchedule = FeeSchedule::forPeriod(now(), true);
            if ($currentSchedule) {
                $settingPayload = array_merge($settingPayload, $currentSchedule->only($priceFields));
            }

            $setting->update($settingPayload);
        });
    }

    private function storeLegacyFeeChange(Setting $setting, string $field, mixed $value): void
    {
        $priceFields = ['electric_price', 'water_price', 'internet_fee', 'service_fee'];
        $effectiveFrom = now()->startOfMonth();

        DB::transaction(function () use ($setting, $field, $value, $priceFields, $effectiveFrom): void {
            $effectiveDate = $effectiveFrom->toDateString();
            $schedule = FeeSchedule::query()
                ->where('effective_from', $effectiveDate)
                ->lockForUpdate()
                ->first();

            if ($schedule?->invoices()->exists()) {
                throw ValidationException::withMessages([
                    $field => 'Bảng giá tháng hiện tại đã được dùng để phát hành hóa đơn và không thể sửa.',
                ]);
            }

            $prices = $setting->only($priceFields);
            $prices[$field] = $value;
            FeeSchedule::query()->updateOrCreate(
                ['effective_from' => $effectiveDate],
                $prices
            );
            $setting->update([$field => $value]);
        });
    }

    private function propertyRules(): array
    {
        return [
            'property_name' => ['required', 'string', 'max:255'],
            'property_address' => ['required', 'string', 'max:1000'],
            'landlord_name' => ['required', 'string', 'max:255'],
            'landlord_date_of_birth' => ['nullable', 'date', 'before:today'],
            'landlord_identity_number' => ['nullable', 'string', 'max:30'],
            'landlord_identity_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'landlord_identity_issued_by' => ['nullable', 'string', 'max:255'],
            'landlord_phone' => ['required', 'string', 'max:30'],
            'landlord_address' => ['required', 'string', 'max:1000'],
        ];
    }

    private function bankRules(): array
    {
        return [
            'bank_id' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'bank_account_no' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'bank_account_name' => ['required', 'string', 'max:100'],
        ];
    }
}
