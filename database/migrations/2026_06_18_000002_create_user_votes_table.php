<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('user_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vote_site_id')->constrained('vote_sites')->cascadeOnDelete();
            $table->timestamp('voted_at');
            $table->unsignedInteger('points_awarded')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'vote_site_id', 'voted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_votes');
    }
};
