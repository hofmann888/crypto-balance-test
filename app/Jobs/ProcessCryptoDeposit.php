<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Enums\CryptoCurrency;
use App\Services\CryptoBalanceService;
use App\Services\BlockchainService;

class ProcessCryptoDeposit implements ShouldQueue
{
    use Queueable;

    public int $tries = 100;
    public int $delay = 300;

    public function __construct(
        private readonly int $userId,
        private readonly int $amount,
        private readonly string $txHash,
        private readonly CryptoCurrency $currency,
    ) {}

    public function handle(BlockchainService $blockchain, CryptoBalanceService $balanceService): void
    {
        $confirmations = $blockchain->getConfirmations($this->txHash);

        if ($confirmations < $this->currency->requiredConfirmations()) {
            $this->release($this->delay);
            return;
        }

        $balanceService->deposit(
            $this->userId,
            $this->amount,
            $confirmations,
            $this->txHash,
            $this->currency,
        );
    }
}