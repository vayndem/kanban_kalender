<?php

namespace App\Services;

use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Carbon\Carbon;
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
        $period = $now->translatedFormat('F Y');
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
            ->chunkById(500, function ($students) use ($packages, $period, $now, &$createdCount) {
                DB::transaction(function () use ($students, $packages, $period, $now, &$createdCount) {
                    $descriptions = $packages
                        ->map(fn (Paket $package) => "Tagihan Paket {$package->nama_paket} - {$period}")
                        ->unique()
                        ->values();

                    $existingKeys = Pembayaran::query()
                        ->select(['id_siswa', 'no_hp', 'keterangan'])
                        ->whereIn('id_siswa', $students->pluck('id'))
                        ->whereIn('keterangan', $descriptions)
                        ->get()
                        ->mapWithKeys(fn (Pembayaran $payment) => [
                            $this->invoiceKey($payment->id_siswa, $payment->no_hp, $payment->keterangan) => true,
                        ]);

                    $rows = [];
                    foreach ($students as $student) {
                        foreach (self::PACKAGE_COLUMNS as $column) {
                            $package = $packages->get($student->{$column});
                            if (! $package) {
                                continue;
                            }

                            $description = "Tagihan Paket {$package->nama_paket} - {$period}";
                            $key = $this->invoiceKey($student->id, $student->no_hp, $description);
                            if ($existingKeys->has($key)) {
                                continue;
                            }

                            $rows[] = [
                                'id_siswa' => $student->id,
                                'no_hp' => $student->no_hp,
                                'harga' => $package->harga,
                                'keterangan' => $description,
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
            });

        return $createdCount;
    }

    public function settleActive(?string $phone = null, string $description = 'Selesai sistem'): int
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

    private function invoiceKey(int $studentId, ?string $phone, string $description): string
    {
        return $studentId.'|'.$phone.'|'.$description;
    }
}
