<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donation_packages', function (Blueprint $table) {
            $table->string('border_color', 20)->default('blue')->after('sort_order');
            $table->boolean('is_featured')->default(false)->after('border_color');
        });
    }

    public function down(): void
    {
        Schema::table('donation_packages', function (Blueprint $table) {
            $table->dropColumn(['border_color', 'is_featured']);
        });
    }
};
