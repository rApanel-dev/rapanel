<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ra_donations MODIFY COLUMN platform ENUM('paypal','stripe','manual','mercadopago') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ra_donations MODIFY COLUMN platform ENUM('paypal','stripe','manual') NOT NULL");
    }
};
