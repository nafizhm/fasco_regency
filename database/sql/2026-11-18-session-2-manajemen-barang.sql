-- SESI BARU: Manajemen Barang dan Pengajuan Pembelian (18 November 2026).
-- MySQL/MariaDB. Pilih database aplikasi di phpMyAdmin sebelum import.
-- Terpisah dari 2026-11-18-session-changes.sql (sesi sebelumnya).
-- Prasyarat: modul inventory, menu, hak_akses, role_user dan migrations tersedia.
-- Jalankan SQL bersama upload file aplikasi pada catatan sesi ini.
-- Barang masuk lama tetap tanpa referensi; tidak ada penghapusan data lama.

CREATE TABLE IF NOT EXISTS `pengajuan_pembelian` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nomor` VARCHAR(100) NOT NULL,
    `tanggal` DATE NOT NULL,
    `id_supplier` BIGINT UNSIGNED NOT NULL,
    `keterangan` TEXT NULL,
    UNIQUE KEY `pengajuan_pembelian_nomor_unique` (`nomor`),
    KEY `pengajuan_pembelian_id_supplier_index` (`id_supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pengajuan_pembelian_detail` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_pengajuan` BIGINT UNSIGNED NOT NULL,
    `id_barang` BIGINT UNSIGNED NOT NULL,
    `jumlah` INT UNSIGNED NOT NULL,
    KEY `pengajuan_pembelian_detail_id_pengajuan_index` (`id_pengajuan`),
    UNIQUE KEY `pengajuan_pembelian_barang_unique` (`id_pengajuan`, `id_barang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @purchase_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'barang_masuk' AND COLUMN_NAME = 'id_pengajuan_pembelian') = 0,
    'ALTER TABLE `barang_masuk` ADD COLUMN `id_pengajuan_pembelian` BIGINT UNSIGNED NULL, ADD INDEX `barang_masuk_id_pengajuan_pembelian_index` (`id_pengajuan_pembelian`)',
    'SELECT 1');
PREPARE purchase_stmt FROM @purchase_sql;
EXECUTE purchase_stmt;
DEALLOCATE PREPARE purchase_stmt;

SET @purchase_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_020000_create_pengajuan_pembelian', @purchase_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_18_020000_create_pengajuan_pembelian');

-- Menu: pindahkan ID menu lama sehingga hak akses Barang Masuk/Keluar tetap.
SET @purchase_source = (SELECT `id` FROM `menu` WHERE `route_name` = 'barang-masuk.index' LIMIT 1);
SET @purchase_position = (SELECT COALESCE(MIN(`urutan`), 20) FROM `menu` WHERE `route_name` IN ('barang-masuk.index', 'barang-keluar.index'));
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT 0, 'Manajemen Barang', '#', 'fas fa-boxes', @purchase_position, 1, 0, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `title` = 'Manajemen Barang' AND `id_parent` = 0);
SET @purchase_parent = (SELECT `id` FROM `menu` WHERE `title` = 'Manajemen Barang' AND `id_parent` = 0 LIMIT 1);

INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @purchase_parent, 'Pengajuan Pembelian', 'pengajuan-pembelian.index', 'fas fa-clipboard-list', 1, 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name` = 'pengajuan-pembelian.index');
SET @purchase_menu = (SELECT `id` FROM `menu` WHERE `route_name` = 'pengajuan-pembelian.index' LIMIT 1);
UPDATE `menu` SET `id_parent` = @purchase_parent, `urutan` = 1 WHERE `id` = @purchase_menu;
UPDATE `menu` SET `id_parent` = @purchase_parent, `urutan` = 2 WHERE `route_name` = 'barang-masuk.index';
UPDATE `menu` SET `id_parent` = @purchase_parent, `urutan` = 3 WHERE `route_name` = 'barang-keluar.index';

-- Hak menu baru mengikuti hak Barang Masuk; tidak memperluas hak pengguna lain.
INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT a.`id_user`, @purchase_menu, a.`lihat`, 0, a.`tambah`, a.`edit`, a.`hapus`
FROM `hak_akses` a WHERE a.`id_menu` = @purchase_source
AND NOT EXISTS (SELECT 1 FROM `hak_akses` b WHERE b.`id_user` = a.`id_user` AND b.`id_menu` = @purchase_menu);
INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT a.`id_role`, @purchase_menu, a.`lihat`, 0, a.`tambah`, a.`edit`, a.`hapus`
FROM `role_user` a WHERE a.`id_menu` = @purchase_source
AND NOT EXISTS (SELECT 1 FROM `role_user` b WHERE b.`id_role` = a.`id_role` AND b.`id_menu` = @purchase_menu);

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT a.`id_user`, @purchase_parent, 1, 0, 0, 0, 0
FROM `hak_akses` a JOIN `menu` m ON m.`id` = a.`id_menu`
WHERE m.`id_parent` = @purchase_parent AND a.`lihat` = 1
AND NOT EXISTS (SELECT 1 FROM `hak_akses` b WHERE b.`id_user` = a.`id_user` AND b.`id_menu` = @purchase_parent);
UPDATE `hak_akses` p SET p.`lihat` = 1 WHERE p.`id_menu` = @purchase_parent AND p.`id_user` IN (
    SELECT visible.`id_user` FROM (SELECT DISTINCT a.`id_user` FROM `hak_akses` a
    JOIN `menu` m ON m.`id` = a.`id_menu` WHERE m.`id_parent` = @purchase_parent AND a.`lihat` = 1) visible);
INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT a.`id_role`, @purchase_parent, 1, 0, 0, 0, 0
FROM `role_user` a JOIN `menu` m ON m.`id` = a.`id_menu`
WHERE m.`id_parent` = @purchase_parent AND a.`lihat` = 1
AND NOT EXISTS (SELECT 1 FROM `role_user` b WHERE b.`id_role` = a.`id_role` AND b.`id_menu` = @purchase_parent);
UPDATE `role_user` p SET p.`lihat` = 1 WHERE p.`id_menu` = @purchase_parent AND p.`id_role` IN (
    SELECT visible.`id_role` FROM (SELECT DISTINCT a.`id_role` FROM `role_user` a
    JOIN `menu` m ON m.`id` = a.`id_menu` WHERE m.`id_parent` = @purchase_parent AND a.`lihat` = 1) visible);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_030000_add_manajemen_barang_menu', @purchase_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_18_030000_add_manajemen_barang_menu');
