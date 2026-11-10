<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    public $timestamps = false;

    protected $fillable = [
        'sku',
        'nama',
        'id_satuan',
        'id_supplier',
        'stok_awal',
        'stok_minimal',
        'stok',
        'harga_beli',
        'deskripsi',
        'harga_jual',
    ];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan');
    }

    public function history()
    {
        return $this->hasMany(HistoryBarang::class, 'id_barang');
    }
}
