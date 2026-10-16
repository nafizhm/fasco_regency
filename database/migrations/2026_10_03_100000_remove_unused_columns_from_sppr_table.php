<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $obsolete = [
            'biaya_surat_surat',
            'peningkatan_mutu',
            'biaya_kelebihan_tanah',
            'biaya_sudut',
            'biaya_lain_lain',
            'total_yang_harus_dibayar',
            'jumlah_booking_fee',
            'cicilan_per_bulan',
            'id_marketing',
            'keterangan',
            'agama',
            'pekerjaan',
            'promo',
            'perubahan_posisi',
            'keterangan_booking',
            'keterangan_dp',
            'nominal_biaya_posisi_unit',
            'keterangan_posisi_unit',
            'nominal_biaya_kpr',
            'keterangan_kpr',
            'nominal_blokir_angsuran',
            'keterangan_blokir_angsuran',
            'nominal_biaya_materai',
            'keterangan_materai',
            'nominal_biaya_buka_tabungan',
            'keterangan_tabungan',
            'keterangan_shm',
        ];
        $columns = array_values(array_intersect($obsolete, Schema::getColumnListing('sppr')));
        if ($columns !== []) {
            Schema::table('sppr', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        // Restore the legacy schema only; deleted column values cannot be recovered.
        Schema::table('sppr', function (Blueprint $table) {
            $table->bigInteger('biaya_surat_surat')->default(0);
            $table->bigInteger('peningkatan_mutu')->default(0);
            $table->bigInteger('biaya_kelebihan_tanah')->nullable();
            $table->bigInteger('biaya_sudut')->nullable();
            $table->bigInteger('biaya_lain_lain')->nullable();
            $table->bigInteger('total_yang_harus_dibayar')->default(0);
            $table->bigInteger('jumlah_booking_fee')->default(0);
            $table->bigInteger('cicilan_per_bulan')->default(0);
            $table->integer('id_marketing')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->text('promo')->nullable();
            $table->text('perubahan_posisi')->nullable();
            $table->string('keterangan_booking', 100)->nullable();
            $table->string('keterangan_dp', 100)->nullable();
            $table->bigInteger('nominal_biaya_posisi_unit')->nullable();
            $table->string('keterangan_posisi_unit', 100)->nullable();
            $table->bigInteger('nominal_biaya_kpr')->nullable();
            $table->string('keterangan_kpr', 100)->nullable();
            $table->bigInteger('nominal_blokir_angsuran')->nullable();
            $table->string('keterangan_blokir_angsuran', 100)->nullable();
            $table->bigInteger('nominal_biaya_materai')->nullable();
            $table->string('keterangan_materai', 100)->nullable();
            $table->bigInteger('nominal_biaya_buka_tabungan')->nullable();
            $table->string('keterangan_tabungan', 100)->nullable();
            $table->string('keterangan_shm', 100)->nullable();
        });
    }
};
