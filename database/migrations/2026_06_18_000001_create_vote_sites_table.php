<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('vote_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('icon_url', 500)->nullable();
            $table->string('vote_url', 500);
            $table->enum('callback_type', ['honor', 'callback'])->default('honor');
            $table->string('callback_secret', 255)->nullable();
            $table->string('callback_ip', 100)->nullable();
            $table->unsignedInteger('points_per_vote')->default(1);
            $table->unsignedInteger('cooldown_hours')->default(12);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_sites');
    }
};
