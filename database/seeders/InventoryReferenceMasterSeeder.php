<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryReferenceMasterSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('inventory_reference_master.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $units = [];
            foreach ($data['satuan'] as $unit) {
                $name = trim($unit['nama_satuan']);
                $id = DB::table('satuan')->where('nama_satuan', $name)->value('id');
                $units[$unit['id']] = $id ?: DB::table('satuan')->insertGetId(['nama_satuan' => $name]);
            }

            foreach ($data['barang'] as $item) {
                // Re-running the import must preserve any stock and transactions already entered here.
                if (DB::table('barang')->where('nama', trim($item['nama']))->exists()) {
                    continue;
                }
                $unit = $units[$item['id_satuan']] ?? null;
                if (! $unit) {
                    throw new \RuntimeException('Satuan barang referensi tidak ditemukan: '.$item['nama']);
                }
                DB::table('barang')->insert([
                    'nama' => trim($item['nama']),
                    'sku' => $item['sku'],
                    'id_satuan' => $unit,
                    'harga_beli' => (int) $item['harga_beli'],
                    'stok_awal' => 0,
                    'stok' => 0,
                    'stok_minimal' => (int) $item['stok_minimal'],
                    'deskripsi' => $item['deskripsi'],
                ]);
            }
        });
    }
}
