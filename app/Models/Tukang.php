<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tukang extends Model
{
    use HasFactory;

    protected $table = 'tukang';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'alamat',
        'no_telp',
    ];
}
