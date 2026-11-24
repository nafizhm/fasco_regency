-- Paket lengkap perubahan SQL sesi 20 November 2026.
-- 1. Kolom Biaya KPR pada tambah/edit akad.
-- 2. Submenu Laporan Tukang beserta hak akses.
-- 3. Menu Rekap Transaksi setelah SPK beserta hak akses dan urutan menu.
-- 4. Tabel dan menu Fee Marketing beserta hak CRUD (23 November 2026).
-- Prasyarat: database sudah memuat perubahan sebelum sesi ini, termasuk modul Tukang dan SPK.
-- Jalankan pada database aplikasi yang dituju. Data lama tetap NULL.
SET @biaya_kpr_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'detail_akad' AND COLUMN_NAME = 'biaya_kpr') = 0,
    'ALTER TABLE `detail_akad` ADD COLUMN `biaya_kpr` DECIMAL(18,2) NULL',
    'SELECT 1');
PREPARE biaya_kpr_stmt FROM @biaya_kpr_sql;
EXECUTE biaya_kpr_stmt;
DEALLOCATE PREPARE biaya_kpr_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_20_000000_add_biaya_kpr_to_detail_akad', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_20_000000_add_biaya_kpr_to_detail_akad');

-- Submenu Laporan Tukang. Prasyarat: menu Tukang/Absen Tukang sudah tersedia.
-- Laporan memakai absensi dan tarif tersimpan, tanpa tabel pembayaran baru.
SET @laporan_tukang_source = COALESCE(
    (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'absen-tukang.index'),
    (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'tukang.index'));
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT src.`id_parent`, 'Laporan Tukang', 'laporan-tukang.index', 'fas fa-chart-bar', 3, 1, 0, 0, 0
FROM `menu` src WHERE src.`id` = @laporan_tukang_source
AND NOT EXISTS (SELECT 1 FROM `menu` existing WHERE existing.`route_name` = 'laporan-tukang.index');
SET @laporan_tukang_menu = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'laporan-tukang.index');

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @laporan_tukang_menu, src.`lihat`, 0, 0, 0, 0
FROM `hak_akses` src WHERE src.`id_menu` = @laporan_tukang_source AND @laporan_tukang_menu IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `hak_akses` existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @laporan_tukang_menu);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @laporan_tukang_menu, src.`lihat`, 0, 0, 0, 0
FROM `role_user` src WHERE src.`id_menu` = @laporan_tukang_source AND @laporan_tukang_menu IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_user` existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @laporan_tukang_menu);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_20_010000_add_laporan_tukang_menu', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE @laporan_tukang_menu IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_20_010000_add_laporan_tukang_menu');

-- Rekap Transaksi tepat di bawah SPK. Prasyarat: menu SPK dan tabel hak akses tersedia.
START TRANSACTION;
SET @rekap_spk_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'spk.index');
SET @rekap_spk_parent = (SELECT `id_parent` FROM `menu` WHERE `id` = @rekap_spk_id);
SET @rekap_spk_order = (SELECT `urutan` FROM `menu` WHERE `id` = @rekap_spk_id);
SET @rekap_menu_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'rekap-transaksi.index');
UPDATE `menu` SET `urutan` = `urutan` + 1
WHERE @rekap_menu_id IS NULL AND `id_parent` = @rekap_spk_parent AND `urutan` > @rekap_spk_order;
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @rekap_spk_parent, 'Rekap Transaksi', 'rekap-transaksi.index', 'fas fa-chart-bar', @rekap_spk_order + 1, 1, 0, 0, 0
WHERE @rekap_menu_id IS NULL AND @rekap_spk_id IS NOT NULL;
SET @rekap_menu_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'rekap-transaksi.index');

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @rekap_menu_id, src.`lihat`, 0, 0, 0, 0
FROM `hak_akses` src WHERE src.`id_menu` = @rekap_spk_id AND @rekap_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `hak_akses` existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @rekap_menu_id);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @rekap_menu_id, src.`lihat`, 0, 0, 0, 0
FROM `role_user` src WHERE src.`id_menu` = @rekap_spk_id AND @rekap_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_user` existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @rekap_menu_id);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_20_020000_add_rekap_transaksi_menu', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE @rekap_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_20_020000_add_rekap_transaksi_menu');
COMMIT;

