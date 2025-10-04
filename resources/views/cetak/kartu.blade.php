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
            size: A4 landscape;
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
            width: 297mm;
            height: 210mm;
            page-break-after: always;
            display: flex;
            flex-wrap: wrap;
            justify-content: justify;
            align-content: justify;
            padding: 5mm;
            gap: 3.4mm;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .kartu {
            width: 54.6mm;
            height: 86.8mm;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 2mm;
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
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                width: 297mm;
                height: 210mm;
            }

            @page {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">

    @foreach ($pengajuans->chunk(10) as $chunk)
        <div class="page">
            @foreach ($chunk as $pengajuan)
                <div class="kartu">
                    <img src="{{ Storage::url($pengajuan->user->avatar) }}" alt="{{ $pengajuan->user->name }}">
                </div>
            @endforeach
        </div>
    @endforeach

</body>

</html>
