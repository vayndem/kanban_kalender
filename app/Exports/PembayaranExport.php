<?php

namespace App\Exports;

use App\Exports\Concerns\MemaksaTeksUntukAwalanPlus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembayaranExport implements WithMultipleSheets
{
    protected Collection $pembayarans;

    protected Collection $diskons;

    protected array $filterSummary;

    public function __construct(Collection $pembayarans, Collection $diskons, array $filterSummary = [])
    {
        $this->pembayarans = $pembayarans;
        $this->diskons = $diskons;
        $this->filterSummary = $filterSummary;
    }

    public function sheets(): array
    {
        return [
            new PembayaranRingkasanKeluargaSheet($this->pembayarans, $this->diskons, $this->filterSummary),
            new PembayaranDetailInvoiceSheet($this->pembayarans, $this->filterSummary),
            new PembayaranDetailCicilanSheet($this->pembayarans, $this->filterSummary),
        ];
    }
}

class PembayaranRingkasanKeluargaSheet implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    protected Collection $pembayarans;

    protected Collection $diskons;

    protected array $filterSummary;

    private const STATUS_LABELS = [0 => 'Belum Bayar', 1 => 'Tertagih', 2 => 'Lunas'];

    public function __construct(Collection $pembayarans, Collection $diskons, array $filterSummary)
    {
        $this->pembayarans = $pembayarans;
        $this->diskons = $diskons;
        $this->filterSummary = $filterSummary;
    }

    public function title(): string
    {
        return 'Ringkasan Keluarga';
    }

    public function collection()
    {
        $diskonByPhone = $this->diskons->keyBy('no_hp');
        $diskonUniversal = $this->diskons->firstWhere('no_hp', null);

        return $this->pembayarans->groupBy('no_hp')->map(function (Collection $group, $noHp) use ($diskonByPhone, $diskonUniversal) {
            $totalHarga = (int) $group->sum('harga');
            $totalDibayar = (int) $group->sum('total_sudah_dibayar');
            $diskonSpesifik = $diskonByPhone->get($noHp);
            $nominalDiskon = (int) ($diskonSpesifik->diskon ?? 0) + (int) ($diskonUniversal->diskon ?? 0);
            $totalAkhir = max(0, $totalHarga - $nominalDiskon);
            $statuses = $group->pluck('status')->map(fn ($s) => (int) $s);
            $status = $statuses->every(fn ($s) => $s === 2)
                ? 2
                : ($statuses->contains(fn ($s) => in_array($s, [1, 2], true)) ? 1 : 0);

            return [
                'no_hp' => $noHp,
                'siswa_names' => $group->pluck('siswa.name')->filter()->unique()->implode(', '),
                'total_harga' => $totalHarga,
                'total_dibayar' => $totalDibayar,
                'nominal_diskon' => $nominalDiskon,
                'total_akhir' => $totalAkhir,
                'sisa' => max(0, $totalAkhir - $totalDibayar),
                'status' => self::STATUS_LABELS[$status],
                'keterangan' => $group->pluck('keterangan')->filter()->unique()->implode(' | '),
                'tanggal_terakhir' => $group->pluck('tanggal_pembayaran')->filter()->max(),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PEMBAYARAN - RINGKASAN PER KELUARGA'],
            [implode(' | ', $this->filterSummary)],
            [''],
            ['No. HP', 'Nama Siswa', 'Total Tagihan', 'Total Dibayar', 'Diskon', 'Total Setelah Diskon', 'Sisa Tagihan', 'Status', 'Keterangan', 'Tanggal Terakhir Bayar'],
        ];
    }

    public function map($row): array
    {
        return [
            $row['no_hp'] ?: '-',
            $row['siswa_names'] ?: '-',
            $row['total_harga'],
            $row['total_dibayar'],
            $row['nominal_diskon'],
            $row['total_akhir'],
            $row['sisa'],
            $row['status'],
            $row['keterangan'] ?: '-',
            $row['tanggal_terakhir'] ? Carbon::parse($row['tanggal_terakhir'])->format('d/m/Y') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            ],
        ];
    }
}

class PembayaranDetailInvoiceSheet implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    protected Collection $pembayarans;

    protected array $filterSummary;

    private const STATUS_LABELS = [0 => 'Belum Bayar', 1 => 'Tertagih', 2 => 'Lunas'];

    private const METODE_LABELS = [0 => 'Cash', 1 => 'Transfer'];

    public function __construct(Collection $pembayarans, array $filterSummary)
    {
        $this->pembayarans = $pembayarans;
        $this->filterSummary = $filterSummary;
    }

    public function title(): string
    {
        return 'Detail Invoice';
    }

    public function collection()
    {
        return $this->pembayarans->sortBy('no_hp')->values();
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PEMBAYARAN - DETAIL INVOICE'],
            [implode(' | ', $this->filterSummary)],
            [''],
            ['ID Invoice', 'No. HP', 'Nama Siswa', 'Kelas', 'Keterangan', 'Nominal Tagihan', 'Sudah Dibayar', 'Sisa', 'Status', 'Metode Bayar', 'Tanggal Bayar', 'Dibuat Pada'],
        ];
    }

    public function map($item): array
    {
        $sisa = max(0, (int) $item->harga - (int) $item->total_sudah_dibayar);

        return [
            $item->id,
            $item->no_hp ?: '-',
            $item->siswa->name ?? 'Siswa Tidak Ditemukan',
            $item->siswa->kelas ?? '-',
            $item->keterangan ?? '-',
            (int) $item->harga,
            (int) $item->total_sudah_dibayar,
            $sisa,
            self::STATUS_LABELS[(int) $item->status] ?? '-',
            self::METODE_LABELS[$item->pembayaran_via] ?? '-',
            $item->tanggal_pembayaran ? Carbon::parse($item->tanggal_pembayaran)->format('d/m/Y') : '-',
            $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
            ],
        ];
    }
}

class PembayaranDetailCicilanSheet implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    protected Collection $pembayarans;

    protected array $filterSummary;

    public function __construct(Collection $pembayarans, array $filterSummary)
    {
        $this->pembayarans = $pembayarans;
        $this->filterSummary = $filterSummary;
    }

    public function title(): string
    {
        return 'Detail Cicilan';
    }

    public function collection()
    {
        return $this->pembayarans
            ->flatMap(function ($pembayaran) {
                return $pembayaran->details->map(function ($detail) use ($pembayaran) {
                    return [
                        'invoice' => $pembayaran,
                        'detail' => $detail,
                    ];
                });
            })
            ->sortBy(fn ($row) => $row['detail']->created_at)
            ->values();
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PEMBAYARAN - DETAIL CICILAN / SETORAN'],
            [implode(' | ', $this->filterSummary)],
            [''],
            ['ID Invoice', 'No. HP', 'Nama Siswa', 'Nominal Setoran', 'Keterangan Setoran', 'Tanggal Setoran'],
        ];
    }

    public function map($row): array
    {
        $invoice = $row['invoice'];
        $detail = $row['detail'];

        return [
            $invoice->id,
            $invoice->no_hp ?: '-',
            $invoice->siswa->name ?? 'Siswa Tidak Ditemukan',
            (int) $detail->pembayaran,
            $detail->keterangan ?? '-',
            $detail->created_at ? Carbon::parse($detail->created_at)->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F97316']],
            ],
        ];
    }
}
