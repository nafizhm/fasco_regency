<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pemasukan', fn (Blueprint $table) => $table->decimal('persentase_termin', 5, 2)->nullable());
    }

    public function down(): void
    {
        Schema::table('pemasukan', fn (Blueprint $table) => $table->dropColumn('persentase_termin'));
    }
};
