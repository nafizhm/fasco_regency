<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu')->where('route_name', 'fee-marketing.index')->update(['id_parent' => 0]);
    }

    public function down(): void
    {
        $parent = DB::table('menu')->where('route_name', 'pemasukan.index')->value('id_parent');
        if ($parent !== null) {
            DB::table('menu')->where('route_name', 'fee-marketing.index')->update(['id_parent' => $parent]);
        }
    }
};
