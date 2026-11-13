<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_masuk', fn (Blueprint $table) => $table->date('jatuh_tempo')->nullable());
    }

    public function down(): void
    {
        Schema::table('barang_masuk', fn (Blueprint $table) => $table->dropColumn('jatuh_tempo'));
    }
};
