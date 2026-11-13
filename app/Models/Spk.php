<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    protected $table = 'spk';
    protected $guarded = ['id'];
    protected $casts = ['items' => 'array', 'tanggal' => 'date', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function pembayaran()
    {
        return $this->hasMany(SpkPembayaran::class, 'id_spk');
    }

    public function getTerbayarAttribute(): int
    {
        return (int) ($this->pembayaran_sum_nominal ?? $this->pembayaran->sum('nominal'));
    }

    public function getSisaAttribute(): int
    {
        return max(0, (int) $this->nilai_borongan - $this->terbayar);
    }

    public function getStatusPembayaranAttribute(): string
    {
        return $this->sisa === 0 ? 'Lunas' : ($this->terbayar > 0 ? 'Terbayar Sebagian' : 'Belum Dibayar');
    }
}
