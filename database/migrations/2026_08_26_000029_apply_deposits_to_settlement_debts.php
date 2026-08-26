<?php

use App\Models\Contract;
use App\Models\SettlementStatement;
use App\Services\SettlementService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(SettlementService::class);

        SettlementStatement::query()
            ->whereHas('contract', fn ($query) => $query->where('status', Contract::STATUS_SETTLING))
            ->orderBy('id')
            ->eachById(function (SettlementStatement $statement) use ($service): void {
                $service->refreshFinancials($statement);
            }, 50);
    }

    public function down(): void
    {
        // Các phiếu bù trừ là chứng từ tài chính nên không tự động xóa khi rollback.
    }
};
