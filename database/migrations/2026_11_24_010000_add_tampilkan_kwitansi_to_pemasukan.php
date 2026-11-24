<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pemasukan', 'tampilkan_kwitansi')) {
            Schema::table('pemasukan', fn (Blueprint $table) => $table->boolean('tampilkan_kwitansi')->default(true));
        }
    }

    public function down(): void
    {
        Schema::table('pemasukan', fn (Blueprint $table) => $table->dropColumn('tampilkan_kwitansi'));
    }
};
