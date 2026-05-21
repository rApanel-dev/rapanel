<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_db', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_id')->unique();
            $table->string('aegis_name', 100);
            $table->string('name', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('type', 50)->default('Etc');
            $table->string('subtype', 50)->nullable();
            $table->unsignedTinyInteger('slots')->default(0);
            $table->text('description_html')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('aegis_name');
            $table->index('display_name');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_db');
    }
};
