<?php

namespace App\Enums;

enum StatusPresensi: string
{
    case Libur = 'Libur';
    case Dispen = 'Dispen';
    case Hadir = 'Hadir';
    case Terlambat = 'Terlambat';
    case Alfa = 'Alfa';
    case Izin = 'Izin';
    case Cuti = 'Cuti';
    case DinasLuar = 'Dinas Luar';
    case Sakit = 'Sakit';

    public function label(): string
    {
        return match ($this) {
            self::Libur => 'Libur',
            self::Dispen => 'Dispen',
            self::Hadir => 'Hadir',
            self::Terlambat => 'Terlambat',
            self::Alfa => 'Alfa',
            self::Izin => 'Izin',
            self::Cuti => 'Cuti',
            self::DinasLuar => 'Dinas Luar',
            self::Sakit => 'Sakit',
        };
    }
}
