<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // XuanNam adds invoice_type with the merged rental/deposit/settlement model.
    }

    public function down(): void
    {
        // No schema change is made by up().
    }
};
