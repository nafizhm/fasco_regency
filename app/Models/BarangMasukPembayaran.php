<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasukPembayaran extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk_pembayaran';
    public $timestamps = false;

    protected $fillable = [
        'id_barang_masuk',
        'tanggal_pembayaran',
        'jumlah_bayar',
        'keterangan',
        'bukti_bayar',
        'bukti_bayar_original_name',
        'bukti_bayar_extension',
    ];

    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasuk::class, 'id_barang_masuk');
    }
}