-- 4. Fee Marketing: CRUD, rincian/riwayat fleksibel dan cetak PDF (23 November 2026).
-- Prasyarat: menu pemasukan.index tersedia. Hak akses awal menyalin Pemasukan.
CREATE TABLE IF NOT EXISTS `fee_marketing` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomor` VARCHAR(150) NOT NULL,
  `tanggal` DATE NOT NULL,
  `jumlah` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `data` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_marketing_nomor_unique` (`nomor`),
  KEY `fee_marketing_tanggal_index` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
SET @fee_source_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'pemasukan.index');
SET @fee_parent = (SELECT `id_parent` FROM `menu` WHERE `id` = @fee_source_id);
SET @fee_order = (SELECT `urutan` FROM `menu` WHERE `id` = @fee_source_id);
SET @fee_menu_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'fee-marketing.index');
UPDATE `menu` SET `urutan` = `urutan` + 1
WHERE @fee_menu_id IS NULL AND `id_parent` = @fee_parent AND `urutan` > @fee_order;
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @fee_parent, 'Fee Marketing', 'fee-marketing.index', 'fas fa-hand-holding-usd', @fee_order + 1, 1, 1, 1, 1
WHERE @fee_menu_id IS NULL AND @fee_source_id IS NOT NULL;
SET @fee_menu_id = (SELECT MIN(`id`) FROM `menu` WHERE `route_name` = 'fee-marketing.index');

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_user`, @fee_menu_id, src.`lihat`, 0, src.`tambah`, src.`edit`, src.`hapus`
FROM `hak_akses` src WHERE src.`id_menu` = @fee_source_id AND @fee_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `hak_akses` existing WHERE existing.`id_user` = src.`id_user` AND existing.`id_menu` = @fee_menu_id);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT DISTINCT src.`id_role`, @fee_menu_id, src.`lihat`, 0, src.`tambah`, src.`edit`, src.`hapus`
FROM `role_user` src WHERE src.`id_menu` = @fee_source_id AND @fee_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_user` existing WHERE existing.`id_role` = src.`id_role` AND existing.`id_menu` = @fee_menu_id);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_23_000000_create_fee_marketing', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE @fee_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_23_000000_create_fee_marketing');
COMMIT;

-- Fee Marketing menjadi menu utama (bukan submenu Keuangan).
UPDATE `menu` SET `id_parent` = 0 WHERE `route_name` = 'fee-marketing.index';

-- Delegasi Pekerjaan pada dashboard (23 November 2026).
-- Prasyarat: tabel work_priorities dan migrations sudah tersedia.
-- Nilai NULL mempertahankan semua pekerjaan lama sebagai Prioritas Pekerjaan.
SET @delegasi_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_priorities' AND COLUMN_NAME = 'delegated_to') = 0,
    'ALTER TABLE `work_priorities` ADD COLUMN `delegated_to` VARCHAR(200) NULL, ADD INDEX `work_priorities_delegated_to_index` (`delegated_to`)',
    'SELECT 1');
PREPARE delegasi_stmt FROM @delegasi_sql;
EXECUTE delegasi_stmt;
DEALLOCATE PREPARE delegasi_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_23_020000_add_delegated_to_to_work_priorities', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_23_020000_add_delegated_to_to_work_priorities');


-- Purchase Order (24 November 2026).
-- Ganti nama menu menjadi Purchase Order. Route dan hak akses tetap.
UPDATE `menu` SET `title` = 'Purchase Order' WHERE `route_name` = 'pengajuan-pembelian.index';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_24_000000_rename_purchase_order_menu', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_24_000000_rename_purchase_order_menu');

-- Pilihan tampil pada riwayat kwitansi customer (24 November 2026).
SET @kwitansi_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pemasukan' AND COLUMN_NAME = 'tampilkan_kwitansi') = 0,
    'ALTER TABLE `pemasukan` ADD COLUMN `tampilkan_kwitansi` TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT 1');
PREPARE kwitansi_stmt FROM @kwitansi_sql;
EXECUTE kwitansi_stmt;
DEALLOCATE PREPARE kwitansi_stmt;
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_24_010000_add_tampilkan_kwitansi_to_pemasukan', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_24_010000_add_tampilkan_kwitansi_to_pemasukan');
