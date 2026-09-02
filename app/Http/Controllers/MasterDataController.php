<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $kelasUnik = Jadwal::query()
            ->select('hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id')
            ->distinct()
            ->get()
            ->all();

        return view('admin.master-data', [
            'gurus' => $this->gurusDenganKonteks(),
            'ruangs' => $this->ruangsDenganKonteks(),
            'sesis' => $this->sesisDenganKonteks(),
            'mapels' => $this->mapelsDenganKonteks(),
            'pakets' => $this->paketsDenganKonteks(),
            'ketersediaan' => $this->petaKetersediaan(),
            'ringkasan' => [
                'guru' => Guru::count(),
                'guru_berakun' => User::whereNotNull('guru_id')->count(),
                'guru_tanpa_email' => Guru::whereNull('email')->count(),
                'ruang' => Ruang::count(),
                'sesi' => Sesi::count(),
                'mapel' => MataPelajaran::count(),
                'paket' => Paket::count(),
                'siswa' => Siswa::count(),
                'kelas' => count($kelasUnik),
            ],
        ]);
    }

    public function buatAkunGuru(Request $request, $id)
    {
        $guru = Guru::find($id);

        if (! $guru) {
            return $this->balas($request, 'error', 'Data guru tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('gurus', 'email')->ignore($guru->id), Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($guru->punyaAkun()) {
            return $this->balas($request, 'error', "Guru {$guru->name} sudah punya akun login.", 422);
        }

        try {
            DB::transaction(function () use ($guru, $validated) {
                $guru->update(['email' => $validated['email']]);

                $user = User::create([
                    'name' => $guru->name,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'guru_id' => $guru->id,
                ]);

                $user->assignRole(User::ROLE_GURU);
            });
        } catch (\Throwable $e) {
            return $this->balas($request, 'error', 'Gagal membuat akun: '.$e->getMessage(), 500);
        }

        return $this->balas($request, 'success', "Akun login untuk {$guru->name} berhasil dibuat.");
    }

    public function ubahEmailGuru(Request $request, $id)
    {
        $guru = Guru::with('user')->find($id);

        if (! $guru) {
            return $this->balas($request, 'error', 'Data guru tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('gurus', 'email')->ignore($guru->id)],
        ]);

        $email = $validated['email'] ?? null;

        if ($guru->user && blank($email)) {
            throw ValidationException::withMessages([
                'email' => "Email {$guru->name} tidak bisa dikosongkan karena sudah dipakai untuk login.",
            ]);
        }

        try {
            DB::transaction(function () use ($guru, $email) {
                $guru->update(['email' => $email]);

                if ($guru->user) {
                    $guru->user->update(['email' => $email]);
                }
            });
        } catch (\Throwable $e) {
            return $this->balas($request, 'error', 'Gagal memperbarui email: '.$e->getMessage(), 500);
        }

        return $this->balas($request, 'success', "Email {$guru->name} diperbarui.");
    }

    private function gurusDenganKonteks()
    {
        $pemakaian = Jadwal::query()
            ->select('guru_id', DB::raw('COUNT(*) as baris'))
            ->groupBy('guru_id')
            ->pluck('baris', 'guru_id');

        $kelas = Jadwal::query()
            ->select('guru_id', 'hari_id', 'sesi_id')
            ->distinct()
            ->get()
            ->groupBy('guru_id');

        return Guru::with('user:id,guru_id,email')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (Guru $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'email' => $g->email,
                'punya_akun' => $g->user !== null,
                'siap_dibuatkan_akun' => filled($g->email) && $g->user === null,
                'jumlah_baris_jadwal' => (int) ($pemakaian[$g->id] ?? 0),
                'jumlah_slot' => $kelas->get($g->id)?->count() ?? 0,
            ]);
    }

    private function ruangsDenganKonteks()
    {
        $terpakai = Jadwal::query()
            ->select('ruang_id', 'hari_id', 'sesi_id')
            ->distinct()
            ->get()
            ->groupBy('ruang_id')
            ->map(fn ($rows) => $rows->count());

        $totalSlot = max(1, Hari::count() * Sesi::count());

        return Ruang::orderBy('name')->get(['id', 'name'])->map(fn (Ruang $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'slot_terpakai' => (int) ($terpakai->get($r->id) ?? 0),
            'slot_total' => $totalSlot,
            'slot_kosong' => $totalSlot - (int) ($terpakai->get($r->id) ?? 0),
        ]);
    }

    private function sesisDenganKonteks()
    {
        $terpakai = Jadwal::query()
            ->select('sesi_id', DB::raw('COUNT(*) as baris'))
            ->groupBy('sesi_id')
            ->pluck('baris', 'sesi_id');

        return Sesi::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time'])
            ->map(fn (Sesi $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'jumlah_baris_jadwal' => (int) ($terpakai[$s->id] ?? 0),
            ]);
    }

    private function mapelsDenganKonteks()
    {
        $terpakai = Jadwal::query()
            ->select('mata_pelajaran_id', DB::raw('COUNT(*) as baris'))
            ->groupBy('mata_pelajaran_id')
            ->pluck('baris', 'mata_pelajaran_id');

        return MataPelajaran::orderBy('name')->get(['id', 'name'])
            ->map(fn (MataPelajaran $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'jumlah_baris_jadwal' => (int) ($terpakai[$m->id] ?? 0),
                'bisa_dihapus' => (int) ($terpakai[$m->id] ?? 0) === 0,
            ]);
    }

    private function paketsDenganKonteks()
    {
        $kolom = ['paket_pembayaran', 'paket_pembayaran_2', 'paket_pembayaran_3', 'paket_pembayaran_4', 'paket_pembayaran_5'];
        $pemakai = [];

        foreach ($kolom as $k) {
            foreach (Siswa::whereNotNull($k)->pluck($k) as $paketId) {
                $pemakai[$paketId] = ($pemakai[$paketId] ?? 0) + 1;
            }
        }

        return Paket::orderBy('nama_paket')->get(['id', 'nama_paket', 'harga', 'pertemuan'])
            ->map(fn (Paket $p) => [
                'id' => $p->id,
                'nama_paket' => $p->nama_paket,
                'harga' => (int) $p->harga,
                'pertemuan' => (int) $p->pertemuan,
                'jumlah_siswa' => $pemakai[$p->id] ?? 0,
                'bisa_dihapus' => ($pemakai[$p->id] ?? 0) === 0,
            ]);
    }

    private function petaKetersediaan(): array
    {
        $haris = Hari::orderBy('id')->get(['id', 'name']);
        $sesis = Sesi::orderBy('start_time')->get(['id', 'name']);
        $ruangs = Ruang::orderBy('name')->get(['id', 'name']);
        $gurus = Guru::orderBy('name')->get(['id', 'name']);

        $terisi = Jadwal::query()
            ->select('hari_id', 'sesi_id', 'ruang_id', 'guru_id')
            ->distinct()
            ->get();

        $peta = [];

        foreach ($haris as $hari) {
            foreach ($sesis as $sesi) {
                $diSlot = $terisi->where('hari_id', $hari->id)->where('sesi_id', $sesi->id);

                $ruangTerpakai = $diSlot->pluck('ruang_id')->unique();
                $guruTerpakai = $diSlot->pluck('guru_id')->unique();

                $peta[] = [
                    'hari' => $hari->name,
                    'sesi' => $sesi->name,
                    'kelas_berjalan' => $diSlot->count(),
                    'ruang_kosong' => $ruangs->whereNotIn('id', $ruangTerpakai)->pluck('name')->values(),
                    'guru_kosong' => $gurus->whereNotIn('id', $guruTerpakai)->pluck('name')->values(),
                ];
            }
        }

        return $peta;
    }

    private function balas(Request $request, string $status, string $pesan, int $kode = 200)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => $status, 'message' => $pesan], $status === 'success' ? 200 : $kode);
        }

        return redirect()->back()->with($status, $pesan);
    }
}
