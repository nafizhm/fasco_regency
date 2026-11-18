<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanPembelianDetail extends Model
{
    protected $table = 'pengajuan_pembelian_detail';

    public $timestamps = false;

    protected $fillable = ['id_barang', 'jumlah', 'harga'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
