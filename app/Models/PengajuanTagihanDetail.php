<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanTagihanDetail extends Model
{
    protected $table = 'pengajuan_tagihan_detail';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanTagihan::class, 'id_pengajuan');
    }

    public function getTotalAttribute(): int
    {
        return array_sum(array_map(fn ($key) => (int) $this->$key, array_keys(PengajuanTagihan::LOKASI)));
    }
}
