<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spk', function (Blueprint $table) {
            $table->timestamp('dibatalkan_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('spk', fn (Blueprint $table) => $table->dropColumn('dibatalkan_pada'));
    }
};
