<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\BatchSudahDijalankanException;
use Illuminate\Validation\ValidationException;

/**
 * Penerjemah kegagalan menjadi respons untuk layar pembayaran.
 *
 * Dipindahkan apa adanya dari PembayaranController: bentuk balasan, kode status,
 * dan bunyi pesannya sengaja tidak diubah, karena layar sudah bergantung padanya.
 */
trait MerespondKesalahanPembayaran
{
    private function handleNotFound($request, $item)
    {
        $msg = "Maaf, data $item tidak ditemukan. Silakan segarkan halaman.";

        return $request->wantsJson()
            ? response()->json(['status' => 'error', 'message' => $msg], 404)
            : redirect()->back()->with('error', $msg);
    }

    private function handleException($request, $prefix, $e)
    {
        $msg = $prefix.': '.$e->getMessage();
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
}
