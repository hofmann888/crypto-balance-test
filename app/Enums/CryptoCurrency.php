<?php

namespace App\Enums;

enum CryptoCurrency: string
{
    case BtcSatoshi = 'btc_satoshi';

    public const BTC_SATOSHI = self::BtcSatoshi->value;

    public function requiredConfirmations(): int
    {
        return match($this) {
            self::BtcSatoshi  => 6,
        };
    }
}
