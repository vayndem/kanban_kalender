<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Pembayaran</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: never;
        }

        .brand-header {
            margin-bottom: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .brand-strip {
            height: 10px;
            background: #1d4ed8;
            border-bottom: 4px solid #f97316;
        }

        .brand-body {
            padding: 12px 14px 10px;
        }

        .brand-kicker {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #f97316;
            margin-bottom: 4px;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 9px;
            color: #475569;
        }

        .header {
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #334155;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            margin-top: 3px;
            color: #475569;
            font-size: 9px;
        }

        .filter-box {
            margin-top: 8px;
            padding: 8px 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .filter-item {
            margin-bottom: 2px;
            color: #334155;
            font-size: 9px;
        }

        .summary-grid {
            width: 100%;
            margin-top: 10px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .summary-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 8px;
            border-radius: 6px;
        }

        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }

        .summary-value {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .section-title {
            margin-top: 14px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
        }

        th {
            background: #0f172a;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #64748b;
            font-size: 8px;
        }

        .status-badge {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: 700;
            color: #fff;
        }

        .status-0 {
            background: #dc2626;
        }

        .status-1 {
            background: #d97706;
        }

        .status-2 {
            background: #059669;
        }

        .empty-state {
            padding: 24px 10px;
            text-align: center;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>

<body>
    @foreach ($allData as $statusName => $data)
        @php
            $flattened = $data['groups']->flatten(1);
            $totalNominal = $flattened->sum('harga');
            $totalSudahDibayar = $flattened->sum('total_sudah_dibayar');
            $totalKeluarga = $data['groups']->count();
        @endphp

        <div class="page-break">
            <div class="brand-header">
                <div class="brand-strip"></div>
                <div class="brand-body">
                    <div class="brand-kicker">Brand Report</div>
                    <div class="brand-name">E-Ling Course</div>
                    <div class="brand-subtitle">Laporan resmi administrasi dan operasional lembaga.</div>
                </div>
            </div>

            <div class="header">
                <div class="title">Laporan Administrasi Pembayaran</div>
                <div class="subtitle">E-Ling Course • Diekspor pada {{ $exportedAt }}</div>

                <div class="filter-box">
                    @foreach ($filterSummary as $item)
                        <div class="filter-item">{{ $item }}</div>
                    @endforeach
                </div>

                <table class="summary-grid">
                    <tr>
                        <td class="summary-card">
                            <div class="summary-label">Status Laporan</div>
                            <div class="summary-value">
                                <span class="status-badge status-{{ $data['code'] }}">{{ strtoupper($statusName) }}</span>
                            </div>
                        </td>
                        <td class="summary-card">
                            <div class="summary-label">Total Keluarga</div>
                            <div class="summary-value">{{ $totalKeluarga }}</div>
                        </td>
                        <td class="summary-card">
                            <div class="summary-label">Total Nominal</div>
                            <div class="summary-value">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
                        </td>
                        <td class="summary-card">
                            <div class="summary-label">Total Sudah Dibayar</div>
                            <div class="summary-value">Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="section-title">Rincian Kelompok Keluarga</div>

            @if ($data['groups']->isEmpty())
                <div class="empty-state">Tidak ada data pembayaran pada kategori ini.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%">No HP Keluarga</th>
                            <th style="width: 18%">Nama Siswa</th>
                            <th style="width: 29%">Keterangan Tagihan</th>
                            <th style="width: 12%">Nominal</th>
                            <th style="width: 12%">Sudah Dibayar</th>
                            <th style="width: 14%">Keterangan Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['groups'] as $noHp => $pembayarans)
                            @php
                                $rowspan = $pembayarans->count();
                                $keluargaDiskon = $diskons->get($noHp);
                            @endphp
                            @foreach ($pembayarans as $index => $item)
                                <tr>
                                    @if ($index === 0)
                                        <td rowspan="{{ $rowspan }}" class="text-center">
                                            <strong>{{ $noHp ?: '-' }}</strong>
                                            @if ($keluargaDiskon)
                                                <div class="muted" style="margin-top:4px;">
                                                    Diskon: Rp {{ number_format((int) $keluargaDiskon->diskon, 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ $item->siswa->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $item->keterangan ?? '-' }}
                                        <div class="muted">
                                            Input: {{ optional($item->created_at)->translatedFormat('d M Y') ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-right">Rp {{ number_format((int) $item->harga, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format((int) $item->total_sudah_dibayar, 0, ',', '.') }}</td>
                                    <td>
                                        @if ((int) $item->status === 2)
                                            {{ $item->pembayaran_via === 1 ? 'Transfer Bank' : 'Cash / Tunai' }}
                                            <div class="muted">
                                                {{ $item->tanggal_pembayaran ? \Carbon\Carbon::parse($item->tanggal_pembayaran)->translatedFormat('d M Y') : '-' }}
                                            </div>
                                        @elseif ((int) $item->status === 1)
                                            Sudah ditagihkan
                                        @else
                                            Menunggu pembayaran
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>

</html>
