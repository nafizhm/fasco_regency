<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $restoreOpeningStock = Schema::hasTable('barang') && Schema::hasColumn('barang', 'stok')
            && ! Schema::hasColumn('barang', 'stok_awal');
        $this->ensure('satuan', [
            'nama_satuan' => fn (Blueprint $t) => $t->string('nama_satuan')->nullable(),
        ]);
        $this->ensure('supplier', [
            'kode' => fn (Blueprint $t) => $t->string('kode', 50)->nullable(),
            'nama' => fn (Blueprint $t) => $t->string('nama')->nullable(),
            'no_telp' => fn (Blueprint $t) => $t->string('no_telp', 50)->nullable(),
            'alamat' => fn (Blueprint $t) => $t->text('alamat')->nullable(),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
            'status' => fn (Blueprint $t) => $t->unsignedTinyInteger('status')->default(1),
        ]);
        $this->ensure('barang', [
            'sku' => fn (Blueprint $t) => $t->string('sku', 100)->nullable(),
            'nama' => fn (Blueprint $t) => $t->string('nama')->nullable(),
            'id_satuan' => fn (Blueprint $t) => $t->unsignedBigInteger('id_satuan')->nullable()->index(),
            'stok_awal' => fn (Blueprint $t) => $t->integer('stok_awal')->default(0),
            'stok_minimal' => fn (Blueprint $t) => $t->integer('stok_minimal')->default(0),
            'stok' => fn (Blueprint $t) => $t->integer('stok')->default(0),
            'harga_beli' => fn (Blueprint $t) => $t->unsignedBigInteger('harga_beli')->default(0),
            'deskripsi' => fn (Blueprint $t) => $t->text('deskripsi')->nullable(),
        ]);
        if ($restoreOpeningStock) {
            DB::table('barang')->update(['stok_awal' => DB::raw('stok')]);
        }
        $this->ensure('barang_masuk', [
            'id_po' => fn (Blueprint $t) => $t->unsignedBigInteger('id_po')->nullable(),
            'id_supplier' => fn (Blueprint $t) => $t->unsignedBigInteger('id_supplier')->nullable()->index(),
            'tanggal' => fn (Blueprint $t) => $t->date('tanggal')->nullable(),
            'nama_penerima' => fn (Blueprint $t) => $t->string('nama_penerima')->nullable(),
            'total_harga' => fn (Blueprint $t) => $t->unsignedBigInteger('total_harga')->default(0),
            'jum_item' => fn (Blueprint $t) => $t->unsignedInteger('jum_item')->default(0),
            'terbayar' => fn (Blueprint $t) => $t->unsignedBigInteger('terbayar')->default(0),
            'status' => fn (Blueprint $t) => $t->unsignedTinyInteger('status')->default(0),
        ]);
        $this->ensure('barang_masuk_detail', [
            'id_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_masuk')->nullable()->index(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'jumlah' => fn (Blueprint $t) => $t->unsignedInteger('jumlah')->default(0),
            'harga_beli' => fn (Blueprint $t) => $t->unsignedBigInteger('harga_beli')->default(0),
            'sub_total' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_total')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
        ]);
        $this->ensure('barang_masuk_lampiran', [
            'id_barang_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_masuk')->nullable()->index(),
            'file_name' => fn (Blueprint $t) => $t->string('file_name')->nullable(),
            'file_original_name' => fn (Blueprint $t) => $t->string('file_original_name')->nullable(),
            'file_extension' => fn (Blueprint $t) => $t->string('file_extension', 10)->nullable(),
        ]);
        $this->ensure('barang_masuk_pembayaran', [
            'id_barang_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_masuk')->nullable()->index(),
            'tanggal_pembayaran' => fn (Blueprint $t) => $t->date('tanggal_pembayaran')->nullable(),
            'jumlah_bayar' => fn (Blueprint $t) => $t->unsignedBigInteger('jumlah_bayar')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
            'bukti_bayar' => fn (Blueprint $t) => $t->string('bukti_bayar')->nullable(),
            'bukti_bayar_original_name' => fn (Blueprint $t) => $t->string('bukti_bayar_original_name')->nullable(),
            'bukti_bayar_extension' => fn (Blueprint $t) => $t->string('bukti_bayar_extension', 10)->nullable(),
        ]);
        $this->ensure('tukang', [
            'nama' => fn (Blueprint $t) => $t->string('nama')->nullable(),
            'alamat' => fn (Blueprint $t) => $t->text('alamat')->nullable(),
            'no_telp' => fn (Blueprint $t) => $t->string('no_telp', 50)->nullable(),
        ]);
        $this->ensure('master_project', [
            'jenis_proyek' => fn (Blueprint $t) => $t->string('jenis_proyek')->nullable(),
            'jenis_pekerjaan' => fn (Blueprint $t) => $t->string('jenis_pekerjaan', 30)->default('pembangunan_unit'),
        ]);
        $this->ensure('master_project_pekerjaan', [
            'id_master_project' => fn (Blueprint $t) => $t->unsignedBigInteger('id_master_project')->nullable()->index(),
            'nama_pekerjaan' => fn (Blueprint $t) => $t->string('nama_pekerjaan')->nullable(),
        ]);
        $this->ensure('master_project_detail', [
            'id_master_project' => fn (Blueprint $t) => $t->unsignedBigInteger('id_master_project')->nullable()->index(),
            'id_master_project_pekerjaan' => fn (Blueprint $t) => $t->unsignedBigInteger('id_master_project_pekerjaan')->nullable()->index(),
            'tipe' => fn (Blueprint $t) => $t->string('tipe', 20)->nullable(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'nama_item' => fn (Blueprint $t) => $t->string('nama_item')->nullable(),
            'satuan' => fn (Blueprint $t) => $t->string('satuan')->nullable(),
            'qty' => fn (Blueprint $t) => $t->decimal('qty', 18, 2)->default(0),
            'harga_satuan' => fn (Blueprint $t) => $t->unsignedBigInteger('harga_satuan')->default(0),
            'sub_total' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_total')->default(0),
        ]);
        $this->ensure('setting_proyek', [
            'id_lokasi' => fn (Blueprint $t) => $t->unsignedBigInteger('id_lokasi')->nullable()->index(),
            'id_kavling' => fn (Blueprint $t) => $t->unsignedBigInteger('id_kavling')->nullable()->unique(),
            'id_master_project' => fn (Blueprint $t) => $t->unsignedBigInteger('id_master_project')->nullable()->index(),
            'id_tukang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_tukang')->nullable()->index(),
            'status' => fn (Blueprint $t) => $t->unsignedTinyInteger('status')->default(1),
        ]);
        $this->ensure('barang_keluar', [
            'tanggal' => fn (Blueprint $t) => $t->date('tanggal')->nullable(),
            'id_project' => fn (Blueprint $t) => $t->unsignedBigInteger('id_project')->nullable()->index(),
            'id_lokasi' => fn (Blueprint $t) => $t->unsignedBigInteger('id_lokasi')->nullable()->index(),
            'id_kavling' => fn (Blueprint $t) => $t->unsignedBigInteger('id_kavling')->nullable()->index(),
            'id_tukang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_tukang')->nullable()->index(),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
            'lampiran' => fn (Blueprint $t) => $t->string('lampiran')->default(''),
        ]);
        $this->ensure('barang_keluar_detail', [
            'id_barang_keluar' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_keluar')->nullable()->index(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'id_master_project_pekerjaan' => fn (Blueprint $t) => $t->unsignedBigInteger('id_master_project_pekerjaan')->nullable()->index(),
            'jumlah' => fn (Blueprint $t) => $t->unsignedInteger('jumlah')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
        ]);
        $this->ensure('mutasi_stok', [
            'tanggal' => fn (Blueprint $t) => $t->date('tanggal')->nullable(),
            'id_barang_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_masuk')->nullable()->index(),
            'id_barang_keluar' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_keluar')->nullable()->index(),
            'id_realisasi_bahan' => fn (Blueprint $t) => $t->unsignedBigInteger('id_realisasi_bahan')->nullable(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'jumlah' => fn (Blueprint $t) => $t->integer('jumlah')->default(0),
            'stok_sisa' => fn (Blueprint $t) => $t->integer('stok_sisa')->default(0),
        ]);
        $this->ensure('history_barang', [
            'tanggal' => fn (Blueprint $t) => $t->date('tanggal')->nullable(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'id_barang_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_masuk')->nullable()->index(),
            'id_barang_keluar' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_keluar')->nullable()->index(),
            'id_mutasi_stok' => fn (Blueprint $t) => $t->unsignedBigInteger('id_mutasi_stok')->nullable()->index(),
            'tipe' => fn (Blueprint $t) => $t->string('tipe', 20)->nullable(),
            'masuk' => fn (Blueprint $t) => $t->integer('masuk')->default(0),
            'keluar' => fn (Blueprint $t) => $t->integer('keluar')->default(0),
            'stok' => fn (Blueprint $t) => $t->integer('stok')->default(0),
        ]);
        $this->ensure('log_harga_barang', [
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'id_barang_masuk' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang_masuk')->nullable()->index(),
            'id_user' => fn (Blueprint $t) => $t->unsignedBigInteger('id_user')->nullable()->index(),
            'proses' => fn (Blueprint $t) => $t->string('proses', 20)->nullable(),
            'stok_sebelum' => fn (Blueprint $t) => $t->integer('stok_sebelum')->default(0),
            'harga_sebelum' => fn (Blueprint $t) => $t->bigInteger('harga_sebelum')->default(0),
            'qty_lama' => fn (Blueprint $t) => $t->integer('qty_lama')->nullable(),
            'harga_lama' => fn (Blueprint $t) => $t->bigInteger('harga_lama')->nullable(),
            'qty_baru' => fn (Blueprint $t) => $t->integer('qty_baru')->nullable(),
            'harga_baru_input' => fn (Blueprint $t) => $t->bigInteger('harga_baru_input')->nullable(),
            'stok_sesudah' => fn (Blueprint $t) => $t->integer('stok_sesudah')->default(0),
            'harga_sesudah' => fn (Blueprint $t) => $t->bigInteger('harga_sesudah')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable()->useCurrent(),
        ]);
        $this->ensure('pengajuan_barang', [
            'tanggal' => fn (Blueprint $t) => $t->date('tanggal')->nullable(),
            'id_supplier' => fn (Blueprint $t) => $t->unsignedBigInteger('id_supplier')->nullable()->index(),
            'total_harga' => fn (Blueprint $t) => $t->unsignedBigInteger('total_harga')->default(0),
            'jum_item' => fn (Blueprint $t) => $t->unsignedInteger('jum_item')->default(0),
            'status' => fn (Blueprint $t) => $t->unsignedTinyInteger('status')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
        ]);
        $this->ensure('pengajuan_barang_detail', [
            'id_pengajuan' => fn (Blueprint $t) => $t->unsignedBigInteger('id_pengajuan')->nullable()->index(),
            'id_barang' => fn (Blueprint $t) => $t->unsignedBigInteger('id_barang')->nullable()->index(),
            'jumlah' => fn (Blueprint $t) => $t->unsignedInteger('jumlah')->default(0),
            'harga_beli' => fn (Blueprint $t) => $t->unsignedBigInteger('harga_beli')->default(0),
            'sub_total' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_total')->default(0),
            'keterangan' => fn (Blueprint $t) => $t->text('keterangan')->nullable(),
        ]);

        $this->menus();
    }

    private function ensure(string $name, array $columns): void
    {
        if (! Schema::hasTable($name)) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $define) {
                    $define($table);
                }
            });
            return;
        }

        $missing = array_diff_key($columns, array_flip(Schema::getColumnListing($name)));
        if ($missing) {
            Schema::table($name, function (Blueprint $table) use ($missing) {
                foreach ($missing as $define) {
                    $define($table);
                }
            });
        }
    }

    private function menus(): void
    {
        if (! Schema::hasTable('menu')) {
            return;
        }

        $order = ((int) DB::table('menu')->where('id_parent', 0)->max('urutan')) + 1;
        $warehouse = DB::table('menu')->where('id_parent', 0)->where('title', 'Gudang')->value('id');
        if (! $warehouse) {
            $warehouse = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Gudang', 'route_name' => '#',
                'icon' => 'fas fa-warehouse', 'urutan' => $order++,
                'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
            ]);
        }

        $items = [
            [0, 'Barang Masuk', 'barang-masuk.index', 'fas fa-truck-loading', $order++, true],
            [0, 'Barang Keluar', 'barang-keluar.index', 'fas fa-dolly', $order++, true],
            [0, 'Pengajuan Barang', 'pengajuan-barang.index', 'fas fa-clipboard-list', $order++, true],
            [$warehouse, 'Supplier', 'supplier.index', 'fas fa-truck', 1, true],
            [$warehouse, 'Satuan', 'satuan.index', 'fas fa-ruler-combined', 2, true],
            [$warehouse, 'Barang', 'barang.index', 'fas fa-box-open', 3, true],
            [$warehouse, 'Riwayat Mutasi', 'riwayat-mutasi.index', 'fas fa-exchange-alt', 4, false],
            [$warehouse, 'Tukang', 'tukang.index', 'fas fa-hard-hat', 5, true],
            [$warehouse, 'Master Project', 'master-project.index', 'fas fa-project-diagram', 6, true],
            [$warehouse, 'Setting Proyek', 'setting-proyek.index', 'fas fa-tasks', 7, true],
            [$warehouse, 'Stok Barang', 'stok-barang.index', 'fas fa-boxes', 8, false],
        ];

        $this->grantAdministrators((int) $warehouse, false);
        foreach ($items as [$parent, $title, $route, $icon, $position, $writable]) {
            $id = DB::table('menu')->where('route_name', $route)->value('id');
            $values = ['id_parent' => $parent, 'title' => $title, 'route_name' => $route,
                'icon' => $icon, 'urutan' => $position, 'lihat' => 1,
                'tambah' => (int) $writable, 'edit' => (int) $writable, 'hapus' => (int) $writable];
            if ($id) {
                DB::table('menu')->where('id', $id)->update($values);
            } else {
                $id = DB::table('menu')->insertGetId($values);
            }
            $this->grantAdministrators((int) $id, $writable);
        }
    }

    private function grantAdministrators(int $menu, bool $writable): void
    {
        $adminMenu = DB::table('menu')->where('route_name', 'hak-akses.index')->value('id');
        if (! $adminMenu) {
            return;
        }
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $ownerKey) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $administrators = DB::table($table)->where('id_menu', $adminMenu)
                ->where('lihat', 1)->where('edit', 1)->distinct()->pluck($ownerKey);
            foreach ($administrators as $owner) {
                // Existing assignments are deliberately retained for installations that restore inventory.
                if (! DB::table($table)->where($ownerKey, $owner)->where('id_menu', $menu)->exists()) {
                    DB::table($table)->insert([$ownerKey => $owner, 'id_menu' => $menu,
                        'lihat' => 1, 'beranda' => 0, 'tambah' => (int) $writable,
                        'edit' => (int) $writable, 'hapus' => (int) $writable]);
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Inventory contains operational records. Restore a database backup to undo this migration safely.');
    }
};
