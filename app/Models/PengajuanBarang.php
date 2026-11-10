<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanBarang extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_barang';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'id_supplier',
        'total_harga',
        'jum_item',
        'status',
        'keterangan',
    ];

    public function detail()
    {
        return $this->hasMany(PengajuanBarangDetail::class, 'id_pengajuan');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
}
