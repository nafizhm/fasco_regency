<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $ready = DB::table('progres_list_penjualan')
                ->whereRaw('LOWER(TRIM(status_progres)) IN (?, ?)', ['ready', 'unit ready'])->first();
            if (!$ready) return;

            $hold = DB::table('progres_list_penjualan')
                ->whereRaw('LOWER(TRIM(status_progres)) = ?', ['hold'])->first();
            $position = (int) $ready->urutan + 1;
            $rows = DB::table('progres_list_penjualan')->where('urutan', '>=', $position);
            if ($hold) $rows->where('id', '!=', $hold->id);
            if (!$hold || (int) $hold->urutan !== $position) $rows->increment('urutan');

            if ($hold) {
                DB::table('progres_list_penjualan')->where('id', $hold->id)->update([
                    'urutan' => $position, 'stt_tampil' => 1,
                ]);
            } else {
                DB::table('progres_list_penjualan')->insert([
                    'status_progres' => 'Hold',
                    'urutan' => $position,
                    'keterangan' => 'Unit dalam proses hold',
                    'warna' => '#ffc107',
                    'short_name' => 'HOLD',
                    'stt_tampil' => 1,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve status records that may already be referenced by units or customers.
    }
};
