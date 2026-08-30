<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('status', User::STATUS_FORMER)
            ->update(['status' => User::STATUS_ACTIVE]);
    }

    public function down(): void
    {
        // Không thể phân biệt an toàn tài khoản đang hoạt động với tài khoản
        // từng được chuyển từ trạng thái "former", nên không hoàn tác dữ liệu.
    }
};
