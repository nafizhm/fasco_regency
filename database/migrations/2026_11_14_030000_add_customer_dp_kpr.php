<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customer', 'pengajuan_hold'] as $table) {
            if (! Schema::hasColumn($table, 'dp_kpr')) {
                Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('dp_kpr')->nullable());
            }
        }
        // Nominal DP historis harus ditentukan admin, bukan ditebak dari harga rumah.
    }

    public function down(): void
    {
        throw new RuntimeException('Nominal DP yang sudah diisi harus dipertahankan.');
    }
};
