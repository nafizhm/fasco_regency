<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->date('tgl_booking')->nullable();
            $table->unsignedInteger('id_bank_kpr')->nullable();
        });
        Schema::table('akad', fn (Blueprint $table) => $table->date('tgl_akad')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('customer', fn (Blueprint $table) => $table->dropColumn(['tgl_booking', 'id_bank_kpr']));
    }
};
