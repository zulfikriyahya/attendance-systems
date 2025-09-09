@foreach ($pegawais as $pegawai)
    @php
        $dataPresensi = $presensis[$pegawai->id] ?? collect();
    @endphp

    @include('exports.presensi-pegawai', [
        'pegawai' => $pegawai,
        'presensis' => $dataPresensi,
        'bulan' => $bulan,
        'tahun' => $tahun,
    ])
@endforeach
