<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProjectPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'master_project_pekerjaan';
    public $timestamps = false;

    protected $fillable = [
        'id_master_project',
        'nama_pekerjaan',
    ];

    public function project()
    {
        return $this->belongsTo(MasterProject::class, 'id_master_project');
    }

    public function details()
    {
        return $this->hasMany(MasterProjectDetail::class, 'id_master_project_pekerjaan');
    }
}
