<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_masuk_lampiran', function (Blueprint $table) {
            $table->string('jenis_lampiran', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('barang_masuk_lampiran', fn (Blueprint $table) => $table->dropColumn('jenis_lampiran'));
    }
};
