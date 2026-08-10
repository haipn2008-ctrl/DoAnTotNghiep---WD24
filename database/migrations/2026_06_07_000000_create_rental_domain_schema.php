<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Database hiện tại đã có các bảng nghiệp vụ.
        // Migration này chỉ được đánh dấu là đã chạy,
        // không tạo lại các bảng.
    }

    public function down(): void
    {
        // Không xóa các bảng hiện tại.
    }
};
