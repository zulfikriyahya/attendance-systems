<?php

namespace App\Enums;

enum StatusPulang: string
{
    case Pulang = 'Pulang';
    case PulangCepat = 'Pulang Sebelum Waktunya';
    case Bolos = 'Bolos';
    case Mangkir = 'Mangkir';

    public function label(): string
    {
        return match ($this) {
            self::Pulang => 'Pulang',
            self::PulangCepat => 'Pulang Sebelum Waktunya',
            self::Bolos => 'Bolos',
            self::Mangkir => 'Mangkir',
        };
    }
}
