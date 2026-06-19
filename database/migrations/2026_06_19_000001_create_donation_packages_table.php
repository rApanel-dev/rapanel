<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('donation_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('description', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->decimal('price_usd', 8, 2);
            $table->unsignedInteger('cashpoints');
            $table->unsignedTinyInteger('bonus_percent')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_packages');
    }
};
