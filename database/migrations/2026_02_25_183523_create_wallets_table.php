<?php

use App\Enums\CryptoCurrency;
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
        Schema::create('user_crypto_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('currency', [CryptoCurrency::BTC_SATOSHI]);
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('locked_balance')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_crypto_balances');
    }
};
