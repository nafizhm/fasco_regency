<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpkPembayaran extends Model
{
    protected $table = 'spk_pembayaran';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date'];
}
