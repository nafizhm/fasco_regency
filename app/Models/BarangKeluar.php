<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'barang_keluar';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'id_project',
        'id_lokasi',
        'id_kavling',
        'id_tukang',
        'keterangan',
        'lampiran',
    ];

    public function detail()
    {
        return $this->hasMany(BarangKeluarDetail::class, 'id_barang_keluar');
    }

    public function lokasi()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi', 'id');
    }

    public function kavling()
    {
        return $this->belongsTo(KavlingPeta::class, 'id_kavling', 'id');
    }

    public function tukang()
    {
        return $this->belongsTo(Tukang::class, 'id_tukang');
    }

    public function masterProject()
    {
        return $this->belongsTo(MasterProject::class, 'id_project');
    }
}
