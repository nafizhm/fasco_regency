<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hutang', fn (Blueprint $table) => $table->string('transaksi_pembayaran', 20)->default('transfer'));
    }

    public function down(): void
    {
        Schema::table('hutang', fn (Blueprint $table) => $table->dropColumn('transaksi_pembayaran'));
    }
};
