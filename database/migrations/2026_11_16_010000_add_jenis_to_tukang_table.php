<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tukang', 'jenis')) {
            Schema::table('tukang', fn (Blueprint $table) => $table->string('jenis', 20)->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('tukang', fn (Blueprint $table) => $table->dropColumn('jenis'));
    }
};
