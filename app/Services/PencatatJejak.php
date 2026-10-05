<?php

namespace App\Services;

use App\Models\JejakPerubahan;
use Illuminate\Support\Facades\Auth;

class PencatatJejak
{
    private static bool $aktif = true;

    public static function jeda(): void
    {
        self::$aktif = false;
    }

    public static function lanjut(): void
    {
        self::$aktif = true;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    public function catat(
        string $entitas,
        string $aksi,
        string $ringkasan,
        ?int $entitasId = null,
        array $detail = [],
        ?string $kodeKelas = null
    ): ?JejakPerubahan {
        if (! self::$aktif) {
            return null;
        }

        $pelaku = Auth::user();

        return JejakPerubahan::create([
            'user_id' => $pelaku?->id,
            'nama_pelaku' => $pelaku?->name,
            'entitas' => $entitas,
            'aksi' => $aksi,
            'entitas_id' => $entitasId,
            'kode_kelas' => $kodeKelas,
            'ringkasan' => mb_substr($ringkasan, 0, 500),
            'detail' => $detail === [] ? null : $detail,
        ]);
    }

    /**
     * @param  array<string, mixed>  $sebelum
     * @param  array<string, mixed>  $sesudah
     * @param  array<string, string>  $label
     * @return array<string, array{dari: mixed, jadi: mixed}>
     */
    public function bandingkan(array $sebelum, array $sesudah, array $label = []): array
    {
        $berubah = [];

        foreach ($sesudah as $kolom => $nilaiBaru) {
            $nilaiLama = $sebelum[$kolom] ?? null;

            if ($this->sama($nilaiLama, $nilaiBaru)) {
                continue;
            }

            $berubah[$label[$kolom] ?? $kolom] = [
                'dari' => $nilaiLama,
                'jadi' => $nilaiBaru,
            ];
        }

        return $berubah;
    }

    /**
     * @param  array<string, array{dari: mixed, jadi: mixed}>  $perubahan
     */
    public function ringkasPerubahan(array $perubahan): string
    {
        if ($perubahan === []) {
            return 'Tidak ada nilai yang berubah.';
        }

        return collect($perubahan)
            ->map(fn (array $isi, string $kolom) => sprintf(
                '%s: %s → %s',
                $kolom,
                $this->tampil($isi['dari']),
                $this->tampil($isi['jadi'])
            ))
            ->implode('; ');
    }

    private function sama(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return (string) $a === (string) $b;
    }

    private function tampil(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '(kosong)';
        }

        return (string) $nilai;
    }
}
