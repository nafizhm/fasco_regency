<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapKpr extends Model
{
    protected $table = 'rekap_kpr';

    protected $guarded = ['id'];

    protected $casts = ['tandai_merah' => 'boolean'];

    public const BANK = ['BRI', 'BNI', 'MANDIRI', 'BSN'];
}
