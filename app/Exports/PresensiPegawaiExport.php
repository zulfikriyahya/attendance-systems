<?php

namespace App\Exports;

use App\Models\PresensiPegawai;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PresensiPegawaiExport implements FromCollection, WithHeadings
{
    protected $bulan;

    protected $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection()
    {
        return PresensiPegawai::with('pegawai.user')
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->get()
            ->map(function ($item) {
                return [
                    'Nama Lengkap' => $item->pegawai?->user?->name,
                    'Tanggal' => $item->tanggal->translatedFormat('l, d F Y'),
                    'Jam Datang' => $item->jamDatang ? Carbon::parse($item->jamDatang)->format('H:i:s').' WIB' : '-',
                    'Jam Pulang' => $item->jamPulang ? Carbon::parse($item->jamPulang)->format('H:i:s').' WIB' : '-',
                    'Status Presensi' => $item->statusPresensi->label() ?? '-',
                    'Status Pulang' => $item->statusPulang?->label() ?? '-',
                    'Catatan' => $item->catatan,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Hari/Tanggal',
            'Jam Datang',
            'Jam Pulang',
            'Status Datang',
            'Status Pulang',
            'Catatan',
        ];
    }
}
