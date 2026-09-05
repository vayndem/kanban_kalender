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

class JadwalExport implements WithMultipleSheets
{
    protected Collection $jadwals;

    protected ?string $search;

    public function __construct(Collection $jadwals, ?string $search = null)
    {
        $this->jadwals = $jadwals;
        $this->search = $search;
    }

    public function sheets(): array
    {
        return [
            new JadwalDetailSheet($this->jadwals, $this->search),
            new JadwalCatatanSheet($this->jadwals),
        ];
    }
}

class JadwalDetailSheet implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    protected Collection $jadwals;

    protected ?string $search;

    public function __construct(Collection $jadwals, ?string $search)
    {
        $this->jadwals = $jadwals;
        $this->search = $search;
    }

    public function title(): string
    {
        return 'Jadwal Lengkap';
    }

    public function collection()
    {
        return $this->jadwals->sortBy([['hari_id', 'asc'], ['sesi_id', 'asc']])->values();
    }

    public function headings(): array
    {
        return [
            'Hari',
            'Sesi',
            'Jam Mulai',
            'Jam Selesai',
            'Mata Pelajaran',
            'Guru',
            'Ruang',
            'Nama Siswa',
            'Panggilan',
            'Kelas',
            'No. HP Siswa',
            'Jumlah Catatan',
        ];
    }

    public function map($jadwal): array
    {
        return [
            $jadwal->hari?->name ?? '-',
            $jadwal->sesi?->name ?? '-',
            $jadwal->sesi?->start_time ? Carbon::parse($jadwal->sesi->start_time)->format('H:i') : '-',
            $jadwal->sesi?->end_time ? Carbon::parse($jadwal->sesi->end_time)->format('H:i') : '-',
            $jadwal->mataPelajaran?->name ?? '-',
            $jadwal->guru?->name ?? '-',
            $jadwal->ruang?->name ?? '-',
            $jadwal->siswa?->name ?? '-',
            $jadwal->siswa?->panggilan ?? '-',
            $jadwal->siswa?->kelas ?? '-',
            $jadwal->siswa?->no_hp ?? '-',
            $jadwal->siswa?->tandas?->count() ?? 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            ],
        ];
    }
}

class JadwalCatatanSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $jadwals;

    public function __construct(Collection $jadwals)
    {
        $this->jadwals = $jadwals;
    }

    public function title(): string
    {
        return 'Catatan Siswa';
    }

    public function collection()
    {
        $rows = collect();

        $this->jadwals->pluck('siswa')->filter()->unique('id')->each(function ($siswa) use ($rows) {
            foreach ($siswa->tandas ?? [] as $tanda) {
                $rows->push([
                    'siswa' => $siswa,
                    'tanda' => $tanda,
                ]);
            }
        });

        return $rows;
    }

    public function headings(): array
    {
        return ['Nama Siswa', 'Kelas', 'Catatan', 'Tanggal Catatan'];
    }

    public function map($row): array
    {
        return [
            $row['siswa']->name,
            $row['siswa']->kelas ?? '-',
            $row['tanda']->keterangan,
            $row['tanda']->created_at ? Carbon::parse($row['tanda']->created_at)->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F97316']],
            ],
        ];
    }
}
