<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('telegram_user', function (Blueprint $table) {
            $table->id(); // Автоинкрементный первичный ключ
            $table->bigInteger('telegram_id')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable(); 
            $table->string('username')->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user');
    }
};
