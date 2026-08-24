<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        // The XuanNam billing-period migration owns the final invoice constraint.
    }

    public function down(): void
    {
        // No schema change is made by up().
    }
};
