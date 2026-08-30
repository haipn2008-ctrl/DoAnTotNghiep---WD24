<?php

use App\Models\Contract;
use App\Models\Setting;
use App\Models\SupportRequest;
use App\Models\TemporaryResidence;
use App\Models\UtilityReading;
use App\Services\ClientNotificationService;
use App\Services\ContractLifecycleService;
use App\Services\DepositRefundReceiptService;
use App\Services\OverdueInvoiceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

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
        ['Mã giao dịch', 'Số bản ghi', 'ID thanh toán'],
        $duplicates->map(function ($duplicate) {
            $ids = DB::table('payments')
                ->where('transaction_code', $duplicate->transaction_code)
                ->orderBy('id')
                ->pluck('id')
                ->implode(', ');

            return [$duplicate->transaction_code, $duplicate->occurrences, $ids];
        })->all()
    );
    $this->warn('Hãy đối soát các ID thanh toán trên và sửa dữ liệu có xác nhận trước khi chạy migration.');

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
        ['ID yêu cầu hỗ trợ', 'Đường dẫn được lưu'],
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
        $this->info('Cấu hình đơn giá đang hoạt động hợp lệ: '.$active->count().' bản ghi.');

        return 0;
    }

    $this->error('Phát hiện nhiều cấu hình đơn giá đang hoạt động. Không tự động thay đổi dữ liệu tài chính.');
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
})->purpose('Kiểm tra có nhiều cấu hình đơn giá đang hoạt động mà không thay đổi dữ liệu');

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

Artisan::command('contracts:process-lifecycle', function () {
    $result = app(ContractLifecycleService::class)->processDailyAlerts();
    $this->info("Đã đánh dấu {$result['expired']} hợp đồng expired; tạo {$result['alerts_created']} cảnh báo mới.");

    return 0;
})->purpose('Xử lý hết hạn hợp đồng và cảnh báo vòng đời theo cách idempotent');

Artisan::command('temporary-residences:expire', function () {
    $count = TemporaryResidence::query()
        ->whereIn('status', ['pending', 'active'])
        ->whereNotNull('end_date')
        ->whereDate('end_date', '<', today())
        ->update(['status' => 'expired']);

    $this->info("Đã chuyển {$count} giấy tạm trú quá hạn sang trạng thái hết hiệu lực.");

    return 0;
})->purpose('Đánh dấu giấy tạm trú hết hiệu lực theo ngày kết thúc');

Artisan::command('invoices:notify-overdue', function () {
    $count = app(OverdueInvoiceService::class)->notifyNewlyOverdue();
    $this->info("Đã gửi {$count} thông báo hóa đơn quá hạn.");

    return 0;
})->purpose('Gửi một lần thông báo quá hạn và yêu cầu khách giải trình chậm thanh toán');

Artisan::command('invoices:notify-due-today', function () {
    $count = app(OverdueInvoiceService::class)->notifyDueToday();
    $this->info("Đã gửi {$count} thông báo hạn cuối thanh toán hôm nay.");

    return 0;
})->purpose('Gửi một lần thông báo cho khách vào chính ngày hết hạn hóa đơn');

