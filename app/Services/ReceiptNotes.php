<?php

namespace App\Services;

use App\Models\Customer;

class ReceiptNotes
{
    public const DEFAULT_TEXT = "Pembeli telah melakukan survey/ cek lokasi secara langsung dan mendapat penjelasan yang cukup tentang ukuran tanah/bangunan serta skema pembayaran.\nIkatan Tanda Jadi Sebesar Rp. 11.000.000 (Biaya Admin Rp. 1.000.000)\nApabila mengudurkan diri secara sepihak uang tanda jadi tidak dapat diminta kembali.\nApabila transaksi berlanjut maka uang tanda jadi nya Rp. 10.000.000 diperhitungkan sebagai bagian dari uang muka\nUntuk skema KPR : Sudah termasuk Biaya Realisasi KPR, Biaya Pajak/BPHTB ( free Smartdoorlock, Kanopi ,keramik carport, pompa & tandon air)\nBiaya Kelebihan Tanah (Jika ada) adalah sebesar Rp. 4.000.000/m2";

    public static function lines(Customer $customer): array
    {
        $text = $customer->catatan_kwitansi ?? self::DEFAULT_TEXT;

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($line) => $line !== ''));
    }
}
