<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara_sp3k', function (Blueprint $table) {
            $table->integer('tenor')->nullable()->change();
            $table->string('no_sp3k')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wawancara_sp3k', function (Blueprint $table) {
            $table->integer('tenor')->nullable(false)->change();
            $table->string('no_sp3k')->nullable(false)->change();
        });
    }
};
