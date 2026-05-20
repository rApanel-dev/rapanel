<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mvp_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_id')->unique();
            $table->string('name_override', 255)->nullable();
            $table->string('image_path', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mvp_cards');
    }
};
