<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryBarang extends Model
{
    protected $table = 'history_barang';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'id_barang',
        'id_barang_masuk',
        'id_barang_keluar',
        'id_mutasi_stok',
        'tipe',
        'masuk',
        'keluar',
        'stok',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
