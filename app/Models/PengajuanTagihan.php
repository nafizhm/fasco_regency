<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanTagihan extends Model
{
    protected $table = 'pengajuan_tagihan';

    protected $guarded = ['id'];

    public const STATUS = ['Draft', 'Diajukan', 'Disetujui', 'Ditolak', 'Dibayar'];

    public const LOKASI = ['fasco_regency' => 'Fasco Regency', 'tegalgondo' => 'Tegalgondo', 'ruko' => 'Ruko', 'bedali' => 'Bedali', 'lumajang' => 'Lumajang'];

    public function detail()
    {
        return $this->hasMany(PengajuanTagihanDetail::class, 'id_pengajuan')->orderBy('id');
    }
}
