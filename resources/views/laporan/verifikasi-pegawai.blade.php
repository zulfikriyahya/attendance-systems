<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Laporan Presensi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap');

        * {
            box-sizing: border-box;
            font-family: 'Ubuntu', sans-serif;
        }

        body {
            padding: 20px;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 24px;
            color: #222;
        }

        .info {
            background-color: #fff;
            border-left: 4px solid #4caf50;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-radius: 6px;
        }

        .info p {
            margin: 4px 0;
        }

        .verified {
            color: #4caf50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: 600;
        }

        td {
            background-color: #fff;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 20px;
            }

            th,
            td {
                font-size: 13px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <h1>Verifikasi Laporan Presensi Pegawai</h1>
    <div class="info" style="overflow-x: auto; padding: 16px; background: #fff; margin-bottom: 20px;">
        <p class="verified" style="margin-bottom: 10px; text-align: center;">✅ Dokumen ini sah dan diterbitkan secara
            otomatis oleh Sistem Presensi.</p>

        <table style="font-size: 13px; border-collapse: collapse; border: none;">
            <tr>
                <td style="width: 70px; border: none;"><strong>Nama</strong></td>
                <td style="border: none;">: {{ $pegawai->user->name }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>NIK/NIP/NUPTK</strong></td>
                <td style="border: none;">: {{ $pegawai->nip ?? '-' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Jabatan</strong></td>
                <td style="border: none;">: {{ optional($pegawai->jabatan)->nama }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Periode</strong></td>
                <td style="border: none;">: {{ \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F') }}
                    {{ $tahun }}</td>
            </tr>
        </table>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">No</th>
                    <th style="text-align: center;">Hari/Tanggal</th>
                    <th style="text-align: center;">Jam Datang</th>
                    <th style="text-align: center;">Jam Pulang</th>
                    <th style="text-align: center;">Status Presensi</th>
                    <th style="text-align: center;">Status Pulang</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presensis as $presensi)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td> <!-- Menampilkan nomor urut -->
                        <td style="text-align: center;">
                            {{ \Carbon\Carbon::parse($presensi->tanggal)->translatedFormat('l, d F Y') }}</td>
                        <td style="text-align: center;">{{ $presensi->jamDatang ?? '- WIB' }}</td>
                        <td style="text-align: center;">{{ $presensi->jamPulang ?? '- WIB' }}</td>
                        <td>{{ ucfirst($presensi->statusPresensi->value) }}</td>
                        <td>{{ ucfirst($presensi->statusPulang->value ?? '') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada data presensi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    <div class="footer">
        Dicetak Otomatis oleh Sistem Presensi {{ $pegawai->jabatan->instansi->nama ?? 'Madrasah' }}.
        {{-- {{ \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->setTime(16, 00)->translatedFormat('d F Y H:i') }} --}}

        <br>
        <span><a href="https://zedlabs.id" style="text-decoration: none; color: #4caf50;">Powered By ZEDLABS</a></span>
    </div>
</body>

</html>
