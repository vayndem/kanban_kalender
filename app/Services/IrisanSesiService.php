<?php

namespace App\Services;

use App\Models\Sesi;
use Illuminate\Support\Collection;

class IrisanSesiService
{
    /** @var array<int, array<int, int>>|null */
    private ?array $peta = null;

    /** @var Collection<int, Sesi>|null */
    private ?Collection $sesis = null;

    /**
     * Sesi yang waktunya bertindih dengan sesi ini, termasuk dirinya sendiri.
     *
     * @return array<int, int>
     */
    public function idBeririsan(int $sesiId): array
    {
        return $this->peta()[$sesiId] ?? [$sesiId];
    }

    /**
     * Peta lengkap: id sesi => daftar id sesi yang waktunya bertindih (termasuk dirinya).
     *
     * @return array<int, array<int, int>>
     */
    public function peta(): array
    {
        if ($this->peta !== null) {
            return $this->peta;
        }

        $sesis = $this->sesis();
        $peta = [];

        foreach ($sesis as $a) {
            $peta[$a->id] = [$a->id];

            foreach ($sesis as $b) {
                if ($a->id === $b->id) {
                    continue;
                }

                if ($this->bertindih($a, $b)) {
                    $peta[$a->id][] = $b->id;
                }
            }
        }

        return $this->peta = $peta;
    }

    public function bertindih(Sesi $a, Sesi $b): bool
    {
        if ($a->start_time === null || $a->end_time === null || $b->start_time === null || $b->end_time === null) {
            return false;
        }

        return $a->start_time < $b->end_time && $b->start_time < $a->end_time;
    }

    public function kapasitasSlotPerHari(): int
    {
        $sesis = $this->sesis()->sortBy('start_time')->values();
        $dipakai = 0;
        $selesaiTerakhir = null;

        foreach ($sesis as $sesi) {
            if ($sesi->start_time === null || $sesi->end_time === null) {
                continue;
            }

            if ($selesaiTerakhir === null || $sesi->start_time >= $selesaiTerakhir) {
                $dipakai++;
                $selesaiTerakhir = $sesi->end_time;
            }
        }

        return $dipakai;
    }

    /**
     * @return Collection<int, Sesi>
     */
    private function sesis(): Collection
    {
        return $this->sesis ??= Sesi::query()->get(['id', 'name', 'start_time', 'end_time']);
    }
}
