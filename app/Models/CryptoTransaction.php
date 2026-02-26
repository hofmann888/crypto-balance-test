<?php

namespace App\Models;

use App\Enums\CryptoCurrency;
use App\Enums\CryptoTransactionStatus;
use App\Enums\CryptoTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'tx_hash',
        'confirmations',
        'required_confirmations',
        'idempotency_key',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'currency' => CryptoCurrency::class,
            'type' => CryptoTransactionType::class,
            'status' => CryptoTransactionStatus::class,
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'confirmations' => 'integer',
            'required_confirmations' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getWithdrawalTransactionForUpdate(int $txId): CryptoTransaction
    {
        $tx = CryptoTransaction::query()
            ->where('id', $txId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($tx->type->value !== CryptoTransactionType::WITHDRAWAL) {
            throw new \RuntimeException('Transaction is not a withdrawal.');
        }

        if ($tx->status->value !== CryptoTransactionStatus::PENDING) {
            throw new \RuntimeException('Transaction is not in a pending status.');
        }

        return $tx;
    }
}
