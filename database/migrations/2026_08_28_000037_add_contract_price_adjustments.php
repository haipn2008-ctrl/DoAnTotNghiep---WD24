<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->decimal('electric_price_snapshot', 10, 2)->nullable()->after('deposit_amount');
            $table->decimal('water_price_snapshot', 10, 2)->nullable()->after('electric_price_snapshot');
            $table->decimal('internet_fee_snapshot', 10, 2)->nullable()->after('water_price_snapshot');
            $table->decimal('service_fee_snapshot', 10, 2)->nullable()->after('internet_fee_snapshot');
        });

        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->json('price_adjustments')->nullable()->after('content');
        });

        $fallback = DB::table('settings')->where('is_active', true)->latest('id')->first();
        DB::table('contracts')->whereNotNull('signed_at')->orderBy('id')->chunkById(200, function ($contracts) use ($fallback): void {
            foreach ($contracts as $contract) {
                $referenceDate = Carbon::parse($contract->signed_at ?? $contract->start_date ?? $contract->created_at)->startOfMonth();
                $rates = DB::table('fee_schedules')
                    ->whereDate('effective_from', '<=', $referenceDate->toDateString())
                    ->orderByDesc('effective_from')
                    ->orderByDesc('id')
                    ->first() ?? $fallback;

                if (! $rates) {
                    continue;
                }

                DB::table('contracts')->where('id', $contract->id)->update([
                    'electric_price_snapshot' => $rates->electric_price,
                    'water_price_snapshot' => $rates->water_price,
                    'internet_fee_snapshot' => $rates->internet_fee,
                    'service_fee_snapshot' => $rates->service_fee,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->dropColumn('price_adjustments');
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn([
                'electric_price_snapshot',
                'water_price_snapshot',
                'internet_fee_snapshot',
                'service_fee_snapshot',
            ]);
        });
    }
};
