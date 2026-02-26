<?php

namespace App\Models;

use App\Enums\CryptoCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCryptoBalance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'locked_balance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => CryptoCurrency::class,
            'balance' => 'integer',
            'locked_balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
