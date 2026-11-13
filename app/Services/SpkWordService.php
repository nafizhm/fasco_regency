<?php

namespace App\Services;

use App\Models\Spk;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class SpkWordService
{
    public function generate(Spk $spk): string
    {
        Settings::setOutputEscapingEnabled(true);
        $template = new TemplateProcessor(resource_path('documents/spk-template.docx'));
        $money = fn ($value) => number_format((int) $value, 0, ',', '.');
        $values = [];
        foreach (['nomor', 'nama_perusahaan', 'alamat_perusahaan', 'telp_perusahaan', 'penandatangan', 'kota',
            'nama_pemborong', 'jabatan_pemborong', 'alamat_pemborong', 'nama_proyek', 'lokasi_proyek',
            'jenis_pekerjaan', 'lama_pengerjaan', 'skema_pembayaran'] as $field) $values[$field] = $spk->$field;
        $values['tanggal'] = $spk->tanggal->locale('id')->translatedFormat('d F Y');
        $values['waktu_pelaksanaan'] = $spk->tanggal_mulai->locale('id')->translatedFormat('d F Y')
            .($spk->tanggal_selesai ? ' s/d '.$spk->tanggal_selesai->locale('id')->translatedFormat('d F Y') : '');
        $values['nilai_borongan'] = $money($spk->nilai_borongan);
        $values['total_rincian'] = $money($spk->total_rincian);
        $template->setValues($values);
        $rows = [];
        foreach ($spk->items as $i => $item) {
            $rows[] = ['no_item' => $i + 1, 'item' => $item['item'], 'volume' => number_format((float) $item['volume'], 3, ',', '.'),
                'satuan' => $item['satuan'], 'harga_satuan' => $money($item['harga_satuan']), 'total_item' => $money($item['total'])];
        }
        $template->cloneRowAndSetValues('no_item', $rows);
        $directory = storage_path('app/private/spk');
        if (! is_dir($directory)) mkdir($directory, 0755, true);
        $path = tempnam($directory, 'spk_');
        try {
            $template->saveAs($path);
        } catch (\Throwable $error) {
            @unlink($path);
            throw $error;
        }

        return $path;
    }
}
