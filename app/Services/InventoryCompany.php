<?php

namespace App\Services;

use App\Models\Perusahaan;

class InventoryCompany
{
    public function details(?int $locationId = null): array
    {
        $companyId = $locationId ? \App\Models\LokasiKavlingPerusahaan::where('id_lokasi', $locationId)->value('id_perusahaan') : null;
        $company = $companyId ? Perusahaan::find($companyId) : null;
        $company ??= Perusahaan::orderBy('id')->first();

        return [
            'nama_perusahaan' => $company?->nama_perusahaan ?: config('app.name'),
            'alamat' => $company?->alamat_perusahaan ?: '',
            'telp' => $company?->telp_perusahaan ?: '',
            'kota' => $company?->kota_penandatangan ?: '',
            'nama_penandatangan' => $company?->nama_penandatangan ?: '',
            'jabatan_penandatangan' => $company?->jabatan_penandatangan ?: '',
        ];
    }
}
