<?php

namespace App\Services;

use App\Exceptions\BatchSudahDijalankanException;
use App\Models\BatchPembayaranLog;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentBatchService
{
    private const PACKAGE_COLUMNS = [
        'paket_pembayaran',
        'paket_pembayaran_2',
        'paket_pembayaran_3',
        'paket_pembayaran_4',
        'paket_pembayaran_5',
    ];

    /**
     * Membuat tagihan bulanan untuk seluruh siswa yang punya paket aktif.
     *
     * Duplikat dicegah lewat anchor (id_siswa, id_paket, periode) -- bukan lagi
     * kecocokan teks keterangan. Ini yang dulu bikin tagihan manual dan tagihan
     * massal untuk paket yang sama tidak saling terdeteksi, sehingga satu siswa
     * bisa tertagih dua kali dalam satu bulan.
     */
    public function createMonthlyInvoices(): int
    {
        $packages = Paket::query()
            ->select(['id', 'nama_paket', 'harga'])
            ->get()
            ->keyBy('id');

        if ($packages->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $periode = $now->format('Y-m');
        $label = $now->translatedFormat('F Y');

        return $this->runOncePerPeriod(
            BatchPembayaranLog::JENIS_PENAGIHAN,
            $periode,
            function () use ($packages, $periode, $label, $now) {
                $createdCount = 0;

                Siswa::query()
                    ->select(array_merge(['id', 'no_hp'], self::PACKAGE_COLUMNS))
                    ->where(function ($query) {
                        foreach (self::PACKAGE_COLUMNS as $index => $column) {
                            $index === 0
                                ? $query->whereNotNull($column)
                                : $query->orWhereNotNull($column);
                        }
                    })
                    ->chunkById(500, function ($students) use ($packages, $periode, $label, $now, &$createdCount) {
                        $existingKeys = $this->existingAnchorKeys($students->pluck('id'), $periode);

                        $rows = [];
                        foreach ($students as $student) {
                            foreach (self::PACKAGE_COLUMNS as $column) {
                                $package = $packages->get($student->{$column});
                                if (! $package) {
                                    continue;
                                }

                                $key = $this->anchorKey($student->id, $package->id, $periode);
                                if ($existingKeys->has($key)) {
                                    continue;
                                }

                                $rows[] = [
                                    'id_siswa' => $student->id,
                                    'id_paket' => $package->id,
                                    'periode' => $periode,
                                    'no_hp' => $student->no_hp,
                                    'harga' => $package->harga,
                                    'keterangan' => "Tagihan Paket {$package->nama_paket} - {$label}",
                                    'status' => 0,
                                    'total_sudah_dibayar' => 0,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                                $existingKeys->put($key, true);
                            }
                        }

                        if ($rows !== []) {
                            Pembayaran::query()->insert($rows);
                            $createdCount += count($rows);
                        }
                    });

                return $createdCount;
            }
        );
    }

    /**
     * Daftar tagihan paket yang belum dibuat untuk periode berjalan.
     * Memakai anchor yang sama dengan createMonthlyInvoices supaya preview dan
     * eksekusi tidak pernah berbeda hasil.
     */
    public function previewMissingInvoices(): Collection
    {
        $packages = Paket::query()
            ->select(['id', 'nama_paket', 'harga'])
            ->get()
            ->keyBy('id');

        if ($packages->isEmpty()) {
            return collect();
        }

        $periode = Carbon::now()->format('Y-m');

        $students = Siswa::query()
            ->select(array_merge(['id', 'name', 'no_hp'], self::PACKAGE_COLUMNS))
            ->where(function ($query) {
                foreach (self::PACKAGE_COLUMNS as $index => $column) {
                    $index === 0
                        ? $query->whereNotNull($column)
                        : $query->orWhereNotNull($column);
                }
            })
            ->get();

        $existingKeys = $this->existingAnchorKeys($students->pluck('id'), $periode);

        $missing = collect();
        foreach ($students as $student) {
            foreach (self::PACKAGE_COLUMNS as $column) {
                $package = $packages->get($student->{$column});
                if (! $package) {
                    continue;
                }

                if ($existingKeys->has($this->anchorKey($student->id, $package->id, $periode))) {
                    continue;
                }

                $missing->push([
                    'siswa_id' => $student->id,
                    'siswa_name' => $student->name,
                    'no_hp' => $student->no_hp,
                    'paket' => $package->nama_paket,
                    'harga' => $package->harga,
                ]);
            }
        }

        return $missing->values();
    }

    /**
     * Menutup tagihan aktif menjadi lunas, sambil tetap meninggalkan jejak
     * PembayaranDetail untuk sisa yang ditutup sistem.
     *
     * Tanpa $phone berarti "tutup buku" seluruh sistem -- dikunci sekali per
     * bulan. Dengan $phone berarti pelunasan satu keluarga, yang memang aksi
     * harian dan tidak dikunci.
     */
    public function settleActive(?string $phone = null, string $description = 'Selesai sistem'): int
    {
        if ($phone !== null) {
            return $this->performSettlement($phone, $description);
        }

        return $this->runOncePerPeriod(
            BatchPembayaranLog::JENIS_PELUNASAN,
            Carbon::now()->format('Y-m'),
            fn () => $this->performSettlement(null, $description)
        );
    }

    /**
     * Log eksekusi massal terakhir per jenis, untuk ditampilkan di UI supaya
     * admin tahu status periode berjalan sebelum menekan tombol.
     */
    public function currentPeriodStatus(): array
    {
        $periode = Carbon::now()->format('Y-m');

        $logs = BatchPembayaranLog::query()
            ->with('user:id,name')
            ->where('periode', $periode)
            ->get()
            ->keyBy('jenis');

        return [
            'periode' => $periode,
            'penagihan_massal' => $this->describeLog($logs->get(BatchPembayaranLog::JENIS_PENAGIHAN)),
            'pelunasan_massal' => $this->describeLog($logs->get(BatchPembayaranLog::JENIS_PELUNASAN)),
        ];
    }

    private function describeLog(?BatchPembayaranLog $log): ?array
    {
        if (! $log) {
            return null;
        }

        return [
            'dijalankan_pada' => $log->created_at?->translatedFormat('d F Y, H:i'),
            'oleh' => $log->user?->name,
            'jumlah_diproses' => $log->jumlah_diproses,
        ];
    }

    private function performSettlement(?string $phone, string $description): int
    {
        return DB::transaction(function () use ($phone, $description) {
            $query = Pembayaran::query()
                ->select(['id', 'harga', 'total_sudah_dibayar'])
                ->whereIn('status', [0, 1])
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate();

            if ($phone !== null) {
                $query->where('no_hp', $phone);
            }

            $payments = $query->get();
            if ($payments->isEmpty()) {
                return 0;
            }

            $settledAt = Carbon::now();
            $details = $payments
                ->map(function (Pembayaran $payment) use ($description, $settledAt) {
                    $remaining = max(0, (int) $payment->harga - (int) $payment->total_sudah_dibayar);

                    return $remaining > 0 ? [
                        'id_pembayaran' => $payment->id,
                        'pembayaran' => $remaining,
                        'keterangan' => $description,
                        'created_at' => $settledAt,
                        'updated_at' => $settledAt,
                    ] : null;
                })
                ->filter()
                ->values()
                ->all();

            if ($details !== []) {
                DB::table('pembayaran_details')->insert($details);
            }

            Pembayaran::query()
                ->whereIn('id', $payments->pluck('id'))
                ->update([
                    'total_sudah_dibayar' => DB::raw('harga'),
                    'status' => 2,
                    'tanggal_pembayaran' => $settledAt->toDateString(),
                    'pembayaran_via' => 0,
                    'updated_at' => $settledAt,
                ]);

            return $payments->count();
        });
    }

    /**
     * Menjalankan aksi massal maksimal sekali per periode.
     *
     * Kunci diambil dengan menulis baris log lebih dulu; UNIQUE(jenis, periode)
     * di database yang menjadi penjaminnya, sehingga dua klik bersamaan tidak
     * bisa dua-duanya lolos. Kalau aksinya ternyata tidak memproses apa pun,
     * kunci dilepas kembali supaya admin masih bisa mengulang setelah
     * memperbaiki data paket.
     */
    private function runOncePerPeriod(string $jenis, string $periode, callable $action): int
    {
        $log = $this->acquirePeriodLock($jenis, $periode);

        try {
            $count = $action();
        } catch (\Throwable $e) {
            $log->delete();
            throw $e;
        }

        if ($count === 0) {
            $log->delete();

            return 0;
        }

        $log->update(['jumlah_diproses' => $count]);

        return $count;
    }

    private function acquirePeriodLock(string $jenis, string $periode): BatchPembayaranLog
    {
        try {
            return BatchPembayaranLog::create([
                'jenis' => $jenis,
                'periode' => $periode,
                'jumlah_diproses' => 0,
                'user_id' => Auth::id(),
            ]);
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $existing = BatchPembayaranLog::query()
                ->with('user:id,name')
                ->where('jenis', $jenis)
                ->where('periode', $periode)
                ->firstOrFail();

            throw new BatchSudahDijalankanException($existing);
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || str_contains(strtolower($e->getMessage()), 'unique');
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @return Collection<string, bool>
     */
    private function existingAnchorKeys($studentIds, string $periode)
    {
        return Pembayaran::query()
            ->select(['id_siswa', 'id_paket'])
            ->whereIn('id_siswa', $studentIds)
            ->whereNotNull('id_paket')
            ->where('periode', $periode)
            ->get()
            ->mapWithKeys(fn (Pembayaran $payment) => [
                $this->anchorKey($payment->id_siswa, $payment->id_paket, $periode) => true,
            ]);
    }

    private function anchorKey(int $studentId, int $packageId, string $periode): string
    {
        return $studentId.'|'.$packageId.'|'.$periode;
    }
}
