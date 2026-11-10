<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProjectDetail extends Model
{
    use HasFactory;

    protected $table = 'master_project_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_master_project',
        'id_master_project_pekerjaan',
        'tipe',
        'id_barang',
        'nama_item',
        'satuan',
        'qty',
        'harga_satuan',
        'sub_total',
    ];

    public function project()
    {
        return $this->belongsTo(MasterProject::class, 'id_master_project');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function pekerjaan()
    {
        return $this->belongsTo(MasterProjectPekerjaan::class, 'id_master_project_pekerjaan');
    }
}
