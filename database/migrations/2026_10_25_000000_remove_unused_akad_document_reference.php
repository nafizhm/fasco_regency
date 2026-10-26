<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('detail_akad', 'id_persyaratan')) {
            Schema::table('detail_akad', function (Blueprint $table) {
                $table->dropColumn('id_persyaratan');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('detail_akad', 'id_persyaratan')) {
            Schema::table('detail_akad', function (Blueprint $table) {
                $table->integer('id_persyaratan')->default(0);
            });
        }
    }
};
