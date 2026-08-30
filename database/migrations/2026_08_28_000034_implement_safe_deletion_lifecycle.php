<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deactivated_at')->nullable()->after('last_login_at');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')
                ->constrained('users')->nullOnDelete();
            $table->text('deactivation_reason')->nullable()->after('deactivated_by');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->index()->after('user_id');
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->foreignId('archived_by')->nullable()->after('archived_at')
                ->constrained('users')->nullOnDelete();
            $table->text('archive_reason')->nullable()->after('archived_by');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('archived_license_plate')->nullable()->after('license_plate');
            $table->timestamp('removed_at')->nullable()->after('review_note');
            $table->foreignId('removed_by')->nullable()->after('removed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('removal_reason')->nullable()->after('removed_by');
        });

        // Convert the old enum to a string so "retired" is portable across databases.
        Schema::table('rooms', function (Blueprint $table): void {
            $table->string('status', 20)->default('available')->change();
            $table->timestamp('retired_at')->nullable()->after('status');
            $table->foreignId('retired_by')->nullable()->after('retired_at')
                ->constrained('users')->nullOnDelete();
            $table->text('retirement_reason')->nullable()->after('retired_by');
        });

        Schema::table('contract_histories', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('support_requests', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->foreign('role_id')->references('id')->on('roles')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->foreign('role_id')->references('id')->on('roles')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('support_requests', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('contract_histories', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('retired_by');
            $table->dropColumn(['retired_at', 'retirement_reason']);
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('removed_by');
            $table->dropColumn(['archived_license_plate', 'removed_at', 'removal_reason']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn(['status', 'archived_at', 'archive_reason']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn(['deactivated_at', 'deactivation_reason']);
        });
    }
};
