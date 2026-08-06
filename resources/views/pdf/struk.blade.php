<!DOCTYPE html>
<html>

<head>
    <title>Bukti Pelunasan Pembayaran</title>
    <style>
        body {
            font-family: monospace;
            font-size: 10px;
            width: 100%;
            margin: 0;
            padding: 5px;
        }

        .brand-header {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 8px;
            background: #fff;
        }

        .brand-strip {
            height: 8px;
            background: #1d4ed8;
            border-bottom: 3px solid #f97316;
        }

        .brand-body {
            padding: 8px 10px;
        }

        .brand-kicker {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #f97316;
            margin-bottom: 2px;
        }

        .brand-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-subtitle {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo-img {
            width: 60px;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="brand-header">
        <div class="brand-strip"></div>
        <div class="brand-body">
            <div class="brand-kicker">Brand Receipt</div>
            <div class="brand-name">E-Ling Course</div>
            <div class="brand-subtitle">Bukti pelunasan pembayaran resmi</div>
        </div>
    </div>

    @if (!empty($logoDataUri))
        <div class="logo-container">
            <img src="{{ $logoDataUri }}" class="logo-img">
        </div>
    @endif

    <div class="text-center bold">
        BUKTI PELUNASAN PEMBAYARAN
    </div>
    <div class="line"></div>
    <table>
        <tr>
            <td>No HP Keluarga:</td>
            <td class="text-right">{{ $no_hp }}</td>
        </tr>
        <tr>
            <td>Nama Siswa:</td>
            <td class="text-right font-bold">
                {{ $pembayarans->map(fn($p) => $p->siswa->name ?? '')->unique()->implode(', ') }}</td>
        </tr>
        <tr>
            <td>Tanggal Cetak:</td>
            <td class="text-right">{{ now()->translatedFormat('d M Y H:i') }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="bold">RINCIAN KOMPONEN TAGIHAN:</div>
    <table>
        @foreach ($pembayarans as $p)
            <tr>
                <td>{{ $p->keterangan }}</td>
                <td class="text-right">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <div class="bold">RINCIAN PENERIMAAN PEMBAYARAN:</div>
    <table>
        @php
            $allDetails = $pembayarans
                ->flatMap(fn($p) => $p->details->map(fn($detail) => [
                    'tanggal' => optional($detail->created_at)->translatedFormat('d M Y'),
                    'keterangan' => $detail->keterangan ?: 'Tanpa keterangan',
                    'nominal' => (int) $detail->pembayaran,
                ]))
                ->values();
        @endphp
        @forelse ($allDetails as $detail)
            <tr>
                <td>
                    {{ $detail['tanggal'] }}<br>
                    {{ $detail['keterangan'] }}
                </td>
                <td class="text-right">Rp {{ number_format($detail['nominal'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td>Tidak ada detail penerimaan pembayaran.</td>
                <td class="text-right">-</td>
            </tr>
        @endforelse
    </table>
    <div class="line"></div>
    @php
        $totalKotor = $pembayarans->sum('harga');
        $totalAkhir = $totalKotor - $nominalDiskon;
        if ($totalAkhir < 0) {
            $totalAkhir = 0;
        }
    @endphp
    <table>
        <tr>
            <td>Total Tagihan Kotor:</td>
            <td class="text-right">Rp {{ number_format($totalKotor, 0, ',', '.') }}</td>
        </tr>
        @if ($diskon)
            <tr>
                <td>Potongan Keluarga ({{ $diskon->keterangan }}):</td>
                <td class="text-right">-Rp {{ number_format((int) $diskon->diskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if (!empty($diskonUniversal))
            <tr>
                <td>Potongan Universal ({{ $diskonUniversal->keterangan }}):</td>
                <td class="text-right">-Rp {{ number_format((int) $diskonUniversal->diskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td>Total Sudah Dibayar:</td>
            <td class="text-right">Rp {{ number_format($pembayarans->sum('total_sudah_dibayar'), 0, ',', '.') }}</td>
        </tr>
        <tr class="bold">
            <td>TOTAL KEWAJIBAN BERSIH:</td>
            <td class="text-right">Rp {{ number_format($totalAkhir, 0, ',', '.') }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="text-center bold" style="font-size: 11px; margin-top: 5px;">
        STATUS ADMINISTRASI: LUNAS<br>
        DOKUMEN INI MENUNJUKKAN BUKTI PELUNASAN YANG TERCATAT
    </div>
</body>

</html>
