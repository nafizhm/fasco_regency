<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'id_barang_masuk',
        'id_barang_keluar',
        'id_realisasi_bahan',
        'id_barang',
        'jumlah',
        'stok_sisa',
    ];
}
