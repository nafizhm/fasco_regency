<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingProyek extends Model
{
    use HasFactory;

    protected $table = 'setting_proyek';
    public $timestamps = false;

    protected $fillable = [
        'id_lokasi',
        'id_kavling',
        'id_master_project',
        'id_tukang',
        'status',
    ];

    public function lokasi()
    {
        return $this->belongsTo(LokasiKavling::class, 'id_lokasi', 'id');
    }

    public function kavling()
    {
        return $this->belongsTo(KavlingPeta::class, 'id_kavling', 'id');
    }

    public function masterProject()
    {
        return $this->belongsTo(MasterProject::class, 'id_master_project');
    }

    public function tukang()
    {
        return $this->belongsTo(Tukang::class, 'id_tukang');
    }

}
