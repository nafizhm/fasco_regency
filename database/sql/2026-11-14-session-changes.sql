-- Perubahan database sesi 14 November 2026 (Asia/Jakarta).
-- MySQL / MariaDB, jalankan pada database aplikasi yang sudah ada.
-- Prasyarat: tabel barang_masuk, tukang, menu, hak_akses, role_user, migrations.
-- Dapat dijalankan ulang: tidak menghapus transaksi, menimpa gaji, atau menggandakan nama/menu/hak akses.
-- DDL MySQL melakukan implicit commit. Catatan migrations ditulis terakhir setelah semua perubahan selesai.

SET NAMES utf8mb4;

-- 1. Barang masuk: tanggal jatuh tempo.
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'barang_masuk' AND COLUMN_NAME = 'jatuh_tempo') = 0,
    'ALTER TABLE `barang_masuk` ADD COLUMN `jatuh_tempo` DATE NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

-- 2. Data tukang: gaji per hari, tanpa mengubah gaji yang sudah diisi.
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tukang' AND COLUMN_NAME = 'gaji_per_hari') = 0,
    'ALTER TABLE `tukang` ADD COLUMN `gaji_per_hari` BIGINT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

CREATE TABLE IF NOT EXISTS `absen_tukang_pekan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `mulai` DATE NOT NULL,
    `versi` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `absen_tukang_pekan_mulai_unique` (`mulai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `absen_tukang_detail` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_pekan` BIGINT UNSIGNED NOT NULL,
    `id_tukang` BIGINT UNSIGNED NOT NULL,
    `gaji_per_hari` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `hari` JSON NOT NULL,
    UNIQUE KEY `absen_tukang_detail_id_pekan_id_tukang_unique` (`id_pekan`, `id_tukang`),
    KEY `absen_tukang_detail_id_tukang_index` (`id_tukang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Surat Perintah Kerja dan pembayaran.
CREATE TABLE IF NOT EXISTS `spk` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nomor` VARCHAR(255) NOT NULL,
    `tanggal` DATE NOT NULL,
    `nama_perusahaan` VARCHAR(255) NOT NULL,
    `telp_perusahaan` VARCHAR(255) NOT NULL,
    `penandatangan` VARCHAR(255) NOT NULL,
    `kota` VARCHAR(255) NOT NULL,
    `nama_pemborong` VARCHAR(255) NOT NULL,
    `jabatan_pemborong` VARCHAR(255) NOT NULL,
    `nama_proyek` VARCHAR(255) NOT NULL,
    `lama_pengerjaan` VARCHAR(255) NOT NULL,
    `alamat_perusahaan` TEXT NOT NULL,
    `alamat_pemborong` TEXT NOT NULL,
    `lokasi_proyek` TEXT NOT NULL,
    `jenis_pekerjaan` TEXT NOT NULL,
    `skema_pembayaran` TEXT NOT NULL,
    `tanggal_mulai` DATE NOT NULL,
    `tanggal_selesai` DATE NULL,
    `items` JSON NOT NULL,
    `total_rincian` BIGINT UNSIGNED NOT NULL,
    `nilai_borongan` BIGINT UNSIGNED NOT NULL,
    `versi` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `spk_nomor_unique` (`nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spk_pembayaran` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_spk` BIGINT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `nominal` BIGINT UNSIGNED NOT NULL,
    `metode` VARCHAR(100) NULL,
    `referensi` VARCHAR(255) NULL,
    `keterangan` TEXT NULL,
    `token` VARCHAR(36) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `spk_pembayaran_token_unique` (`token`),
    KEY `spk_pembayaran_id_spk_foreign` (`id_spk`),
    CONSTRAINT `spk_pembayaran_id_spk_foreign` FOREIGN KEY (`id_spk`) REFERENCES `spk` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;

-- 4. Menu induk Tukang; gunakan ID dari database tujuan.
SET @tukang_parent = (SELECT MIN(`id`) FROM `menu` WHERE `id_parent` = 0 AND `title` = 'Tukang' AND `route_name` = '#');
SET @menu_order = (SELECT COALESCE(MAX(`urutan`), 0) + 1 FROM `menu` WHERE `id_parent` = 0);
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT 0, 'Tukang', '#', 'fas fa-hard-hat', @menu_order, 1, 0, 0, 0
WHERE @tukang_parent IS NULL;
SET @tukang_parent = (SELECT MIN(`id`) FROM `menu` WHERE `id_parent` = 0 AND `title` = 'Tukang' AND `route_name` = '#');

-- Gunakan menu dan form Data Tukang yang sudah ada; pindahkan induknya.
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @tukang_parent, 'Data Tukang', 'tukang.index', 'fas fa-users', 1, 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name` = 'tukang.index');
UPDATE `menu` SET `id_parent` = @tukang_parent, `title` = 'Data Tukang', `icon` = 'fas fa-users',
    `urutan` = 1, `lihat` = 1, `tambah` = 1, `edit` = 1, `hapus` = 1
WHERE `route_name` = 'tukang.index';
SET @tukang_data = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'tukang.index');

INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @tukang_parent, 'Absen Tukang', 'absen-tukang.index', 'fas fa-calendar-check', 2, 1, 1, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name` = 'absen-tukang.index');
SET @tukang_absen = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'absen-tukang.index');

