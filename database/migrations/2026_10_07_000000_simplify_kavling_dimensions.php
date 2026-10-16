<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $oldColumns = array_values(array_filter(
            ['panjang_kanan', 'panjang_kiri', 'lebar_depan', 'lebar_belakang'],
            fn ($column) => Schema::hasColumn('kavling_peta', $column)
        ));
        if ($oldColumns) {
            // Preserve the original dimensions before removing the side columns.
            $backup = DB::table('kavling_peta')->select(array_merge(['id'], $oldColumns))->get();
            $path = 'backups/kavling_dimensions_' . now()->format('Ymd_His') . '.json';
            if (!Storage::disk('local')->put($path, $backup->toJson(JSON_PRETTY_PRINT))) {
                throw new RuntimeException('Gagal menyimpan backup ukuran kavling.');
            }
        }
        foreach (['panjang_kanan' => 'panjang', 'lebar_depan' => 'lebar'] as $old => $new) {
            if (Schema::hasColumn('kavling_peta', $old) && !Schema::hasColumn('kavling_peta', $new)) {
                Schema::table('kavling_peta', fn (Blueprint $table) => $table->renameColumn($old, $new));
            }
        }
        $unused = array_values(array_filter(
            ['panjang_kiri', 'lebar_belakang'],
            fn ($column) => Schema::hasColumn('kavling_peta', $column)
        ));
        if ($unused) {
            Schema::table('kavling_peta', fn (Blueprint $table) => $table->dropColumn($unused));
        }
    }

    public function down(): void
    {
        Schema::table('kavling_peta', function (Blueprint $table) {
            $table->renameColumn('panjang', 'panjang_kanan');
            $table->renameColumn('lebar', 'lebar_depan');
            $table->double('panjang_kiri', 11, 1)->nullable();
            $table->double('lebar_belakang', 11, 1)->nullable();
        });
        DB::table('kavling_peta')->update([
            'panjang_kiri' => DB::raw('panjang_kanan'),
            'lebar_belakang' => DB::raw('lebar_depan'),
        ]);
    }
};