Artisan::command('contracts:audit-lifecycle', function () {
    $issues = collect();
    Contract::query()->with(['room', 'utilityReadings'])->orderBy('id')->each(function (Contract $contract) use ($issues): void {
        $add = function (string $problem) use ($issues, $contract): void {
            $issues->push([$contract->id, $contract->contract_code, $contract->status, $problem]);
        };
        if (in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_SIGNATURE], true) && $contract->signed_at) {
            $add('Trạng thái chưa ký nhưng signed_at có dữ liệu.');
        }
        if (in_array($contract->status, [Contract::STATUS_PENDING_DEPOSIT, Contract::STATUS_AWAITING_MOVE_IN, Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED], true) && ! $contract->signed_at) {
            $add('Trạng thái sau ký nhưng thiếu signed_at.');
        }
        if ($contract->status === Contract::STATUS_AWAITING_MOVE_IN && (! $contract->scheduled_move_in_date || ! $contract->reservation_expires_at)) {
            $add('Chờ nhận phòng nhưng thiếu lịch/hạn giữ phòng.');
        }
        if (in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
            if (! $contract->actual_move_in_at || ! $contract->utilityReadings->contains('reading_type', 'handover')) {
                $add('Đang ở/quá hạn nhưng thiếu thời điểm hoặc chỉ số bàn giao.');
            }
            if (! $contract->room || $contract->room->status !== 'occupied' || (int) $contract->room->current_people <= 0) {
                $add('Hợp đồng đang có người thuê nhưng trạng thái/số người của phòng mâu thuẫn.');
            }
        }
        if ($contract->status === Contract::STATUS_CANCELLED && blank($contract->cancel_reason)) {
            $add('Hợp đồng hủy thiếu lý do.');
        }
        if ($contract->status === Contract::STATUS_COMPLETED && (! $contract->actual_move_out_at || ! $contract->deposit_resolution)) {
            $add('Hợp đồng đã hoàn tất nhưng thiếu dữ liệu trả phòng hoặc trạng thái tài chính cuối cùng.');
        }
    });

    if ($issues->isEmpty()) {
        $this->info('Không phát hiện dữ liệu vòng đời hợp đồng mâu thuẫn.');

        return 0;
    }
    $this->error('Phát hiện dữ liệu cần admin đối soát. Lệnh chỉ đọc và không sửa dữ liệu.');
    $this->table(['ID', 'Mã hợp đồng', 'Trạng thái', 'Vấn đề'], $issues->all());

    return 1;
})->purpose('Audit read-only dữ liệu vòng đời hợp đồng và dữ liệu cũ mâu thuẫn');

Artisan::command('deposit-refunds:auto-confirm-receipts', function () {
    $confirmed = 0;
    $completed = 0;

    Contract::query()
        ->where('deposit_status', Contract::DEPOSIT_REFUND_PROCESSING)
        ->whereNotNull('deposit_transferred_at')
        ->whereNotNull('deposit_transfer_proof')
        ->whereNull('deposit_receipt_confirmed_at')
        ->where('deposit_receipt_confirmation_due_at', '<=', now())
        ->eachById(function (Contract $contract) use (&$confirmed, &$completed): void {
            $contract = app(DepositRefundReceiptService::class)->confirm(
                $contract,
                DepositRefundReceiptService::SOURCE_AUTOMATIC,
            );

            if ($contract->deposit_receipt_confirmation_source !== DepositRefundReceiptService::SOURCE_AUTOMATIC) {
                return;
            }

            if ($contract->status === Contract::STATUS_SETTLING) {
                $contract = app(ContractLifecycleService::class)
                    ->completeSettlementAfterAutomaticRefundConfirmation($contract);
                $completed++;
            }

            app(ClientNotificationService::class)->contract(
                $contract,
                'deposit_refund_receipt_auto_confirmed',
                'Khoản hoàn cọc và hợp đồng đã được tự động hoàn tất',
                'Đã quá 24 giờ kể từ khi Ban quản lý chuyển tiền và không có phản hồi. Hệ thống đã ghi nhận bạn nhận đủ '.number_format((float) $contract->deposit_transfer_amount, 0, ',', '.').' VNĐ và hoàn tất hợp đồng.'
            );
            $confirmed++;
        });

    $this->info("Đã tự động xác nhận {$confirmed} khoản hoàn cọc và hoàn tất {$completed} hợp đồng quá hạn 24 giờ.");

    return 0;
})->purpose('Tự động xác nhận khoản hoàn cọc và hoàn tất hợp đồng sau 24 giờ khách không phản hồi');

Schedule::command('contracts:process-lifecycle')->dailyAt('01:15')->withoutOverlapping();
Schedule::command('temporary-residences:expire')->dailyAt('01:20')->withoutOverlapping();
Schedule::command('invoices:notify-due-today')->hourlyAt(5)->withoutOverlapping();
Schedule::command('invoices:notify-overdue')->dailyAt('00:10')->withoutOverlapping();
Schedule::command('deposit-refunds:auto-confirm-receipts')->everyMinute()->withoutOverlapping();
