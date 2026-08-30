<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('reactivated_at')->nullable()->after('deactivation_reason');
            $table->foreignId('reactivated_by')->nullable()->after('reactivated_at')
                ->constrained('users')->nullOnDelete();
            $table->text('reactivation_reason')->nullable()->after('reactivated_by');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->timestamp('restored_at')->nullable()->after('archive_reason');
            $table->foreignId('restored_by')->nullable()->after('restored_at')
                ->constrained('users')->nullOnDelete();
            $table->text('restoration_reason')->nullable()->after('restored_by');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->timestamp('restored_at')->nullable()->after('removal_reason');
            $table->foreignId('restored_by')->nullable()->after('restored_at')
                ->constrained('users')->nullOnDelete();
            $table->text('restoration_reason')->nullable()->after('restored_by');
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->timestamp('restored_at')->nullable()->after('retirement_reason');
            $table->foreignId('restored_by')->nullable()->after('restored_at')
                ->constrained('users')->nullOnDelete();
            $table->text('restoration_reason')->nullable()->after('restored_by');
        });
    }

    public function down(): void
    {
        foreach (['rooms', 'vehicles', 'tenants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('restored_by');
                $table->dropColumn(['restored_at', 'restoration_reason']);
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reactivated_by');
            $table->dropColumn(['reactivated_at', 'reactivation_reason']);
        });
    }
};
