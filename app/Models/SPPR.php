<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SPPR extends Model
{
    protected $table = 'sppr';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_customer',
        'no_sppr',
        'nama',
        'alamat',
        'nik',
        'no_telp',
        'luas_bangunan',
        'luas_tanah',
        'blok',
        'no',
        'harga_jual',
        'asumsi_plafon_kpr',
        'penandatangan',
        'nominal_dp',
    ];

    protected $casts = [
        'luas_bangunan' => 'integer',
        'luas_tanah' => 'integer',
        'harga_jual' => 'integer',
        'asumsi_plafon_kpr' => 'integer',
        'nominal_dp' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

}
