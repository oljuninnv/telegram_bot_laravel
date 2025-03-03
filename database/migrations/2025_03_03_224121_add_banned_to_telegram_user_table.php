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
        Schema::table('telegram_user', function (Blueprint $table) {
            $table->boolean('banned')->default(false)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('telegram_user', function (Blueprint $table) {
            $table->dropColumn('banned');
        });
    }
};
