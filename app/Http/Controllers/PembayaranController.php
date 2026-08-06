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
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswas,id',
            'harga' => 'required|integer',
            'keterangan' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1,2',
        ]);

        try {
            $siswa = Siswa::find($request->id_siswa);
            $validated['no_hp'] = $siswa->no_hp;
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
            'status' => 'nullable|integer|in:0,1,2',
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
                        Pembayaran::create([
                            'id_siswa' => $siswa->id,
                            'no_hp' => $siswa->no_hp,
                            'harga' => $pkt->harga,
                            'keterangan' => "Tagihan Paket {$pkt->nama_paket} - {$bulanTahun}",
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
        $pembayarans = Pembayaran::with(['siswa', 'details'])->where('no_hp', $no_hp)->where('status', 2)->get();
        if ($pembayarans->isEmpty()) {
            abort(404, 'Data lunas tidak ditemukan.');
        }

        $diskon = Diskon::where('no_hp', $no_hp)->first();
        $nominalDiskon = $diskon ? (int) $diskon->diskon : 0;
        $logoPath = storage_path('app/Logo.png');
        $logoDataUri = null;

        if (!is_file($logoPath) || !is_readable($logoPath)) {
            $logoPath = storage_path('app/public/Logo.png');
        }

        if (is_file($logoPath) && is_readable($logoPath)) {
            $binary = @file_get_contents($logoPath);
            if ($binary !== false) {
                $logoDataUri = 'data:image/png;base64,' . base64_encode($binary);
            }
        }

        $pdf = Pdf::loadView('pdf.struk', [
            'pembayarans' => $pembayarans,
            'no_hp' => $no_hp,
            'diskon' => $diskon,
            'nominalDiskon' => $nominalDiskon,
            'logoDataUri' => $logoDataUri,
        ])->setPaper([0, 0, 226, 500], 'portrait');

        return $pdf->stream('Struk-' . $no_hp . '.pdf');
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

        $allData = [];
        $diskons = Diskon::all()->keyBy('no_hp');

        foreach ($statuses as $code => $name) {
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
                'groups' => $query->orderBy('no_hp')->get()->groupBy('no_hp')
            ];
        }

        $pdf = Pdf::loadView('pdf.pembayaran', [
            'allData' => $allData,
            'bulan' => $request->bulan,
            'diskons' => $diskons
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-Pembayaran-' . now()->format('YmdHis') . '.pdf');
    }
}
