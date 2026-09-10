<?php

namespace App\Services;

use App\Models\Diskon;
use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Throwable;

class StrukPembayaranService
{
    /**
     * @param  array<int, int>  $idTerpilih
     */
    public function kumpulkanTagihanLunas(string $noHp, array $idTerpilih, ?string $bulan, ?string $pencarian): Collection
    {
        $query = Pembayaran::with(['siswa', 'details'])
            ->where('no_hp', $noHp)
            ->where('status', 2);

        if ($idTerpilih !== []) {
            $query->whereIn('id', $idTerpilih);
        } else {
            if (filled($bulan) && $bulan !== 'all') {
                $query->whereMonth('created_at', $bulan);
            }

            if (filled($pencarian)) {
                $query->where(function ($q) use ($pencarian) {
                    $q->whereHas('siswa', fn ($s) => $s->where('name', 'like', "%{$pencarian}%"))
                        ->orWhere('keterangan', 'like', "%{$pencarian}%")
                        ->orWhere('no_hp', 'like', "%{$pencarian}%");
                });
            }
        }

        return $query->orderBy('created_at')->get();
    }

    /**
     * @return array{diskon: ?Diskon, diskonUniversal: ?Diskon, totalNominal: int}
     */
    public function diskonUntuk(string $noHp): array
    {
        $diskon = Diskon::where('no_hp', $noHp)->first();
        $diskonUniversal = Diskon::whereNull('no_hp')->first();

        return [
            'diskon' => $diskon,
            'diskonUniversal' => $diskonUniversal,
            'totalNominal' => (int) ($diskon->diskon ?? 0) + (int) ($diskonUniversal->diskon ?? 0),
        ];
    }

    public function logoDataUri(): ?string
    {
        foreach ([storage_path('app/public/Logo.png'), storage_path('app/Logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $binary = @file_get_contents($path);
                if ($binary !== false) {
                    return 'data:image/png;base64,'.base64_encode($binary);
                }
            }
        }

        return null;
    }

    public function render(Collection $pembayarans, string $noHp, array $diskonInfo, ?string $logoDataUri)
    {
        try {
            return $this->keluarkanPdf($pembayarans, $noHp, $diskonInfo, $logoDataUri);
        } catch (Throwable $e) {
            report($e);

            return $this->keluarkanPdf($pembayarans, $noHp, $diskonInfo, null);
        }
    }

    public function opsiRuntimeDompdf(): array
    {
        $baseTmpPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'dompdf';
        $fontPath = $baseTmpPath.DIRECTORY_SEPARATOR.'fonts';

        foreach ([$baseTmpPath, $fontPath] as $path) {
            if (! File::exists($path)) {
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

    private function keluarkanPdf(Collection $pembayarans, string $noHp, array $diskonInfo, ?string $logoDataUri)
    {
        $pdf = Pdf::loadView('pdf.struk', [
            'pembayarans' => $pembayarans,
            'no_hp' => $noHp,
            'diskon' => $diskonInfo['diskon'],
            'diskonUniversal' => $diskonInfo['diskonUniversal'],
            'nominalDiskon' => $diskonInfo['totalNominal'],
            'logoDataUri' => $logoDataUri,
        ])
            ->setOptions($this->opsiRuntimeDompdf())
            ->setPaper([0, 0, 226, 500], 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Struk-'.rawurlencode($noHp).'.pdf"',
        ]);
    }
}
