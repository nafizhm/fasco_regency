<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProject extends Model
{
    use HasFactory;

    protected $table = 'master_project';
    public $timestamps = false;

    protected $fillable = [
        'jenis_proyek',
        'jenis_pekerjaan',
    ];

    public function details()
    {
        return $this->hasMany(MasterProjectDetail::class, 'id_master_project');
    }

    public function pekerjaan()
    {
        return $this->hasMany(MasterProjectPekerjaan::class, 'id_master_project');
    }
}