SET @menu_order = (SELECT COALESCE(MAX(`urutan`), 0) + 1 FROM `menu` WHERE `id_parent` = 0);
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT 0, 'SPK', 'spk.index', 'fas fa-file-contract', @menu_order, 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name` = 'spk.index');
SET @spk_menu = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'spk.index');
SET @admin_menu = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'hak-akses.index');

-- 5. Hak akses induk/absen diwarisi dari Data Tukang. SPK untuk pengelola Hak Akses.
INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @tukang_parent, src.lihat, 0, 0, 0, 0
FROM `hak_akses` AS src
WHERE src.`id_menu` = @tukang_data
  AND NOT EXISTS (SELECT 1 FROM `hak_akses` AS existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @tukang_parent);

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @tukang_absen, src.lihat, 0, src.tambah, src.edit, 0
FROM `hak_akses` AS src
WHERE src.`id_menu` = @tukang_data
  AND NOT EXISTS (SELECT 1 FROM `hak_akses` AS existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @tukang_absen);

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @spk_menu, 1, 0, 1, 1, 1
FROM `hak_akses` AS src
WHERE src.`id_menu` = @admin_menu AND src.lihat = 1 AND src.edit = 1
  AND NOT EXISTS (SELECT 1 FROM `hak_akses` AS existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @spk_menu);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @tukang_parent, src.lihat, 0, 0, 0, 0
FROM `role_user` AS src
WHERE src.`id_menu` = @tukang_data
  AND NOT EXISTS (SELECT 1 FROM `role_user` AS existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @tukang_parent);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @tukang_absen, src.lihat, 0, src.tambah, src.edit, 0
FROM `role_user` AS src
WHERE src.`id_menu` = @tukang_data
  AND NOT EXISTS (SELECT 1 FROM `role_user` AS existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @tukang_absen);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @spk_menu, 1, 0, 1, 1, 1
FROM `role_user` AS src
WHERE src.`id_menu` = @admin_menu AND src.lihat = 1 AND src.edit = 1
  AND NOT EXISTS (SELECT 1 FROM `role_user` AS existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @spk_menu);

-- 6. 46 nama dari lampiran. No. telp/alamat kosong dan gaji Rp0 untuk data baru.
-- Nama yang sudah ada tidak diubah. Saiful lama (48) adalah baris kedua dengan nama sama.
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Pak Wari', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Pak Wari');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Tarom', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Tarom');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Agit', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Agit');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'ainul', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'ainul');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Amin', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Amin');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Andik', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Andik');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Andre', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Andre');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Budi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Budi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Dayat', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Dayat');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'edi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'edi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Erdin', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Erdin');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Farhan', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Farhan');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Imam tukang', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Imam tukang');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Jaka', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Jaka');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Jamal', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Jamal');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Jarwo', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Jarwo');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Jumari', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Jumari');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Koni', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Koni');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Mandrak', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Mandrak');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Null', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Null');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Ponadi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Ponadi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Rangga', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Rangga');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Ridwan', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Ridwan');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Riob / Tirto', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Riob / Tirto');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'rifki', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'rifki');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Roni', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Roni');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Saiful (B)', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Saiful (B)');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Saiful lama', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Saiful lama');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Sallem', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Sallem');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Samadi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Samadi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Samain', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Samain');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Samsul', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Samsul');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Sodiq', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Sodiq');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Sueb', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Sueb');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Sugiono', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Sugiono');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Sunarto', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Sunarto');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Susilo', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Susilo');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Tirto', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Tirto');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Wardi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Wardi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Yoga', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Yoga');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Yudi', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Yudi');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Yulianto', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Yulianto');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Yusron', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Yusron');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'khusnul', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'khusnul');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'wasis', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'wasis');
INSERT INTO `tukang` (`nama`, `no_telp`, `alamat`, `gaji_per_hari`)
SELECT 'Saiful lama (48)', NULL, NULL, 0 WHERE NOT EXISTS (SELECT 1 FROM `tukang` WHERE `nama` = 'Saiful lama (48)');

-- 7. Tandai migrasi yang sudah dipenuhi oleh SQL ini agar php artisan migrate tidak menjalankannya ulang.
SET @session_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_10_040000_add_jatuh_tempo_to_barang_masuk', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_10_040000_add_jatuh_tempo_to_barang_masuk');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_14_010000_add_tukang_attendance', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_14_010000_add_tukang_attendance');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_14_020000_create_spk', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_14_020000_create_spk');

COMMIT;

-- Perubahan login password testing, warna/centang Hak Akses, toggle Semua M,
-- total per item Barang Masuk, separator Harga Satuan, dan cetak Word berada di file aplikasi.
-- Perubahan-perubahan tersebut tidak membutuhkan SQL tambahan.
