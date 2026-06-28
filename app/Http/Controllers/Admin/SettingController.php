<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    private static array $types = [
        'electricity' => [
            'field' => 'electric_price',
            'label' => 'Đơn giá điện (VNĐ/kWh)',
            'description' => 'Giá điện dùng để tính tiền điện theo kWh.',
        ],
        'water' => [
            'field' => 'water_price',
            'label' => 'Đơn giá nước (VNĐ/m3)',
            'description' => 'Giá nước dùng để tính tiền nước theo m3.',
        ],
        'internet' => [
            'field' => 'internet_fee',
            'label' => 'Phí internet (VNĐ)',
            'description' => 'Phí cố định internet mỗi tháng.',
        ],
        'service' => [
            'field' => 'service_fee',
            'label' => 'Phí dịch vụ (VNĐ)',
            'description' => 'Phí dịch vụ chung tính vào hóa đơn.',
        ],
    ];

    public function edit(string $type)
    {
        if (!array_key_exists($type, self::$types)) {
            abort(404);
        }

        $setting = Setting::firstOrCreate([], [
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);

        $typeData = self::$types[$type];
        $currentValue = $setting->{$typeData['field']};

        return view('admin.settings.edit', compact('setting', 'type', 'typeData', 'currentValue'));
    }

    public function update(Request $request, string $type)
    {
        if (!array_key_exists($type, self::$types)) {
            abort(404);
        }

        $typeData = self::$types[$type];

        $setting = Setting::firstOrCreate([], [
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);

        $data = $request->validate([
            $typeData['field'] => 'required|numeric|min:0',
        ]);

        $setting->update([$typeData['field'] => $data[$typeData['field']]]);

        return redirect()->route('admin.settings.edit', ['type' => $type])
            ->with('success', 'Đã cập nhật ' . $typeData['label'] . ' thành công!');
    }
}
