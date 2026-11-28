<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_ipl')) {
            Schema::create('invoice_ipl', function (Blueprint $t) {
                $t->id();
                $t->string('nomor', 100)->unique();
                $t->string('nama');
                $t->text('alamat');
                $t->date('tanggal_stu');
                $t->unsignedBigInteger('nominal_tahun');
                $t->date('jatuh_tempo');
                $t->string('nama_rekening');
                $t->string('bank', 100);
                $t->string('no_rekening', 100);
                $t->string('penandatangan');
                $t->string('jabatan');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('invoice_ipl_pembayaran')) {
            Schema::create('invoice_ipl_pembayaran', function (Blueprint $t) {
                $t->id();
                $t->foreignId('id_invoice')->constrained('invoice_ipl')->cascadeOnDelete();
                $t->date('tanggal');
                $t->unsignedBigInteger('nominal');
                $t->string('transaksi', 50);
                $t->string('keterangan', 1000)->nullable();
            });
        }
        $menu = DB::table('menu')->where('route_name', 'invoice-ipl.index')->value('id');
        if (! $menu) {
            $menu = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Invoice IPL', 'route_name' => 'invoice-ipl.index', 'icon' => 'fas fa-file-invoice',
                'urutan' => (DB::table('menu')->max('urutan') ?? 0) + 1, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
        }
        foreach (DB::table('users')->whereRaw('LOWER(username) = ?', ['nita'])->pluck('id') as $user) {
            DB::table('hak_akses')->updateOrInsert(['id_user' => $user, 'id_menu' => $menu], ['lihat' => 1, 'beranda' => 0, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'invoice-ipl.index')->pluck('id');
        DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
        DB::table('role_user')->whereIn('id_menu', $ids)->delete();
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('invoice_ipl_pembayaran');
        Schema::dropIfExists('invoice_ipl');
    }
};
