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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type')->default('manual');
            $table->string('sim')->nullable();
            $table->text('message');
            $table->string('provider')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('mobile');
            $table->string('trxid')->index();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('status')->default('review');
            $table->timestamps();

            $table->unique(['provider', 'trxid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
