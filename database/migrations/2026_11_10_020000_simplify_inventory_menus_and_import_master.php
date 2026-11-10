<?php

use Database\Seeders\InventoryReferenceMasterSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $ids = DB::table('menu')->whereIn('route_name', [
                'master-project.index', 'setting-proyek.index', 'pengajuan-barang.index',
            ])->pluck('id');
            do {
                $children = DB::table('menu')->whereIn('id_parent', $ids)->whereNotIn('id', $ids)->pluck('id');
                $ids = $ids->merge($children)->unique()->values();
            } while ($children->isNotEmpty());
            foreach (['hak_akses', 'role_user'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereIn('id_menu', $ids)->delete();
                }
            }
            DB::table('menu')->whereIn('id', $ids)->delete();
            (new InventoryReferenceMasterSeeder)->run();
        });
    }

    public function down(): void
    {
        // Imported masters may be used by transactions. Retain them on rollback.
    }
};
