<?php

namespace App\Enums;

enum CryptoTransactionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public const PENDING = self::Pending->value;

    public const CONFIRMED = self::Confirmed->value;

    public const FAILED = self::Failed->value;

    public const CANCELLED = self::Cancelled->value;
}
