<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_hold', fn (Blueprint $table) => $table->unsignedBigInteger('diskon')->default(0));
    }

    public function down(): void
    {
        Schema::table('pengajuan_hold', fn (Blueprint $table) => $table->dropColumn('diskon'));
    }
};
