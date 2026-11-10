<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasukLampiran extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk_lampiran';
    public $timestamps = false;

    protected $fillable = [
        'id_barang_masuk',
        'file_name',
        'file_original_name',
        'file_extension',
        'jenis_lampiran',
    ];
}
