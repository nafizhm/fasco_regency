<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarangKeluarDetail extends Model
{
    use HasFactory;

    protected $table = 'barang_keluar_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_barang_keluar',
        'id_barang',
        'id_master_project_pekerjaan',
        'jumlah',
        'keterangan',
    ];

    public function pekerjaan()
    {
        return $this->belongsTo(MasterProjectPekerjaan::class, 'id_master_project_pekerjaan');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
