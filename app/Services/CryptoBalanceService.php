<?php

namespace App\Services;

use App\Enums\CryptoCurrency;
use App\Enums\CryptoTransactionStatus;
use App\Enums\CryptoTransactionType;
use App\Models\CryptoTransaction;
use App\Models\UserCryptoBalance;
use Illuminate\Support\Facades\DB;

class CryptoBalanceService
{
    public function deposit(
        int $userId,
        int $amount,
        int $confirmations,
        string $txHash,
        CryptoCurrency $currency,
    ): CryptoTransaction {
        $idempotencyKey = "deposit:{$currency->value}:{$txHash}";

        $existing = CryptoTransaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($userId, $currency, $amount, $txHash, $confirmations, $idempotencyKey) {

            $balance = UserCryptoBalance::lockForUpdate()
                ->firstOrCreate(['user_id' => $userId, 'currency' => $currency]);

            $balanceBefore = $balance->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $balance->update(['balance' => $balanceAfter]);

            return CryptoTransaction::create([
                'user_id'                => $userId,
                'currency'               => $currency,
                'type'                   => CryptoTransactionType::DEPOSIT,
                'status'                 => CryptoTransactionStatus::CONFIRMED,
                'amount'                 => $amount,
                'balance_before'         => $balanceBefore,
                'balance_after'          => $balanceAfter,
                'tx_hash'                => $txHash,
                'confirmations'          => $confirmations,
                'required_confirmations' => $confirmations,
                'idempotency_key'        => $idempotencyKey,
            ]);
        });
    }

    public function lockFunds(
        int $userId,
        int $amount,
        string $idempotencyKey,
        CryptoCurrency $currency,
        array $meta = []
    ): CryptoTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Withdrawal amount must be positive.');
        }

        return DB::transaction(function () use ($userId, $amount, $idempotencyKey, $currency, $meta): CryptoTransaction {
            $existing = CryptoTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof CryptoTransaction) {
                return $existing;
            }

            $balance = UserCryptoBalance::query()
                ->where('user_id', $userId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $balance->balance - $balance->locked_balance;

            if ($available < $amount) {
                throw new \RuntimeException('Insufficient available balance.');
            }

            $balance->increment('locked_balance', $amount);

            return CryptoTransaction::query()->create([
                'user_id' => $userId,
                'currency' => $currency,
                'type' => CryptoTransactionType::WITHDRAWAL,
                'status' => CryptoTransactionStatus::PENDING,
                'amount' => $amount,
                'balance_before' => $balance->balance,
                'balance_after' => $balance->balance,
                'confirmations' => 0,
                'required_confirmations' => $currency->requiredConfirmations(),
                'idempotency_key' => $idempotencyKey,
                'meta' => $meta === [] ? null : $meta,
            ]);
        });
    }

    public function confirmWithdrawal(
        int $txId,
        ?string $txHash = null,
        int $confirmations = 0,
    ): CryptoTransaction {
        return DB::transaction(function () use ($txId, $txHash, $confirmations): CryptoTransaction {
            $tx = CryptoTransaction::getWithdrawalTransactionForUpdate($txId);

            $tx->tx_hash = $txHash ?? $tx->tx_hash;
            $tx->confirmations = max($tx->confirmations, $confirmations);

            if ($tx->confirmations < $tx->required_confirmations) {
                $tx->save();
                return $tx;
            }

            $balance = UserCryptoBalance::query()
                ->where('user_id', $tx->user_id)
                ->where('currency', $tx->currency->value)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->locked_balance < $tx->amount || $balance->balance < $tx->amount) {
                throw new \RuntimeException('Inconsistent balance state for withdrawal confirmation.');
            }

            $balanceBefore = $balance->balance;
            $balance->locked_balance -= $tx->amount;
            $balance->balance -= $tx->amount;
            $balance->save();

            $tx->status = CryptoTransactionStatus::CONFIRMED;
            $tx->balance_before = $balanceBefore;
            $tx->balance_after = $balance->balance;
            $tx->save();

            return $tx;
        });
    }

    public function cancelWithdrawal(int $txId, ?string $reason = null): CryptoTransaction
    {
        return DB::transaction(function () use ($txId, $reason): CryptoTransaction {
            $tx = CryptoTransaction::getWithdrawalTransactionForUpdate($txId);

            $balance = UserCryptoBalance::query()
                ->where('user_id', $tx->user_id)
                ->where('currency', $tx->currency->value)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->locked_balance < $tx->amount) {
                throw new \RuntimeException('Locked balance is lower than withdrawal amount.');
            }

            $balance->locked_balance -= $tx->amount;
            $balance->save();

            $meta = $tx->meta ?? [];

            if (is_string($reason) && $reason !== '') {
                $meta['cancel_reason'] = $reason;
            }

            $tx->status = CryptoTransactionStatus::CANCELLED;
            $tx->balance_before = $balance->balance;
            $tx->balance_after = $balance->balance;
            $tx->meta = $meta === [] ? null : $meta;
            $tx->save();

            return $tx;
        });
    }
}
