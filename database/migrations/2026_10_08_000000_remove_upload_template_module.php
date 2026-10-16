<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu')) {
            $menuIds = DB::table('menu')
                ->where('route_name', 'upload-template.index')
                ->orWhere('title', 'Upload Template')
                ->pluck('id');

            foreach (['hak_akses', 'role_user'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereIn('id_menu', $menuIds)->delete();
                }
            }

            DB::table('menu')->whereIn('id', $menuIds)->delete();
        }

        Schema::dropIfExists('document_templates');
    }

    public function down(): void
    {
        // Removed template data cannot be restored by a rollback.
    }
};
