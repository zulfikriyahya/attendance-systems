@foreach ($siswas as $siswa)
    @php
        $dataPresensi = $presensis[$siswa->id] ?? collect();
    @endphp

    @include('exports.presensi-siswa', [
        'siswa' => $siswa,
        'presensis' => $dataPresensi,
        'bulan' => $bulan,
        'tahun' => $tahun,
    ])
@endforeach
