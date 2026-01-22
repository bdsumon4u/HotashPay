<?php

use App\Enums\InvoiceStatus;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id()->startingValue(1001);
            $table->binary('ulid', 16)->unique();
            $table->string('client_name')->default('Mr. X');
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->integer('amount');
            $table->string('currency')->default('BDT');
            $table->string('status')->default(InvoiceStatus::PENDING);
            $table->text('redirect_url')->nullable();
            $table->text('cancel_url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
