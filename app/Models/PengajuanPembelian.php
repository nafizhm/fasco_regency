<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanPembelian extends Model
{
    protected $table = 'pengajuan_pembelian';

    public $timestamps = false;

    protected $fillable = ['nomor', 'tanggal', 'id_supplier', 'keterangan'];

    public function detail()
    {
        return $this->hasMany(PengajuanPembelianDetail::class, 'id_pengajuan');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function penerimaan()
    {
        return $this->hasMany(BarangMasuk::class, 'id_pengajuan_pembelian');
    }
}
