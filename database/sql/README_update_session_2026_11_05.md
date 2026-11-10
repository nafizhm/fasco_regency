# Pembaruan sesi 5 November 2026

File utama: `update_session_2026_11_05.sql`.

SQL ini adalah alternatif menjalankan empat migration baru hari ini. SQL juga mencatat keempat migration sebagai selesai sehingga `artisan migrate` berikutnya tidak mengulang penambahan kolom dan menu. Tidak perlu menjalankan empat migration tersebut secara terpisah setelah impor berhasil.

## Isi perubahan

1. `pengajuan_hold`: kolom `diskon` dan `rincian_biaya_penjualan`.
2. `customer`: kolom `catatan_kwitansi`, `harga_jual_awal`, `diskon`, `id_booking`, serta indeks `id_booking`.
3. `customer_tempo`: kolom `harga_jual_awal` dan `diskon`.
4. Tabel baru `notary_deposit_accounts`, `notary_deposit_bills`, dan `notary_deposit_entries`, termasuk `item_index`, indeks, dan pencegahan token transaksi ganda.
5. Menu Pengaturan → Catatan Kwitansi Customer beserta hak akses pengguna, mengikuti Pengaturan Profil.
6. Menu Keuangan → Deposit Notaris beserta hak akses pengguna dan role, mengikuti Pengeluaran.
7. Inisialisasi harga sebelum diskon dan hubungan booking lama hanya untuk customer yang belum memiliki metadata harga. Hubungan booking hanya diisi jika cocok tepat satu.
8. Kapitalisasi nama customer.
9. Pembuatan piutang untuk customer aktif dengan unit valid yang belum mempunyai piutang. Nominal mengikuti harga customer; pembayaran lama diperhitungkan, termasuk status lunas dan tanggal pelunasannya. Tagihan yang sudah ada tidak ditimpa atau digandakan.
10. Mengosongkan keterangan lama pada 56 data `akad` dan 56 `detail_akad` yang dibersihkan pada sesi ini. Setiap UPDATE dijaga oleh ID dan teks lama persis; keterangan yang sudah berbeda di server dipertahankan.
11. Pencatatan empat migration `2026_11_05_*` di tabel `migrations`.

Tidak ada angka rekening/deposit/pembayaran notaris dari gambar yang diimpor. Tidak ada transaksi simulasi, perubahan harga uji, isi catatan khusus yang diisi lewat UI lokal, atau transaksi lokal baru yang disalin ke server.

## Cara menjalankan di server

1. Backup database dan file aplikasi terlebih dahulu. Gunakan database aplikasi yang benar, bukan database kosong. Struktur sebelum sesi ini harus sudah tersedia.
2. Unggah kode aplikasi terbaru, termasuk controller, service, model, route, view, JavaScript, dan keempat file migration.
3. Jalankan `php artisan down` selama impor supaya pencatatan pembayaran tidak berjalan bersamaan dengan pengisian piutang.
4. Pilih database aplikasi di phpMyAdmin lalu impor **seluruh** `update_session_2026_11_05.sql`. Versi terbaru tidak memakai stored procedure, `CALL`, atau `DELIMITER`. Akun database memerlukan izin ALTER, CREATE TABLE, CREATE TEMPORARY TABLES, dan perubahan data. Diuji pada MySQL 8.0.30; sintaks ditujukan untuk MySQL 5.7+/8.x.
5. Hentikan jika ada error; jangan mengabaikannya menggunakan `--force`. DDL MySQL melakukan implicit commit, sehingga transaksi tidak dapat membatalkan seluruh perubahan struktur. File dirancang dapat dijalankan ulang setelah penyebab error diperbaiki.
6. Setelah impor selesai, jalankan `check_update_session_2026_11_05.sql` secara terpisah pada tab SQL untuk memeriksa: empat migration, dua menu baru, dan jumlah customer aktif dengan harga positif yang belum memiliki piutang.
7. Jalankan:

   ```sh
   php artisan optimize:clear
   php artisan migrate:status
   php artisan view:cache
   php artisan up
   ```

