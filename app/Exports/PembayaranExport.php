<?php

namespace App\Exports;

use App\Models\Pembayaran;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PembayaranExport implements
    WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];
        $statuses = [
            0 => 'Belum Bayar',
            1 => 'Tertagih',
            2 => 'Lunas'
        ];

        foreach ($statuses as $code => $name) {
            $sheets[] = new PembayaranStatusSheet($this->request, $code, $name);
        }

        return $sheets;
    }
}

class PembayaranStatusSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    protected $request;
    protected $statusCode;
    protected $statusName;

    public function __construct($request, $statusCode, $statusName)
    {
        $this->request = $request;
        $this->statusCode = $statusCode;
        $this->statusName = $statusName;
    }

    public function title(): string
    {
        return $this->statusName;
    }

    public function collection()
    {
        $query = Pembayaran::with(['siswa'])->where('status', $this->statusCode);

        if ($this->request->search) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($s) use ($search) {
                    $s->where('name', 'like', "%$search%");
                })->orWhere('keterangan', 'like', "%$search%");
            });
        }

        if ($this->request->bulan && $this->request->bulan !== 'all') {
            $query->whereMonth('created_at', $this->request->bulan);
        }

        $data = $query->orderBy('id_siswa')->get();

        if ($data->isEmpty()) {
            return collect();
        }

        return $data->groupBy('id_siswa');
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PEMBAYARAN - ' . strtoupper($this->statusName)],
            ['PERIODE: ' . ($this->request->bulan == 'all' ? 'SEMUA BULAN' : strtoupper($this->request->bulan))],
            [''],
            ['NAMA SISWA', 'KETERANGAN', 'NOMINAL', 'METODE', 'TANGGAL']
        ];
    }

    public function map($group): array
    {
        $rows = [];
        foreach ($group as $data) {
            $rows[] = [
                $data->siswa->name ?? 'Siswa Tidak Ditemukan',
                $data->keterangan ?? '-',
                'Rp ' . number_format($data->harga ?? 0, 0, ',', '.'),
                $data->pembayaran_via == 1 ? 'Transfer' : ($data->pembayaran_via === 0 ? 'Cash' : '-'),
                $data->created_at ? $data->created_at->format('d/m/Y') : '-'
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        $color = $this->statusCode == 0 ? 'EF4444' : ($this->statusCode == 1 ? 'F97316' : '10B981');

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color]
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $dataGroups = $this->collection();
                $startRow = 5;

                foreach ($dataGroups as $group) {
                    $rowCount = $group->count();
                    $endRow = $startRow + $rowCount - 1;
                    if ($rowCount > 1) {
                        $sheet->mergeCells("A{$startRow}:A{$endRow}");
                    }
                    $startRow = $endRow + 1;
                }

                $lastRow = max(4, $startRow - 1);
                $sheet->getStyle("A4:E{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
