<?php

namespace App\Imports;

use App\Models\Paket;
use App\Models\Siswa;
use App\Models\TingkatKemampuan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaMassalImport implements ToCollection, WithHeadingRow
{
    private const KOLOM_PAKET = [
        'nama_paket' => 'paket_pembayaran',
        'nama_paket_2' => 'paket_pembayaran_2',
        'nama_paket_3' => 'paket_pembayaran_3',
        'nama_paket_4' => 'paket_pembayaran_4',
        'nama_paket_5' => 'paket_pembayaran_5',
    ];

    public int $dibuat = 0;

    public int $diperbarui = 0;

    public int $dilewati = 0;

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $nama = trim((string) ($row['nama_lengkap'] ?? ''));

                if ($nama === '') {
                    $this->dilewati++;

                    continue;
                }

                $data = array_filter([
                    'panggilan' => $this->nilai($row['panggilan'] ?? null),
                    'kelas' => $this->nilai($row['kelas'] ?? null),
                    'no_hp' => $this->nilai($row['no_hp'] ?? null),
                ], fn ($v) => $v !== null);

                foreach (self::KOLOM_PAKET as $kolomBerkas => $kolomTabel) {
                    $namaPaket = trim((string) ($row[$kolomBerkas] ?? ''));
                    if ($namaPaket === '') {
                        continue;
                    }

                    $paket = Paket::where('nama_paket', $namaPaket)->first();
                    if ($paket) {
                        $data[$kolomTabel] = $paket->id;
                    }
                }

                $level = $this->nilai($row['kemampuan'] ?? null);
                if ($level !== null && is_numeric($level)) {
                    $kemampuan = TingkatKemampuan::where('level', (int) $level)->first();
                    if ($kemampuan) {
                        $data['tingkat_kemampuan_id'] = $kemampuan->id;
                    }
                }

                $siswa = Siswa::where('name', $nama)->first();

                if ($siswa) {
                    $siswa->update($data);
                    $this->diperbarui++;
                } else {
                    Siswa::create(array_merge(['name' => $nama], $data));
                    $this->dibuat++;
                }
            }
        });
    }

    private function nilai($mentah): ?string
    {
        $bersih = trim((string) ($mentah ?? ''));

        return $bersih === '' ? null : $bersih;
    }
}
