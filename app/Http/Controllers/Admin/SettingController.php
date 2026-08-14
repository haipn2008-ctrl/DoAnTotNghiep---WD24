<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private static array $types = [
        'fees' => [
            'field' => null,
            'label' => 'Phí dịch vụ',
            'unit' => '',
            'description' => 'Quản lý tập trung đơn giá điện, nước, Internet, dịch vụ chung và phí gửi xe.',
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
        'parking' => [
            'field' => null,
            'label' => 'Phí gửi xe',
            'unit' => 'VNĐ/xe/tháng',
            'description' => 'Cấu hình đơn giá trông xe máy và ô tô theo từng xe mỗi tháng.',
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

        return view('admin.settings.edit', compact('setting', 'type', 'typeData', 'currentValue'));
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
            $setting->update($data);
        } elseif ($type === 'parking') {
            $data = $request->validate($this->parkingRules());
            $setting->update($data);
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
            $setting->update([$typeData['field'] => $data[$typeData['field']]]);
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
            'motorcycle_parking_fee' => $priceRule,
            'car_parking_fee' => $priceRule,
        ];
    }

    private function parkingRules(): array
    {
        $priceRule = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'];

        return [
            'motorcycle_parking_fee' => $priceRule,
            'car_parking_fee' => $priceRule,
        ];
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
