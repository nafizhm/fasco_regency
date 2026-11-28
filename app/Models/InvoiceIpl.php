<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceIpl extends Model
{
    public const PERUSAHAAN = [
        'nama_rekening' => 'Laji Siswanto',
        'bank' => 'BCA',
        'no_rekening' => '3681655211',
        'penandatangan' => 'Marianita',
        'jabatan' => 'Staff Administrasi',
    ];

    protected $table = 'invoice_ipl';

    protected $guarded = ['id'];

    public function pembayaran()
    {
        return $this->hasMany(InvoiceIplPembayaran::class, 'id_invoice')->orderBy('tanggal')->orderBy('id');
    }

    public function getTotalPembayaranAttribute(): int
    {
        return (int) $this->pembayaran->sum('nominal');
    }

    public function getSisaAttribute(): int
    {
        return max(0, (int) $this->nominal_tahun - $this->total_pembayaran);
    }

    public function getStatusAttribute(): string
    {
        return $this->sisa === 0 ? 'Lunas' : ($this->total_pembayaran > 0 ? 'Sebagian' : 'Belum Dibayar');
    }
}
