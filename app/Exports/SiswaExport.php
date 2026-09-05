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
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaExport implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    protected Collection $siswas;

    protected string $filterLabel;

    public function __construct(Collection $siswas, string $filterLabel = 'Semua Siswa')
    {
        $this->siswas = $siswas;
        $this->filterLabel = $filterLabel;
    }

    public function title(): string
    {
        return 'Data Siswa';
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->siswas as $siswa) {
            if ($siswa->jadwals->isEmpty()) {
                $rows->push(['siswa' => $siswa, 'jadwal' => null]);

                continue;
            }

            foreach ($siswa->jadwals as $jadwal) {
                $rows->push(['siswa' => $siswa, 'jadwal' => $jadwal]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['DATA MASTER SISWA - E-LING COURSE'],
            ['FILTER: '.strtoupper($this->filterLabel)],
            [''],
            [
                'Nama Siswa',
                'Panggilan',
                'Kelas',
                'No. HP',
                'Paket',
                'Pertemuan/Periode',
                'Hari',
                'Sesi',
                'Jam Mulai',
                'Jam Selesai',
                'Mata Pelajaran',
                'Guru',
                'Ruang',
            ],
        ];
    }

    public function map($row): array
    {
        $siswa = $row['siswa'];
        $jadwal = $row['jadwal'];

        return [
            $siswa->name,
            $siswa->panggilan ?? '-',
            $siswa->kelas ?? '-',
            $siswa->no_hp ?? '-',
            $siswa->paket?->nama_paket ?? '-',
            $siswa->paket?->pertemuan ?? '-',
            $jadwal?->hari?->name ?? '-',
            $jadwal?->sesi?->name ?? '-',
            $jadwal?->sesi?->start_time ? Carbon::parse($jadwal->sesi->start_time)->format('H:i') : '-',
            $jadwal?->sesi?->end_time ? Carbon::parse($jadwal->sesi->end_time)->format('H:i') : '-',
            $jadwal?->mataPelajaran?->name ?? '-',
            $jadwal?->guru?->name ?? '-',
            $jadwal?->ruang?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 10]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            ],
        ];
    }
}
