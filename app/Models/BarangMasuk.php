<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk';
    public $timestamps = false;

    protected $fillable = [
        'id_po',
        'id_supplier',
        'tanggal',
        'nama_penerima',
        'total_harga',
        'jum_item',
        'terbayar',
        'status',
    ];

    public function detail()
    {
        return $this->hasMany(BarangMasukDetail::class, 'id_masuk');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function lampiran()
    {
        return $this->hasMany(BarangMasukLampiran::class, 'id_barang_masuk');
    }

    public function pembayaran()
    {
        return $this->hasMany(BarangMasukPembayaran::class, 'id_barang_masuk');
    }
}
