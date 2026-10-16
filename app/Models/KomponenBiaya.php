<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomponenBiaya extends Model
{
    protected static function booted(): void
    {
        static::saving(function ($item) {
            if ($item->exists && !$item->isDirty('nama')) return;
            $item->nama = \App\Services\KavlingCostNames::canonical($item->nama);
            $duplicates = static::query()->when($item->exists, fn ($q) => $q->where('id', '!=', $item->id))->pluck('nama');
            foreach ($duplicates as $name) {
                if (\App\Services\KavlingCostNames::key($name) === \App\Services\KavlingCostNames::key($item->nama)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['nama' => 'Komponen biaya dengan nama tersebut sudah tersedia.']);
                }
            }
        });
    }
    protected $table = 'komponen_biaya';

    protected $fillable = [
        'kode_unik',
        'nama',
        'deskripsi',
        'urutan',
        'wajib',
        'aktif',
        'satuan',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeUrut($query)
    {
        return $query->orderBy('urutan');
    }
}
