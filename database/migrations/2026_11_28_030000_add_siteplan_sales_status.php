<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('kavling_peta', 'siteplan_status')) {
            Schema::table('kavling_peta', fn (Blueprint $t) => $t->string('siteplan_status', 10)->nullable());
        }
        if (!Schema::hasTable('siteplan_sales_colors')) {
            Schema::create('siteplan_sales_colors', function (Blueprint $t) {
                $t->string('status', 10)->primary();
                $t->string('color', 7);
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('siteplan_sales_colors');
        Schema::table('kavling_peta', fn (Blueprint $t) => $t->dropColumn('siteplan_status'));
    }
};
