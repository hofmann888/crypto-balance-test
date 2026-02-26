<?php

namespace App\Enums;

enum CryptoTransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';

    public const DEPOSIT = self::Deposit->value;

    public const WITHDRAWAL = self::Withdrawal->value;
}
