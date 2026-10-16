<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara_sp3k', function (Blueprint $table) {
            $table->string('dp_mode', 20)->nullable();
            $table->decimal('dp_persen', 7, 4)->nullable();
            $table->unsignedBigInteger('dp_nilai')->nullable();
            $table->unsignedBigInteger('dp_dasar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wawancara_sp3k', fn (Blueprint $table) => $table->dropColumn(['dp_mode', 'dp_persen', 'dp_nilai', 'dp_dasar']));
    }
};
