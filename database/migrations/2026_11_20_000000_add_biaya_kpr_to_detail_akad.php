<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_akad', function (Blueprint $table) {
            $table->decimal('biaya_kpr', 18, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_akad', function (Blueprint $table) {
            $table->dropColumn('biaya_kpr');
        });
    }
};
