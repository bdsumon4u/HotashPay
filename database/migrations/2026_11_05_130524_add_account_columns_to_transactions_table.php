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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('sender_account')->nullable()->after('mobile')->index();
            $table->string('receiver_account')->nullable()->after('sender_account')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['sender_account']);
            $table->dropIndex(['receiver_account']);
            $table->dropColumn(['sender_account', 'receiver_account']);
        });
    }
};
