<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('donation_packages')->nullOnDelete();
            $table->enum('platform', ['paypal', 'stripe', 'manual', 'mercadopago']);
            $table->string('transaction_id', 255)->nullable()->unique();
            $table->decimal('amount_usd', 8, 2);
            $table->unsignedInteger('cashpoints_awarded');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('platform');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
