<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tabla en el panel DB (conexión con prefijo ra_)
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('deleted_accounts_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('account_id')->unique();
            $table->string('original_userid', 23);
            $table->unsignedBigInteger('web_user_id');
            $table->timestamps();

            $table->foreign('web_user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('deleted_accounts_logs');
    }
};
