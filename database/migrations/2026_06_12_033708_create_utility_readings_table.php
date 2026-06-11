<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade'); // Liên kết bảng phòng
            $table->integer('month'); 
            $table->integer('year'); 

            $table->integer('electricity_old')->default(0);
            $table->integer('electricity_new');

            $table->integer('water_old')->default(0);
            $table->integer('water_new');
            
            // Trạng thái: 0 = Nháp (đang kiểm tra), 1 = Đã chốt (không cho sửa)
            $table->tinyInteger('status')->default(0); 
            
            $table->timestamps();
            
            // Đảm bảo mỗi phòng chỉ có 1 bản ghi điện nước mỗi tháng
            $table->unique(['room_id', 'month', 'year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('utility_readings');
    }
};
