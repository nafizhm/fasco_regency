<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('work_priorities', 'delegated_to')) {
            Schema::table('work_priorities', function (Blueprint $table) {
                $table->string('delegated_to', 200)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('work_priorities', 'delegated_to')) {
            Schema::table('work_priorities', function (Blueprint $table) {
                $table->dropIndex(['delegated_to']);
                $table->dropColumn('delegated_to');
            });
        }
    }
};
