<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\BatchSudahDijalankanException;
use App\Exports\PembayaranExport;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use App\Models\Diskon;
use App\Services\PaymentBatchService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class PembayaranController extends Controller
{
    /**
     * Rentang waktu (detik) yang dianggap "klik ganda" untuk pencatatan
     * identik. Cukup lebar menutupi server lambat, cukup sempit agar setoran
     * kedua yang sah beberapa menit kemudian tetap bisa dicatat.
     */
    private const JEDA_ANTI_GANDA = 180;

    public function __construct(private readonly PaymentBatchService $paymentBatchService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'id_paket' => 'nullable|exists:pakets,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $siswa = Siswa::find($request->id_siswa);
            $validated['no_hp'] = $siswa->no_hp;
            $validated['status'] = 0;
            $validated['total_sudah_dibayar'] = 0;

            // Tagihan yang merujuk paket ikut menanam anchor periode, supaya
            // penagihan massal bulan ini mengenalinya dan tidak menagih ulang.
            $validated['periode'] = ! empty($validated['id_paket'])
                ? Carbon::now()->format('Y-m')
                : null;

            if (! empty($validated['id_paket'])) {
                $duplikat = Pembayaran::query()
                    ->where('id_siswa', $validated['id_siswa'])
                    ->where('id_paket', $validated['id_paket'])
                    ->where('periode', $validated['periode'])
                    ->with('paket:id,nama_paket')
                    ->first();

                if ($duplikat) {
                    $namaPaket = $duplikat->paket?->nama_paket ?? 'ini';
                    $periodeLabel = Carbon::createFromFormat('Y-m', $validated['periode'])->translatedFormat('F Y');

                    throw ValidationException::withMessages([
                        'id_paket' => "Siswa {$siswa->name} sudah punya tagihan paket {$namaPaket} untuk periode {$periodeLabel} "
                            . '(dibuat ' . $duplikat->created_at?->translatedFormat('d F Y') . '). '
                            . 'Tagihan ganda dicegah otomatis. Gunakan "Catat Bayar" pada tagihan yang sudah ada, '
                            . 'atau kosongkan pilihan paket bila ini memang tagihan tambahan di luar paket.',
                    ]);
                }
            }

            // Tagihan bebas boleh berulang (buku, denda, kegiatan), jadi tidak
            // bisa dikunci lewat anchor paket. Yang dijaga di sini khusus pola
            // klik ganda: tagihan identik yang dibuat dalam hitungan menit.
            $kembar = Pembayaran::query()
                ->where('id_siswa', $validated['id_siswa'])
                ->where('harga', $validated['harga'])
                ->where('keterangan', $validated['keterangan'] ?? null)
                ->where('created_at', '>=', Carbon::now()->subSeconds(self::JEDA_ANTI_GANDA))
                ->latest('created_at')
                ->first();

            if ($kembar) {
                throw ValidationException::withMessages([
                    'harga' => 'Tagihan dengan nominal dan keterangan yang sama persis baru saja dibuat '
                        . $kembar->created_at?->diffForHumans() . ' untuk siswa ini. '
                        . 'Pembuatan ganda dicegah otomatis. Bila ini memang tagihan kedua yang berbeda, '
                        . 'bedakan keterangannya terlebih dahulu.',
                ]);
            }

            $pembayaran = Pembayaran::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data pembayaran berhasil dicatat.',
                    'data' => $pembayaran,
                ]);
            }

            return redirect()->back()->with('success', 'Data pembayaran berhasil dicatat.');
        } catch (ValidationException $e) {
            return $this->handleValidationException($request, $e);
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
            $updatedCount = $this->paymentBatchService->settleActive();

            $message = $updatedCount === 0
                ? 'Tidak ada tagihan aktif yang perlu diselesaikan.'
                : $updatedCount . ' tagihan telah diselesaikan.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (BatchSudahDijalankanException $e) {
            return $this->handleBatchLocked($request, $e);
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

                $this->tolakBilaPencatatanGanda($request, $siswa);

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

            $updatedCount = $this->paymentBatchService->settleActive($siswa->no_hp);

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
            $count = $this->paymentBatchService->createMonthlyInvoices();

            $message = $count === 0
                ? 'Tidak ada tagihan baru yang perlu dibuat. Semua siswa berpaket sudah tertagih untuk periode ini.'
                : "{$count} Tagihan massal berhasil dibuat.";

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (BatchSudahDijalankanException $e) {
            return $this->handleBatchLocked($request, $e);
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

    /**
     * Menolak pencatatan pembayaran yang identik dan berdekatan waktunya.
     *
     * Server lama (region Amerika) sering lambat merespons, sehingga admin
     * menekan "Catat Bayar" dua kali dan satu setoran tercatat ganda. Overlay
     * di layar sudah mencegah klik kedua, tapi refresh, tombol back, atau
     * pengulangan permintaan oleh jaringan masih bisa lolos -- penjaga inilah
     * yang menutup celah itu.
     *
     * Perbandingan memakai updated_at, bukan created_at, karena created_at pada
     * detail pembayaran sengaja diisi tanggal bayar yang bisa dimundurkan.
     */
    private function tolakBilaPencatatanGanda(Request $request, Siswa $siswa): void
    {
        $nominal = (int) $request->nominal;
        $keterangan = $request->keterangan_detail ?? 'Pembayaran cicilan / bertahap';
        $tanggal = Carbon::parse($request->tanggal_pembayaran)->toDateString();

        $kembar = PembayaranDetail::query()
            ->whereHas('pembayaran', fn ($q) => $q->where('no_hp', $siswa->no_hp))
            ->where('pembayaran', $nominal)
            ->where('keterangan', $keterangan)
            ->whereDate('created_at', $tanggal)
            ->where('updated_at', '>=', Carbon::now()->subSeconds(self::JEDA_ANTI_GANDA))
            ->latest('updated_at')
            ->first();

        if (! $kembar) {
            return;
        }

        throw ValidationException::withMessages([
            'nominal' => 'Pembayaran dengan nominal, tanggal, dan keterangan yang sama persis baru saja dicatat '
                . $kembar->updated_at?->diffForHumans()
                . ' untuk keluarga ini. Pencatatan ganda dicegah otomatis. '
                . 'Periksa dulu "Lihat Detail" untuk memastikan; bila ini memang setoran kedua yang berbeda, '
                . 'ubah keterangannya agar tidak identik.',
        ]);
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

    private function handleValidationException($request, ValidationException $e)
    {
        $msg = implode(' ', $e->validator->errors()->all());

        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $msg], 422);
        }

        return redirect()->back()->withInput()->with('error', $msg);
    }

    private function handleBatchLocked($request, BatchSudahDijalankanException $e)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 409);
        }

        return redirect()->back()->with('error', $e->getMessage());
    }

    public function exportExcel(Request $request)
    {
        $statuses = [
            0 => 'Belum Bayar',
            1 => 'Tertagih',
            2 => 'Lunas'
        ];

        $requestedStatus = $request->filled('status') && $request->status !== 'all'
            ? (int) $request->status
            : null;

        $query = Pembayaran::with(['siswa', 'details'])->orderBy('no_hp')->orderBy('created_at');

        if ($requestedStatus !== null) {
            $query->where('status', $requestedStatus);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($s) use ($search) {
                    $s->where('name', 'like', "%$search%");
                })->orWhere('keterangan', 'like', "%$search%")
                    ->orWhere('no_hp', 'like', "%$search%");
            });
        }

        if ($request->filled('bulan') && $request->bulan !== 'all') {
            $query->whereMonth('created_at', $request->bulan);
        }

        $pembayarans = $query->get();
        $diskons = Diskon::all();

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

        return Excel::download(
            new PembayaranExport($pembayarans, $diskons, $filterSummary ?: ['Semua data pembayaran sesuai status yang dipilih.']),
            'Laporan-Pembayaran-' . now()->format('YmdHis') . '.xlsx'
        );
    }
}
