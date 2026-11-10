<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogHargaBarang extends Model
{
    protected $table = 'log_harga_barang';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'id_barang_masuk',
        'id_user',
        'proses',
        'stok_sebelum',
        'harga_sebelum',
        'qty_lama',
        'harga_lama',
        'qty_baru',
        'harga_baru_input',
        'stok_sesudah',
        'harga_sesudah',
        'keterangan',
        'created_at',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
