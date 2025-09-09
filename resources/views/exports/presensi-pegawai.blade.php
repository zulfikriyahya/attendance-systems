<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Presensi Pegawai</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap');

        * {
            font-family: 'Ubuntu', sans-serif;
            box-sizing: border-box;
        }

        body {
            font-size: 11.5px;
            margin: 10px 20px;
        }

        h2 {
            margin: 10px 0 20px;
            font-size: 18px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .info-table td {
            border: none;
            padding: 5px 4px;
        }

        .rekap-table th,
        .rekap-table td {
            border: 1px solid #999;
        }

        .rekap-table th {
            background-color: #eaeaea;
        }

        .rekap-table tfoot td {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .two-columns {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .column {
            flex: 1;
        }

        .striped tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-container img {
            height: 70px;
        }

        .footer {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .qr-code img {
            height: 80px;
        }

        .footer-text {
            font-size: 10.5px;
            color: #333;
        }

        .page-break {
            page-break-after: always;
            height: 1px;
        }


        @media print {
            body {
                margin: 0;
            }

            @page {
                size: A4 portrait;
                margin: 10mm 25mm 10mm 25mm;
            }
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $rekap = [
            'Hadir' => $presensis->where('statusPresensi', 'Hadir')->count(),
            'Terlambat' => $presensis->where('statusPresensi', 'Terlambat')->count(),
            'Dinas Luar' => $presensis->where('statusPresensi', 'Dinas Luar')->count(),
            'Sakit' => $presensis->where('statusPresensi', 'Sakit')->count(),
            'Izin' => $presensis->where('statusPresensi', 'Izin')->count(),
            'Cuti' => $presensis->where('statusPresensi', 'Cuti')->count(),
            'Alfa' => $presensis->where('statusPresensi', 'Alfa')->count(),
            'Pulang' => $presensis->where('statusPulang', 'Pulang')->count(),
            'Pulang Sebelum Waktunya' => $presensis->where('statusPulang', 'Pulang Sebelum Waktunya')->count(),
            'Mangkir' => $presensis->where('statusPulang', 'Mangkir')->count(),
        ];

        $bulanText = Carbon::create()->month((int) $bulan)->translatedFormat('F') . ' ' . $tahun;
        $urlVerifikasi = route('laporan.pegawai.verifikasi', [
            'id' => $pegawai->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    @endphp

    <div class="logo-container">
        @php
            $logoPath = storage_path('app/public/' . $pegawai->jabatan->instansi->logoInstansi);
        @endphp

        @if (!empty($pegawai->jabatan->instansi->logoInstansi) && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo Instansi" style="max-height: 80px;">
        @else
            <img src="{{ public_path('images/default.png') }}" alt="Default Logo" style="max-height: 80px;">
        @endif

    </div>

    <h2>Laporan Presensi Pegawai</h2>

    <div class="two-columns">
        <div class="column">
            <table class="info-table">
                <tr>
                    <td style="width: 100px;"><strong>Nama</strong></td>
                    <td>: {{ $pegawai->user->name }}</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>: {{ $pegawai->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Jabatan</strong></td>
                    <td>: {{ optional($pegawai->jabatan)->nama }}</td>
                </tr>
                <tr>
                    <td><strong>Bulan</strong></td>
                    <td>: {{ $bulanText }}</td>
                </tr>
            </table>
        </div>
        <div class="column">
            <table class="rekap-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Status</th>
                        <th style="width: 25%; text-align: center;">Jumlah</th>
                        <th style="width: 25%;">Status</th>
                        <th style="width: 25%; text-align: center;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_chunk($rekap, 2, true) as $row)
                        <tr>
                            @foreach ($row as $status => $jumlah)
                                <td style="width: 25%;">{{ $status }}</td>
                                <td style="width: 25%; text-align: center;">{{ $jumlah }} Hari</td>
                            @endforeach
                            @if (count($row) < 2)
                                <td style="width: 25%;"></td>
                                <td style="width: 25%;"></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <!--<tfoot>-->
                <!--    <tr>-->
                <!--        <td colspan="3"><strong>Total</strong></td>-->
                <!--        <td style="text-align: center;"><strong>{{ array_sum($rekap) }} Hari</strong></td>-->
                <!--    </tr>-->
                <!--</tfoot>-->
            </table>
        </div>
    </div>

    <table class="striped">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 25%; text-align: center;">Hari/Tanggal</th>
                <th style="width: 25%; text-align: center;">Jam Masuk</th>
                <th style="width: 25%; text-align: center;">Jam Pulang</th>
                <th style="width: 20%; text-align: center;">Status Masuk</th>
                <th style="width: 20%; text-align: center;">Status Pulang</th>
                {{-- <th style="width: 20%; text-align: center;">Status Persetujuan</th> --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($presensis as $presensi)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l, d F Y') }}</td>
                    <td>{{ $presensi->jamDatang ? \Carbon\Carbon::parse($presensi->jamDatang)->format('H:i:s') : '-' }}
                        {{ ' WIB' }}</td>
                    <td>{{ $presensi->jamPulang ? \Carbon\Carbon::parse($presensi->jamPulang)->format('H:i:s') : '-' }}
                        {{ ' WIB' }}</td>
                    <td>{{ ucfirst($presensi->statusPresensi->value) }}</td>
                    <td>{{ ucfirst(optional($presensi->statusPulang)->label()) ?? '-' }}</td>
                    {{-- <td>{{ ucfirst(optional($presensi->statusApproval)->label()) ?? '-' }}</td> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data presensi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-text">
            <p>Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
            <p>Oleh Sistem Presensi {{ $pegawai->jabatan->instansi->nama ?? 'Instansi' }}</p>
        </div>
        <div class="qr-code">
            <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(100)->generate($urlVerifikasi)) !!}" alt="QR Code Verifikasi">
        </div>
    </div>

</body>

</html>
