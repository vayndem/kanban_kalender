<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AkunGuruController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.akun-guru', [
            'gurus' => $this->gurusDenganKonteks(),
            'ringkasan' => [
                'guru' => Guru::count(),
                'guru_berakun' => User::whereNotNull('guru_id')->count(),
                'guru_tanpa_email' => Guru::whereNull('email')->count(),
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
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('gurus', 'email')->ignore($guru->id),
                Rule::unique('users', 'email')->ignore($guru->user?->id),
            ],
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
        $jadwalPerGuru = Jadwal::query()
            ->select('guru_id', 'hari_id', 'sesi_id')
            ->get()
            ->groupBy('guru_id');

        return Guru::with('user:id,guru_id,email')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function (Guru $g) use ($jadwalPerGuru) {
                $rows = $jadwalPerGuru->get($g->id);

                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'email' => $g->email,
                    'punya_akun' => $g->user !== null,
                    'siap_dibuatkan_akun' => filled($g->email) && $g->user === null,
                    'jumlah_baris_jadwal' => $rows?->count() ?? 0,
                    'jumlah_slot' => $rows?->unique(fn ($r) => "{$r->hari_id}_{$r->sesi_id}")->count() ?? 0,
                ];
            });
    }

    private function balas(Request $request, string $status, string $pesan, int $kode = 200)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => $status, 'message' => $pesan], $status === 'success' ? 200 : $kode);
        }

        return redirect()->back()->with($status, $pesan);
    }
}
