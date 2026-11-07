<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize bKash merchant messages
        DB::table('transactions')
            ->where('provider', 'bKash')
            ->where('message', 'like', '%received payment Tk%')
            ->update(['provider' => 'bkash-merchant']);

        // Normalize remaining bKash to bkash-personal
        DB::table('transactions')
            ->where('provider', 'bKash')
            ->update(['provider' => 'bkash-personal']);

        // Normalize NAGAD to nagad-personal
        DB::table('transactions')
            ->where('provider', 'NAGAD')
            ->update(['provider' => 'nagad-personal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
