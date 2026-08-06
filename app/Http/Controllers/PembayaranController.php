<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use App\Models\Diskon;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $siswa = Siswa::find($request->id_siswa);
            $validated['no_hp'] = $siswa->no_hp;
            $validated['status'] = 0;
            $validated['total_sudah_dibayar'] = 0;

            $pembayaran = Pembayaran::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data pembayaran berhasil dicatat.',
                    'data' => $pembayaran,
                ]);
            }

            return redirect()->back()->with('success', 'Data pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menyimpan', $e);
        }
    }

    public function update(Request $request, $id)
    {
        $pembayaran = Pembayaran::find($id);

        if (!$pembayaran) {
            return $this->handleNotFound($request, "Pembayaran (ID: $id)");
        }

        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1',
        ]);

        try {
            $siswa = Siswa::find($request->id_siswa);
            $validated['no_hp'] = $siswa->no_hp;

            $pembayaran->update($validated);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Data pembayaran diperbarui.']);
            }

            return redirect()->back()->with('success', 'Data pembayaran diperbarui.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memperbarui', $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $pembayaran = Pembayaran::find($id);

            if (!$pembayaran) {
                return $this->handleNotFound($request, "Pembayaran");
            }

            $pembayaran->details()->delete();
            $pembayaran->delete();

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Data pembayaran dihapus.']);
            }

            return redirect()->back()->with('success', 'Data pembayaran dihapus.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal menghapus', $e);
        }
    }

    public function lunasSemua(Request $request)
    {
        try {
            $updatedCount = DB::transaction(function () {
                $pembayarans = Pembayaran::whereIn('status', [0, 1])
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                return $this->settlePembayarans($pembayarans, 'Selesai sistem');
            });

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $updatedCount . ' tagihan telah diselesaikan.']);
            }

            return redirect()->back()->with('success', $updatedCount . ' tagihan telah diselesaikan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses pelunasan massal', $e);
        }
    }

    public function lunasPerSiswa(Request $request, $id_siswa)
    {
        try {
            $siswa = Siswa::find($id_siswa);
            if (!$siswa) {
                return $this->handleNotFound($request, "Siswa");
            }

            Pembayaran::where('no_hp', $siswa->no_hp)
                ->where('status', 0)
                ->update(['status' => 1]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Tagihan siswa berhasil ditagihkan.']);
            }

            return redirect()->back()->with('success', 'Tagihan siswa berhasil ditagihkan.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses tagihan siswa', $e);
        }
    }

    public function bayarPerSiswa(Request $request, $id_siswa)
    {
        $request->validate([
            'nominal' => 'required|integer|min:1',
            'keterangan_detail' => 'nullable|string|max:255',
            'pembayaran_via' => 'required|integer|in:0,1',
            'tanggal_pembayaran' => 'required|date'
        ]);

        try {
            $siswa = Siswa::find($id_siswa);
            if (!$siswa) {
                return $this->handleNotFound($request, "Siswa");
            }

            DB::transaction(function () use ($request, $siswa) {
                $pembayarans = Pembayaran::where('no_hp', $siswa->no_hp)
                    ->whereIn('status', [0, 1])
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                if ($pembayarans->isEmpty()) {
                    throw ValidationException::withMessages(['nominal' => 'Tidak ada tagihan aktif untuk nomor HP ini.']);
                }

                $remaining = (int) $request->nominal;
                $totalOutstanding = $pembayarans->sum(fn ($item) => max(0, (int) $item->harga - (int) $item->total_sudah_dibayar));
                if ($remaining > $totalOutstanding) {
                    throw ValidationException::withMessages([
                        'nominal' => 'Nominal melebihi sisa tagihan sebesar Rp ' . number_format($totalOutstanding, 0, ',', '.') . '.',
                    ]);
                }

                foreach ($pembayarans as $pembayaran) {
                    if ($remaining <= 0) break;

                    $outstanding = max(0, (int) $pembayaran->harga - (int) $pembayaran->total_sudah_dibayar);
                    $allocated = min($remaining, $outstanding);
                    if ($allocated === 0) continue;

                    $detail = PembayaranDetail::create([
                        'id_pembayaran' => $pembayaran->id,
                        'pembayaran' => $allocated,
                        'keterangan' => $request->keterangan_detail ?? 'Pembayaran cicilan / bertahap',
                    ]);
                    $detail->created_at = Carbon::parse($request->tanggal_pembayaran);
                    $detail->save();

                    $newTotal = (int) $pembayaran->total_sudah_dibayar + $allocated;
                    $pembayaran->update([
                        'total_sudah_dibayar' => $newTotal,
                        'status' => $newTotal >= (int) $pembayaran->harga ? 2 : 1,
                        'pembayaran_via' => $request->pembayaran_via,
                        'tanggal_pembayaran' => $request->tanggal_pembayaran,
                    ]);
                    $remaining -= $allocated;
                }
            });

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Pembayaran cicilan berhasil dicatat.']);
            }

            return redirect()->back()->with('success', 'Pembayaran cicilan berhasil dicatat.');
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => implode(' ', $e->validator->errors()->all())], 422);
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal memproses pembayaran', $e);
        }
    }

    public function keLunasMassal(Request $request, $id_siswa)
    {
        try {
            $siswa = Siswa::find($id_siswa);
            if (!$siswa) {
                return $this->handleNotFound($request, "Siswa");
            }

            $updatedCount = DB::transaction(function () use ($siswa) {
                $pembayarans = Pembayaran::where('no_hp', $siswa->no_hp)
                    ->whereIn('status', [0, 1])
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                return $this->settlePembayarans($pembayarans, 'Selesai sistem');
            });

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $updatedCount . ' tagihan berhasil diubah menjadi lunas.']);
            }

            return redirect()->back()->with('success', $updatedCount . ' tagihan berhasil diubah menjadi lunas.');
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal melunaskan', $e);
        }
    }

    public function penagihanMassal(Request $request)
    {
        try {
            $siswas = Siswa::where(function ($q) {
                $q->whereNotNull('paket_pembayaran')
                    ->orWhereNotNull('paket_pembayaran_2')
                    ->orWhereNotNull('paket_pembayaran_3')
                    ->orWhereNotNull('paket_pembayaran_4')
                    ->orWhereNotNull('paket_pembayaran_5');
            })->get();

            $allPakets = \App\Models\Paket::all()->keyBy('id');
            $count = 0;
            $now = Carbon::now();
            $bulanTahun = $now->translatedFormat('F Y');

            foreach ($siswas as $siswa) {
                $columns = ['paket_pembayaran', 'paket_pembayaran_2', 'paket_pembayaran_3', 'paket_pembayaran_4', 'paket_pembayaran_5'];
                foreach ($columns as $col) {
                    if ($siswa->$col && isset($allPakets[$siswa->$col])) {
                        $pkt = $allPakets[$siswa->$col];
                        $keterangan = "Tagihan Paket {$pkt->nama_paket} - {$bulanTahun}";

                        $alreadyExists = Pembayaran::where('id_siswa', $siswa->id)
                            ->where('no_hp', $siswa->no_hp)
                            ->where('keterangan', $keterangan)
                            ->exists();

                        if ($alreadyExists) {
                            continue;
                        }

                        Pembayaran::create([
                            'id_siswa' => $siswa->id,
                            'no_hp' => $siswa->no_hp,
                            'harga' => $pkt->harga,
                            'keterangan' => $keterangan,
                            'status' => 0,
                            'total_sudah_dibayar' => 0
                        ]);
                        $count++;
                    }
                }
            }

            $message = "{$count} Tagihan massal berhasil dibuat.";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return $this->handleException($request, 'Gagal membuat tagihan massal', $e);
        }
    }

    public function printStruk($no_hp)
    {
        $query = Pembayaran::with(['siswa', 'details'])
            ->where('no_hp', $no_hp)
            ->where('status', 2);

        $selectedIds = collect(explode(',', (string) request('ids')))
            ->filter(fn ($id) => ctype_digit(trim($id)))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id', $selectedIds->all());
        } else {
            if (request()->filled('bulan') && request('bulan') !== 'all') {
                $query->whereMonth('created_at', request('bulan'));
            }

            if (request()->filled('search')) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa', function ($s) use ($search) {
                        $s->where('name', 'like', "%$search%");
                    })->orWhere('keterangan', 'like', "%$search%")
                        ->orWhere('no_hp', 'like', "%$search%");
                });
            }
        }

        $pembayarans = $query->orderBy('created_at')->get();
        if ($pembayarans->isEmpty()) {
            abort(404, 'Data lunas tidak ditemukan.');
        }

        $diskon = Diskon::where('no_hp', $no_hp)->first();
        $diskonUniversal = Diskon::whereNull('no_hp')->first();
        $nominalDiskon = $diskon ? (int) $diskon->diskon : 0;
        $nominalDiskonUniversal = $diskonUniversal ? (int) $diskonUniversal->diskon : 0;
        $totalNominalDiskon = $nominalDiskon + $nominalDiskonUniversal;
        $logoPath = storage_path('app/public/Logo.png');
        $logoDataUri = null;

        if (!is_file($logoPath) || !is_readable($logoPath)) {
            $logoPath = storage_path('app/Logo.png');
        }

        if (is_file($logoPath) && is_readable($logoPath)) {
            $binary = @file_get_contents($logoPath);
            if ($binary !== false) {
                $logoDataUri = 'data:image/png;base64,' . base64_encode($binary);
            }
        }

        try {
            return $this->renderStrukPdfResponse(
                $pembayarans,
                $no_hp,
                $diskon,
                $diskonUniversal,
                $totalNominalDiskon,
                $logoDataUri
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->renderStrukPdfResponse(
                $pembayarans,
                $no_hp,
                $diskon,
                $diskonUniversal,
                $totalNominalDiskon,
                null
            );
        }
    }

    public function detailKeluarga(Request $request, $no_hp)
    {
        $selectedIds = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn ($id) => ctype_digit(trim($id)))
            ->map(fn ($id) => (int) $id)
            ->values();

        $query = Pembayaran::select([
            'id',
            'id_siswa',
            'harga',
            'status',
            'keterangan',
            'tanggal_pembayaran',
            'pembayaran_via',
            'no_hp',
            'total_sudah_dibayar',
            'created_at',
        ])->with([
            'siswa:id,name,panggilan,kelas,no_hp',
            'details:id,id_pembayaran,pembayaran,keterangan,created_at',
        ])->where('no_hp', $no_hp);

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id', $selectedIds->all());
        }

        $pembayarans = $query->orderBy('created_at')->get();

        if ($pembayarans->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail keluarga tidak ditemukan.',
            ], 404);
        }

        $rawItems = $pembayarans->map(function ($item) {
            return [
                'id' => $item->id,
                'id_siswa' => $item->id_siswa,
                'siswa' => $item->siswa,
                'harga' => (int) $item->harga,
                'status' => (int) $item->status,
                'keterangan' => $item->keterangan,
                'tanggal_pembayaran' => $item->tanggal_pembayaran
                    ? Carbon::parse($item->tanggal_pembayaran)->translatedFormat('d F Y')
                    : '-',
                'pembayaran_via' => $item->pembayaran_via,
                'no_hp' => $item->no_hp,
                'total_sudah_dibayar' => (int) $item->total_sudah_dibayar,
                'bulan' => $item->created_at?->format('m'),
                'tanggal_format' => $item->created_at?->translatedFormat('d F Y'),
            ];
        })->values();

        $paymentDetails = $pembayarans
            ->flatMap(fn ($item) => $item->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'id_pembayaran' => $detail->id_pembayaran,
                    'pembayaran' => (int) $detail->pembayaran,
                    'keterangan' => $detail->keterangan,
                    'created_at' => $detail->created_at?->toISOString(),
                ];
            }))
            ->sortBy('created_at')
            ->values();

        $diskon = Diskon::where('no_hp', $no_hp)->first();
        $diskonUniversal = Diskon::whereNull('no_hp')->first();
        $nominalDiskonSpesifik = $diskon ? (int) $diskon->diskon : 0;
        $nominalDiskonUniversal = $diskonUniversal ? (int) $diskonUniversal->diskon : 0;
        $totalNominalDiskon = $nominalDiskonSpesifik + $nominalDiskonUniversal;

        $gabunganKetDiskon = collect([
            $diskon?->keterangan,
            $diskonUniversal?->keterangan ? $diskonUniversal->keterangan . ' (Massal)' : null,
        ])->filter()->implode(' + ');

        $totalHarga = (int) $pembayarans->sum('harga');
        $totalSudahDibayar = (int) $pembayarans->sum('total_sudah_dibayar');
        $totalAkhir = max(0, $totalHarga - $totalNominalDiskon);
        $statuses = $pembayarans->pluck('status')->map(fn ($status) => (int) $status);
        $status = $statuses->every(fn ($value) => $value === 2)
            ? 2
            : ($statuses->contains(fn ($value) => in_array($value, [1, 2], true)) ? 1 : 0);

        return response()->json([
            'status' => 'success',
            'data' => [
                'no_hp' => $no_hp,
                'raw_items' => $rawItems,
                'payment_details' => $paymentDetails,
                'total_harga' => $totalHarga,
                'total_sudah_dibayar' => $totalSudahDibayar,
                'nominal_diskon' => $totalNominalDiskon,
                'keterangan_diskon' => $gabunganKetDiskon ?: 'Tanpa Potongan',
                'total_akhir' => $totalAkhir,
                'status' => $status,
            ],
        ]);
    }

    private function settlePembayarans($pembayarans, string $keterangan): int
    {
        $settledAt = Carbon::now();
        $updatedCount = 0;

        foreach ($pembayarans as $pembayaran) {
            $harga = (int) $pembayaran->harga;
            $sudahDibayar = (int) $pembayaran->total_sudah_dibayar;
            $sisa = max(0, $harga - $sudahDibayar);

            if ($sisa > 0) {
                $detail = PembayaranDetail::create([
                    'id_pembayaran' => $pembayaran->id,
                    'pembayaran' => $sisa,
                    'keterangan' => $keterangan,
                ]);
                $detail->created_at = $settledAt;
                $detail->updated_at = $settledAt;
                $detail->save();
            }

            $pembayaran->update([
                'total_sudah_dibayar' => $harga,
                'status' => 2,
                'tanggal_pembayaran' => $settledAt,
                'pembayaran_via' => 0,
            ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    private function renderStrukPdfResponse(
        $pembayarans,
        string $no_hp,
        ?Diskon $diskon,
        ?Diskon $diskonUniversal,
        int $nominalDiskon,
        ?string $logoDataUri
    )
    {
        $pdf = Pdf::loadView('pdf.struk', [
            'pembayarans' => $pembayarans,
            'no_hp' => $no_hp,
            'diskon' => $diskon,
            'diskonUniversal' => $diskonUniversal,
            'nominalDiskon' => $nominalDiskon,
            'logoDataUri' => $logoDataUri,
        ])
            ->setOptions($this->dompdfRuntimeOptions())
            ->setPaper([0, 0, 226, 500], 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Struk-' . rawurlencode($no_hp) . '.pdf"',
        ]);
    }

    private function dompdfRuntimeOptions(): array
    {
        $baseTmpPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dompdf';
        $fontPath = $baseTmpPath . DIRECTORY_SEPARATOR . 'fonts';

        foreach ([$baseTmpPath, $fontPath] as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true, true);
            }
        }

        return [
            'tempDir' => $baseTmpPath,
            'fontDir' => $fontPath,
            'fontCache' => $fontPath,
            'isRemoteEnabled' => false,
            'chroot' => [realpath(base_path()), realpath(storage_path('app'))],
        ];
    }

    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman.";
        return $request->wantsJson()
            ? response()->json(['status' => 'error', 'message' => $msg], 404)
            : redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        $msg = $prefix . ': ' . $e->getMessage();
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }
        return redirect()->back()->withInput()->with('error', $msg);
    }

    public function exportPdf(Request $request)
    {
        $statuses = [
            0 => 'Belum Bayar',
            1 => 'Tertagih',
            2 => 'Lunas'
        ];

        $requestedStatus = $request->filled('status') && $request->status !== 'all'
            ? (int) $request->status
            : null;

        $allData = [];
        $diskons = Diskon::all()->keyBy('no_hp');
        $filterSummary = [];

        if ($request->filled('search')) {
            $filterSummary[] = 'Pencarian: "' . $request->search . '"';
        }

        if ($request->filled('bulan') && $request->bulan !== 'all') {
            $filterSummary[] = 'Bulan: ' . Carbon::create()->month((int) $request->bulan)->translatedFormat('F');
        }

        if ($requestedStatus !== null && isset($statuses[$requestedStatus])) {
            $filterSummary[] = 'Status: ' . $statuses[$requestedStatus];
        }

        foreach ($statuses as $code => $name) {
            if ($requestedStatus !== null && $code !== $requestedStatus) {
                continue;
            }

            $query = Pembayaran::with(['siswa', 'details'])->where('status', $code);

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa', function ($s) use ($search) {
                        $s->where('name', 'like', "%$search%");
                    })->orWhere('keterangan', 'like', "%$search%")
                        ->orWhere('no_hp', 'like', "%$search%");
                });
            }

            if ($request->bulan && $request->bulan !== 'all') {
                $query->whereMonth('created_at', $request->bulan);
            }

            $allData[$name] = [
                'code' => $code,
                'groups' => $query->orderBy('no_hp')->get()->groupBy('no_hp'),
            ];
        }

        $pdf = Pdf::loadView('pdf.pembayaran', [
            'allData' => $allData,
            'bulan' => $request->bulan,
            'diskons' => $diskons,
            'exportedAt' => now()->translatedFormat('d F Y, H:i'),
            'filterSummary' => $filterSummary ?: ['Semua data pembayaran sesuai status yang dipilih.'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-Pembayaran-' . now()->format('YmdHis') . '.pdf');
    }
}
