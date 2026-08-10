<?php

use App\Models\Contract;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\UtilityReading;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:audit-transaction-codes', function () {
    $duplicates = DB::table('payments')
        ->select('transaction_code', DB::raw('COUNT(*) as occurrences'))
        ->whereNotNull('transaction_code')
        ->groupBy('transaction_code')
        ->havingRaw('COUNT(*) > 1')
        ->orderBy('transaction_code')
        ->get();

    if ($duplicates->isEmpty()) {
        $this->info('Không phát hiện mã giao dịch trùng. Có thể chạy migration an toàn.');

        return 0;
    }

    $this->error('Phát hiện mã giao dịch trùng. Không tự động thay đổi dữ liệu tài chính.');
    $this->table(
        ['Mã giao dịch', 'Số bản ghi', 'Payment IDs'],
        $duplicates->map(function ($duplicate) {
            $ids = DB::table('payments')
                ->where('transaction_code', $duplicate->transaction_code)
                ->orderBy('id')
                ->pluck('id')
                ->implode(', ');

            return [$duplicate->transaction_code, $duplicate->occurrences, $ids];
        })->all()
    );
    $this->warn('Hãy đối soát các payment ID trên và sửa dữ liệu có xác nhận trước khi chạy migration.');

    return 1;
})->purpose('Kiểm tra mã giao dịch thanh toán trùng mà không thay đổi dữ liệu');

Artisan::command('support:audit-attachments', function () {
    $missing = SupportRequest::query()
        ->whereNotNull('attachment')
        ->orderBy('id')
        ->get(['id', 'attachment'])
        ->reject(fn ($supportRequest) => $supportRequest->attachmentExists());

    if ($missing->isEmpty()) {
        $this->info('Không phát hiện tệp đính kèm hỗ trợ bị thiếu.');

        return 0;
    }

    $this->error('Phát hiện bản ghi hỗ trợ có tệp đính kèm bị thiếu. Dữ liệu đường dẫn được giữ nguyên để đối soát.');
    $this->table(
        ['Support Request ID', 'Đường dẫn được lưu'],
        $missing->map(fn ($supportRequest) => [$supportRequest->id, $supportRequest->attachment])->all()
    );

    return 1;
})->purpose('Kiểm tra tệp đính kèm hỗ trợ bị thiếu mà không thay đổi dữ liệu');

Artisan::command('settings:audit-active', function () {
    $active = Setting::query()
        ->where('is_active', true)
        ->orderBy('id')
        ->get(['id', 'electric_price', 'water_price', 'internet_fee', 'service_fee', 'updated_at']);

    if ($active->count() <= 1) {
        $this->info('Cấu hình đơn giá active hợp lệ: '.$active->count().' bản ghi.');

        return 0;
    }

    $this->error('Phát hiện nhiều cấu hình đơn giá active. Không tự động thay đổi dữ liệu tài chính.');
    $this->table(
        ['ID', 'Điện', 'Nước', 'Internet', 'Dịch vụ', 'Cập nhật lúc'],
        $active->map(fn ($setting) => [
            $setting->id,
            $setting->electric_price,
            $setting->water_price,
            $setting->internet_fee,
            $setting->service_fee,
            $setting->updated_at,
        ])->all()
    );

    return 1;
})->purpose('Kiểm tra có nhiều cấu hình đơn giá active mà không thay đổi dữ liệu');

Artisan::command('portal:audit-private-files', function () {
    $missing = collect();

    Contract::query()->whereNotNull('contract_file')->get(['id', 'contract_file'])
        ->reject(fn ($contract) => $contract->contractFileExists())
        ->each(fn ($contract) => $missing->push(['Hợp đồng', $contract->id, $contract->contract_file]));

    UtilityReading::query()
        ->where(fn ($query) => $query->whereNotNull('electricity_image')->orWhereNotNull('water_image'))
        ->get(['id', 'electricity_image', 'water_image'])
        ->each(function ($reading) use ($missing) {
            foreach (['electricity', 'water'] as $type) {
                $path = $reading->{$type.'_image'};
                if ($path && ! $reading->meterImageExists($type)) {
                    $missing->push([$type === 'electricity' ? 'Đồng hồ điện' : 'Đồng hồ nước', $reading->id, $path]);
                }
            }
        });

    if ($missing->isEmpty()) {
        $this->info('Không phát hiện tài liệu riêng tư của cổng khách thuê bị thiếu.');

        return 0;
    }

    $this->error('Phát hiện tài liệu riêng tư của cổng khách thuê bị thiếu. Đường dẫn DB được giữ nguyên để đối soát.');
    $this->table(['Loại', 'Bản ghi ID', 'Đường dẫn được lưu'], $missing->all());

    return 1;
})->purpose('Kiểm tra file hợp đồng và ảnh đồng hồ bị thiếu mà không thay đổi dữ liệu');
