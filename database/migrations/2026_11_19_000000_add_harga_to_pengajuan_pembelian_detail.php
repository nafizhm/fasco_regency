<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pembelian_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('harga')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pembelian_detail', fn (Blueprint $table) => $table->dropColumn('harga'));
    }
};
