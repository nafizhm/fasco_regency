<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeMarketing extends Model
{
    protected $table = 'fee_marketing';
    protected $fillable = ['nomor', 'tanggal', 'jumlah', 'data'];
    protected $casts = ['tanggal' => 'date', 'data' => 'array', 'jumlah' => 'integer'];
}

