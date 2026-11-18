-- Kumpulan update hosting sesi 18 November 2026.
-- MySQL / MariaDB: pilih database aplikasi sebelum menjalankan.
-- Prasyarat: tabel wawancara_sp3k dan migrations sudah tersedia.
-- Disiapkan untuk deployment berikutnya; belum dijalankan di hosting.

-- SP3K: tenor dan nomor boleh dikosongkan, sesuai validasi aplikasi.
ALTER TABLE `wawancara_sp3k`
    MODIFY COLUMN `tenor` INT NULL DEFAULT NULL,
    MODIFY COLUMN `no_sp3k` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

-- Catat migrasi agar artisan tidak mengulang perubahan yang sama.
SET @session_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000000_make_acc_bank_sp3k_fields_optional', @session_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000000_make_acc_bank_sp3k_fields_optional'
);

-- Perubahan pendamping berupa file (bukan SQL):
-- resources/views/admin/transaksi/wawancara/index.blade.php
-- Penanganan error simpan SP3K dan pemulihan tombol melalui complete AJAX.
-- app/Http/Controllers/Keuangan/PengeluaranController.php
-- Kop cetak pengeluaran mengikuti identitas perusahaan dan logo profil pembayaran.
-- Perubahan kop pengeluaran tidak memerlukan perubahan SQL.
-- public/assets/img/logo-kwitansi.png
-- Logo cadangan saat file logo pengaturan tidak tersedia; ikut upload ke hosting.
-- Jarak tanggal dalam kotak dan nominal terhadap Rp juga dirapikan.
-- app/Http/Controllers/Keuangan/PemasukanController.php
-- Daftar Pemasukan hanya menampilkan transaksi tanpa customer (NULL / 0).
-- Pembayaran customer dan pencairan KPR tetap dikelola melalui Pembayaran.
-- Pemisahan daftar ini tidak memerlukan SQL atau penghapusan data lama.
-- resources/views/admin/keuangan/pemasukan/index.blade.php
-- Kolom daftar: No, Tanggal, Kategori, Keterangan, Nominal, Action.
-- Kolom Rekening diganti Keterangan; tidak memerlukan SQL.
-- Form tambah/edit/detail Pemasukan (termasuk kategori Pembayaran Piutang):
-- Rekening dihapus, validasi rekening dihapus, transaksi baru memakai id_bank = 0.
-- Rekening historis tetap disimpan saat edit. Tidak memerlukan perubahan SQL.
-- Action Pemasukan: tombol Lihat Lampiran membuka gambar/PDF dalam modal.
-- Kolom Action juga tersedia bagi pengguna yang hanya memiliki akses lihat.
-- Perubahan lampiran ini tidak memerlukan SQL.

-- Pengeluaran: nama bank dan rekening tujuan diisi manual.
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengeluaran' AND COLUMN_NAME = 'nama_bank') = 0,
    'ALTER TABLE `pengeluaran` ADD COLUMN `nama_bank` VARCHAR(255) NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengeluaran' AND COLUMN_NAME = 'rekening_tujuan') = 0,
    'ALTER TABLE `pengeluaran` ADD COLUMN `rekening_tujuan` VARCHAR(255) NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_010000_add_destination_account_to_pengeluaran', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_010000_add_destination_account_to_pengeluaran');

-- File tambahan untuk update Pengeluaran:
-- app/Models/Pengeluaran.php
-- resources/views/admin/keuangan/pengeluaran/index.blade.php
-- database/migrations/2026_11_18_010000_add_destination_account_to_pengeluaran.php
-- Pembayaran Hutang menggunakan form Pengeluaran yang sama: rekening tujuan
-- manual, urutan field seragam, kategori Pembayaran Hutang otomatis terpilih.
-- Action Pengeluaran: Lihat Lampiran (gambar/PDF) dalam modal seperti Pemasukan.
-- Termasuk lampiran transaksi Pembayaran Hutang. Tidak ada tambahan SQL.
-- Urutan kolom Pengeluaran: No, Tanggal, Kategori, Keterangan, Nominal, Action.
-- Kolom Rekening diganti Keterangan. Tidak ada tambahan SQL.
-- Tombol Action Pemasukan dan Pengeluaran diseragamkan menjadi ikon saja:
-- lampiran, cetak, detail, edit, hapus; dilengkapi title dan aria-label.
-- Kategori Pengeluaran hanya berisi nama kategori; keterangan di kolom tersendiri.
