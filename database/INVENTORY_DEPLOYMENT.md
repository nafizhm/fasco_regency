# Pembaruan modul gudang

Setelah semua file aplikasi pembaruan diunggah, jalankan dari direktori aplikasi:

```bash
php artisan migrate --path=database/migrations/2026_11_10_010000_restore_inventory_modules.php --force
php artisan view:clear
php artisan route:clear
```

Pembaruan menu dan isi master berikutnya:

```bash
php artisan migrate --path=database/migrations/2026_11_10_020000_simplify_inventory_menus_and_import_master.php --force
php artisan view:clear
```

Sertakan `database/seeders/InventoryReferenceMasterSeeder.php` dan `database/inventory_reference_master.json` saat mengunggah migrasi ini. Snapshot diambil dari database `gudang_alaska` project referensi pada 10 November 2026: 18 satuan dan 77 barang. Barang baru dimulai dengan stok awal dan stok saat ini nol. Nama, relasi satuan, serta harga beli mengikuti sumber; pengulangan impor tidak menimpa stok/harga barang yang sudah ada. Menu Master Project, Setting Proyek, dan Pengajuan Barang dihapus beserta hak akses menunya. Data proyek dan transaksi tetap tersedia di database. Keluar lalu masuk kembali untuk memperbarui daftar menu sesi.

Migrasi ini menambahkan tabel inventori yang belum ada serta kolom yang belum ada pada tabel lama. Data yang sudah ada dipertahankan. Data contoh dan transaksi dari project referensi tidak disalin.

Tabel: `satuan`, `supplier`, `barang`, `barang_masuk`, `barang_masuk_detail`, `barang_masuk_lampiran`, `barang_masuk_pembayaran`, `barang_keluar`, `barang_keluar_detail`, `mutasi_stok`, `history_barang`, `log_harga_barang`, `pengajuan_barang`, `pengajuan_barang_detail`, `tukang`, `master_project`, `master_project_pekerjaan`, `master_project_detail`, `setting_proyek`.

Menu baru memakai `menu`, `hak_akses`, dan `role_user` milik aplikasi ini. Hak akses awal diberikan hanya kepada pengguna/role yang sudah mempunyai akses lihat dan edit menu Hak Akses. Pengguna lain dapat diberikan akses melalui pengaturan Hak Akses. Keluar lalu masuk kembali untuk memperbarui menu sesi yang sedang aktif.

Isi Satuan, Supplier, Barang, Tukang, Master Project/Pekerjaan, dan Setting Proyek sebelum mencatat transaksi sesuai kebutuhan. Lokasi dan unit menggunakan data aplikasi saat ini.

Urutan penggunaan:

1. Isi Satuan dan Supplier, lalu tambahkan Barang beserta stok awal dan harga beli master.
2. Catat pembelian di Barang Masuk. Stok bertambah; lampiran dan pembayaran supplier dapat dicatat pada transaksi tersebut.
3. Buat Tukang serta Master Project dan jenis pekerjaannya. Untuk pekerjaan unit, hubungkan lokasi/unit ke Master Project melalui Setting Proyek.
4. Catat Barang Keluar untuk unit atau proyek non-unit. Stok berkurang sesuai barang dan pekerjaan yang dipilih.
5. Periksa Riwayat Mutasi dan Stok Barang. Transaksi yang membuat stok negatif pada tanggal riwayatnya akan ditolak.

Harga pembelian transaksi dicatat terpisah dari harga beli master, mengikuti perilaku project referensi. Nilai laporan stok memakai harga beli master. Pembayaran supplier pada Barang Masuk dicatat pada modul gudang.

File integrasi mencakup `routes/web.php` dan `routes/inventory.php`; controller BarangMasuk/BarangKeluar, Master Barang/Supplier/Satuan, Gudang, Pengaturan Tukang/MasterProject/SettingProyek; model inventori terkait; service StockService/HargaBarangService/InventoryCompany; trait InventoryAccess; view `admin/barang_masuk`, `admin/barang_keluar`, `admin/gudang`, master Barang/Supplier/Satuan, serta pengaturan Tukang/MasterProject/SettingProyek. Unggah seluruh file integrasi beserta migrasi, bukan hanya view.

Migrasi tidak menghapus data saat rollback; `migrate:rollback` untuk migrasi ini sengaja ditolak. Gunakan backup database jika instalasi harus dipulihkan.

Pembaruan lampiran penerimaan: unggah controller BarangMasuk, model BarangMasukLampiran, view create/show/attachment_fields, dan migrasi `2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran.php`, lalu jalankan:

```bash
php artisan migrate --path=database/migrations/2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran.php --force
php artisan view:clear
```

Nota, Surat Jalan, dan Foto Barang Datang seluruhnya opsional. Maksimal 10 file per kategori, 2 MB per file. Nota/surat jalan mendukung JPG, PNG, PDF; foto mendukung JPG, PNG, WEBP. Lampiran lama tetap dapat dibuka pada daftar lampiran. Kategori disimpan di kolom nullable `barang_masuk_lampiran.jenis_lampiran`.
