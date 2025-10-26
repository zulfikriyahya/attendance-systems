<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @php
            $date = date('dmY');
            echo $date . ' - Cetak Kartu Presensi';
        @endphp
    </title>
    <style>
        @page {
            size: 300mm 200mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .page {
            width: 300mm;
            height: 200mm;
            page-break-after: always;
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            padding: 5mm;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .kartu {
            width: 54.98mm;
            height: 86.6mm;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3mm;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .kartu img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            display: block;
            /* Mirorring */
            transform: scaleX(-1);
        }

        .kartu.belakang img {
            /* flip vertikal */
            transform: scaleY(-1);
        }


        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                width: 300mm;
                height: 200mm;
            }

            @page {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    {{-- Bagian Depan --}}
    @foreach ($pengajuans->chunk(10) as $chunk)
        <div class="page">
            @foreach ($chunk as $pengajuan)
                <div class="kartu">
                    <img src="{{ asset('storage/kartu/' . basename($pengajuan->user->avatar)) }}"
                        alt="{{ $pengajuan->user->name }}">
                </div>
            @endforeach
        </div>
    @endforeach
    {{-- Bagian Belakang --}}
    @foreach ($pengajuans->chunk(10) as $chunk)
        <div class="page">
            @foreach ($chunk as $pengajuan)
                <div class="kartu belakang">
                    <img src="{{ asset('/images/background-kartu.png') }}" alt="{{ $pengajuan->user->name }}">
                </div>
            @endforeach
        </div>
    @endforeach

</body>

</html>
