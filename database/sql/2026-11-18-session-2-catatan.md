# Sesi baru: Manajemen Barang dan Pengajuan Pembelian

SQL hosting: `2026-11-18-session-2-manajemen-barang.sql`.
Catatan ini terpisah dari kumpulan perubahan sesi sebelumnya.

## Perilaku

- Menu Manajemen Barang berisi Pengajuan Pembelian, Barang Masuk, Barang Keluar.
- ID dan hak akses menu Barang Masuk/Keluar dipertahankan. Hak Pengajuan Pembelian mengikuti Barang Masuk; selanjutnya dapat diatur melalui Hak Akses.
- Pengajuan berisi nomor unik, tanggal, supplier, keterangan, barang dan jumlah rencana.
- Pengajuan tidak menambah stok. Harga aktual diisi saat penerimaan.
- Barang Masuk baru wajib memilih pengajuan. Supplier dan barang mengikuti referensi.
- Semua barang otomatis muncul bersama jumlah diajukan dan sisa. Jumlah datang bisa diubah; isi 0 untuk barang yang belum datang. Hanya jumlah positif yang menambah stok.
- Penerimaan dapat bertahap. Total yang diterima tidak boleh melebihi jumlah pengajuan. Validasi dilakukan di server dan pengajuan dikunci dalam transaksi saat penerimaan/ubah/hapus.
- Pengajuan yang sudah digunakan tidak dapat diedit/dihapus. Barang dan supplier yang dirujuk pengajuan tidak dapat dihapus.
- Barang Masuk lama tetap dapat dibuka dan diedit tanpa dipaksa memiliki pengajuan. Referensi penerimaan tidak dapat diganti.

## File aplikasi yang perlu diunggah

- `routes/inventory.php`
- `app/Http/Controllers/PengajuanPembelianController.php`
- `app/Http/Controllers/BarangMasukController.php`
- `app/Http/Controllers/Master/BarangController.php`
- `app/Http/Controllers/Master/SupplierController.php`
- `app/Models/PengajuanPembelian.php`
- `app/Models/PengajuanPembelianDetail.php`
- `app/Models/BarangMasuk.php`
- `app/Services/PurchaseReceivingService.php`
- `resources/views/admin/pengajuan_pembelian/index.blade.php`
- `resources/views/admin/pengajuan_pembelian/form.blade.php`
- `resources/views/admin/pengajuan_pembelian/show.blade.php`
- `resources/views/admin/barang_masuk/index.blade.php`
- `resources/views/admin/barang_masuk/create.blade.php`
- `resources/views/admin/barang_masuk/show.blade.php`
- `database/migrations/2026_11_18_020000_create_pengajuan_pembelian.php`
- `database/migrations/2026_11_18_030000_add_manajemen_barang_menu.php`

Setelah upload dan import SQL, jalankan `php artisan optimize:clear` di hosting.
SQL belum dijalankan di hosting.

## Verifikasi lokal

- Migrasi tabel dan menu sudah dijalankan di lokal.
- SQL hosting diuji impor ulang pada MySQL lokal; berhasil tanpa duplikasi menu/migrasi.
- Tes inventory mencakup rencana tanpa penambahan stok, penerimaan bertahap, jumlah berlebih,
  barang/supplier yang tidak sesuai referensi, hak akses, edit/hapus, dan penerimaan lama.
- Form pengajuan dan barang masuk mengembalikan HTTP 200, JavaScript hasil render lolos pemeriksaan sintaks.
