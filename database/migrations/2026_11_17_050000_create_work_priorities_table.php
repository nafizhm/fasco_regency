<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('proses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_priorities');
    }
};
