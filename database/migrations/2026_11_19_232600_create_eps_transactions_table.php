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
        if (!Schema::hasTable('eps_transactions')) {
            Schema::create('eps_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('customer_order_id', 100)->unique()->index();
                $table->string('merchant_transaction_id', 100)->unique()->index();
                $table->string('eps_transaction_id', 100)->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->string('purpose', 50)->default('verification'); // verification, add_balance, order
                $table->string('reference_id', 100)->nullable();
                $table->string('status', 30)->default('Initiated'); // Initiated, Success, Failed, Cancelled
                $table->string('financial_entity', 50)->nullable(); // bKash, Nagad, OKWallet, etc.
                $table->string('customer_name', 191)->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->string('customer_email', 191)->nullable();
                $table->text('redirect_url')->nullable();
                $table->longText('response_data')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eps_transactions');
    }
};
