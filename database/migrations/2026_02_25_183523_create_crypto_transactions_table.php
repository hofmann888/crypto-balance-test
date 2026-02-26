<?php

use App\Enums\CryptoCurrency;
use App\Enums\CryptoTransactionStatus;
use App\Enums\CryptoTransactionType;
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
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('currency', [CryptoCurrency::BTC_SATOSHI]);
            $table->enum('type', [CryptoTransactionType::DEPOSIT, CryptoTransactionType::WITHDRAWAL]);
            $table->enum('status', [
                CryptoTransactionStatus::PENDING,
                CryptoTransactionStatus::CONFIRMED,
                CryptoTransactionStatus::FAILED,
                CryptoTransactionStatus::CANCELLED,
            ]);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->string('tx_hash', 100)->nullable()->unique();
            $table->unsignedSmallInteger('confirmations')->default(0);
            $table->unsignedSmallInteger('required_confirmations')->default(6);
            $table->string('idempotency_key', 100)->unique();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'currency', 'status']);
            $table->index('tx_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
