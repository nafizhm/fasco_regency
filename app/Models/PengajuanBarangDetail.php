<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanBarangDetail extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_barang_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_pengajuan',
        'id_barang',
        'jumlah',
        'harga_beli',
        'sub_total',
        'keterangan',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanBarang::class, 'id_pengajuan');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
