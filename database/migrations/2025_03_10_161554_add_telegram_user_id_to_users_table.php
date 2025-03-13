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
        Schema::table('moonshine_users', function (Blueprint $table) {
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table) {
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_user')->onDelete('cascade');
        });
    }
};
