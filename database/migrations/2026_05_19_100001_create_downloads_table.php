<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('download_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            $table->boolean('is_external')->default(false);
            $table->boolean('is_external_url_hidden')->default(false);
            $table->string('file_url', 2048)->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable(); // local upload path

            $table->boolean('is_only_auth')->default(false);
            $table->boolean('is_active')->default(true);
            $table->bigInteger('download_count')->default(0);
            $table->integer('sort_order')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
