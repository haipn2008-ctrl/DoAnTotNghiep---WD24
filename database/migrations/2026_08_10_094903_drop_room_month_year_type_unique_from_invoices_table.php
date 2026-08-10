<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tạm thời chưa xóa index.
        // Index đang được foreign key sử dụng.
    }

    public function down(): void
    {
        //
    }
};