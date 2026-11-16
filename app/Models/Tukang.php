<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tukang extends Model
{
    use HasFactory;

    public const JENIS = ['Mandor', 'Tukang', 'Kuli', 'Harian', 'Borongan'];

    protected $table = 'tukang';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'alamat',
        'no_telp',
        'jenis',
        'gaji_per_hari',
    ];
}