8. Refresh aplikasi. Jika menu baru belum terlihat untuk pengguna tertentu, periksa Hak Akses. Hak yang sudah diatur pada server tidak ditimpa oleh impor ulang.

## Perubahan file yang tidak memiliki SQL

Perbaikan SVG siteplan, badge status Akad, tata letak modal, cetak rekap/kwitansi, hapus fitur DP ke Bank dari antarmuka, cetak progres per unit, kategori pemasukan dinamis dan pencarian, lampiran opsional/modal, serta sinkronisasi harga dan piutang bekerja lewat kode aplikasi. Kolom historis DP ke Bank sengaja tidak dihapus.

Perbaikan cetak kwitansi juga mengoptimalkan gambar logo yang semula sangat besar. Salin:

`deploy_assets_2026_11_05/config_media/HJ11KIrBWxQ0ptnoxgQHZpgqm.jpg`

ke:

`public/config_media/HJ11KIrBWxQ0ptnoxgQHZpgqm.jpg`

File tersebut adalah logo yang dipakai konfigurasi lokal pada sesi ini; pengaturan nama file di database tidak diubah oleh SQL. Jika server menggunakan logo lain, optimalkan logo server tersebut, jangan mengubah referensinya ke nama file lokal tanpa mencocokkan konfigurasi. Gambar paket berukuran 800 × 566 piksel. Direktori `public/config_media` diabaikan Git sehingga file ini perlu diunggah secara terpisah.

Upload lampiran deposit notaris menggunakan disk Laravel `local`; pastikan direktori penyimpanannya dapat ditulis aplikasi.

## Verifikasi paket

SQL telah diuji pada database MySQL sementara dengan kolom baru dilepas dan menu baru belum ada. Pengujian mencakup pemasangan awal, impor kedua tanpa duplikasi, pemeliharaan tagihan lama, pembayaran berlebih, pengecualian biaya ganti nama/pembayaran terhubung ke tagihan lain, dan tanggal pelunasan. Database sementara sudah dihapus. Database aplikasi tidak diubah saat pengujian paket.

## Jika phpMyAdmin menampilkan error #2014

File utama sekarang telah diganti dengan versi TANPA stored procedure, `CALL`, dan `DELIMITER`. Penambahan kolom memakai prepared DDL; pengisian piutang dan alokasi pembayaran memakai query biasa serta tabel TEMPORARY. Tidak ada hasil SELECT yang dikeluarkan saat impor.

1. Unduh ulang file SQL atau ZIP terbaru. Jangan menggunakan salinan dari impor sebelumnya.
2. Keluar lalu masuk kembali ke phpMyAdmin agar memakai koneksi baru.
3. Pilih database aplikasi dan jalankan `SET FOREIGN_KEY_CHECKS = 1;` pada tab SQL.
4. Impor **seluruh file utama terbaru** melalui tab Import. Jika tersedia, nonaktifkan opsi Enable foreign key checks pada form impor untuk menghindari perintah pemeriksaan otomatis phpMyAdmin; file SQL sendiri tidak menonaktifkan foreign key checks.
5. Setelah impor, jalankan `check_update_session_2026_11_05.sql` secara terpisah.

Perintah `SET FOREIGN_KEY_CHECKS = ON` pada laporan error berasal dari phpMyAdmin, bukan dari file ini. Jangan hanya menggantinya menjadi `1` pada koneksi yang masih mengalami #2014: mulai ulang koneksi terlebih dahulu.

Sebagian perintah impor sebelumnya mungkin telah berhasil. Versi terbaru tetap dapat dijalankan ulang tanpa menggandakan menu, piutang, atau migration, dan mempertahankan tagihan lama. Empat migration saja tidak membuktikan semua proses selesai; periksa juga menu dan jumlah customer yang belum memiliki piutang.

Versi tanpa CALL telah diuji dua kali dengan eksekusi query satu per satu tanpa pembersihan result set khusus, diikuti `SET FOREIGN_KEY_CHECKS = 1`, pada database MySQL sementara. Pengujian memeriksa alokasi pembayaran dan tanggal pelunasan, serta tidak adanya tagihan ganda.
