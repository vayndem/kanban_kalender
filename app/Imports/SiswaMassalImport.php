<?php

namespace App\Imports;

use App\Models\Paket;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaMassalImport implements ToCollection, WithHeadingRow
{
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

                $namaPaket = trim((string) ($row['nama_paket'] ?? ''));
                if ($namaPaket !== '') {
                    $paket = Paket::where('nama_paket', $namaPaket)->first();
                    if ($paket) {
                        $data['paket_pembayaran'] = $paket->id;
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
