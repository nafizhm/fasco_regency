<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('absen_tukang_detail', fn (Blueprint $table) => $table->unsignedBigInteger('tambahan_gaji')->default(0));
    }

    public function down(): void
    {
        Schema::table('absen_tukang_detail', fn (Blueprint $table) => $table->dropColumn('tambahan_gaji'));
    }
};
