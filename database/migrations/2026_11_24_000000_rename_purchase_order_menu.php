<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu')->where('route_name', 'pengajuan-pembelian.index')->update(['title' => 'Purchase Order']);
    }

    public function down(): void
    {
        DB::table('menu')->where('route_name', 'pengajuan-pembelian.index')->update(['title' => 'Pengajuan Pembelian']);
    }
};
