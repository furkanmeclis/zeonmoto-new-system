<?php

namespace App;

enum OrderStatus: string
{
    case Draft = 'DRAFT';
    case New = 'NEW';
    case Preparing = 'PREPARING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::New => 'Yeni',
            self::Preparing => 'Hazırlanıyor',
            self::Completed => 'Tamamlandı',
            self::Cancelled => 'İptal Edildi',
        };
    }
}
