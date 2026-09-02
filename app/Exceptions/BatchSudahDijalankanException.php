<?php

namespace App\Exceptions;

use App\Models\BatchPembayaranLog;
use Carbon\Carbon;
use RuntimeException;

/**
 * Dilempar ketika aksi massal (penagihan / pelunasan) sudah pernah dijalankan
 * pada periode yang sama. Membawa detail log lama supaya pesan ke admin bisa
 * menyebut tanggal dan pelakunya, bukan sekadar "tidak boleh".
 */
class BatchSudahDijalankanException extends RuntimeException
{
    public function __construct(public readonly BatchPembayaranLog $log)
    {
        parent::__construct($this->buildMessage());
    }

    private function buildMessage(): string
    {
        $tanggal = $this->log->created_at
            ? Carbon::parse($this->log->created_at)->translatedFormat('d F Y, H:i')
            : 'waktu tidak tercatat';

        $oleh = $this->log->user?->name ?? 'pengguna tidak tercatat';
        $periode = Carbon::createFromFormat('Y-m', $this->log->periode)->translatedFormat('F Y');

        return sprintf(
            '%s untuk periode %s sudah dijalankan pada %s oleh %s (%d tagihan diproses). '
            .'Aksi ini hanya boleh sekali per bulan untuk mencegah tagihan ganda.',
            $this->log->jenis_label,
            $periode,
            $tanggal,
            $oleh,
            $this->log->jumlah_diproses
        );
    }
}
