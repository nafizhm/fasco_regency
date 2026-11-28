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


-- Pengajuan Tagihan - sesi 28 November 2026.
-- Jalankan pada database aplikasi hosting. Aman dijalankan ulang.
CREATE TABLE IF NOT EXISTS `pengajuan_tagihan` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `nomor` VARCHAR(100) NOT NULL,
 `tanggal` DATE NOT NULL,
 `status` VARCHAR(30) NOT NULL DEFAULT 'Draft',
 `keterangan` TEXT NULL,
 `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
 UNIQUE KEY `pengajuan_tagihan_nomor_unique` (`nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pengajuan_tagihan_detail` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `id_pengajuan` BIGINT UNSIGNED NOT NULL,
 `tanggal_nota_max` DATE NULL,
 `nama` VARCHAR(255) NOT NULL, `uraian` TEXT NOT NULL,
 `fasco_regency` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `tegalgondo` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `ruko` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `bedali` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `lumajang` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 `rekening` VARCHAR(500) NULL, `lampiran` VARCHAR(255) NULL, `nama_lampiran` VARCHAR(255) NULL,
 CONSTRAINT `pengajuan_tagihan_detail_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_tagihan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
START TRANSACTION;
SET @pt_urutan = (SELECT COALESCE(MAX(`urutan`),0) + 1 FROM `menu`);
INSERT INTO `menu` (`id_parent`,`title`,`route_name`,`icon`,`urutan`,`lihat`,`tambah`,`edit`,`hapus`)
SELECT 0,'Pengajuan Tagihan','pengajuan-tagihan.index','fas fa-file-invoice',@pt_urutan,1,1,1,1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name`='pengajuan-tagihan.index');
SET @pt_menu = (SELECT `id` FROM `menu` WHERE `route_name`='pengajuan-tagihan.index' LIMIT 1);
UPDATE `hak_akses` h JOIN `users` u ON u.`id`=h.`id_user`
SET h.`lihat`=1,h.`tambah`=1,h.`edit`=1,h.`hapus`=1
WHERE LOWER(u.`username`)='nita' AND h.`id_menu`=@pt_menu;
INSERT INTO `hak_akses` (`id_user`,`id_menu`,`lihat`,`beranda`,`tambah`,`edit`,`hapus`)
SELECT u.`id`,@pt_menu,1,0,1,1,1 FROM `users` u
WHERE LOWER(u.`username`)='nita' AND NOT EXISTS (SELECT 1 FROM `hak_akses` h WHERE h.`id_user`=u.`id` AND h.`id_menu`=@pt_menu);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_28_000000_create_pengajuan_tagihan', b.batch
FROM (SELECT COALESCE(MAX(`batch`),0)+1 AS batch FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_28_000000_create_pengajuan_tagihan');
COMMIT;
-- Verifikasi nita memperoleh akses (hasil harus menunjukkan lihat/tambah/edit/hapus = 1).
SELECT u.`username`,h.`lihat`,h.`tambah`,h.`edit`,h.`hapus` FROM `hak_akses` h
JOIN `users` u ON u.`id`=h.`id_user` WHERE h.`id_menu`=@pt_menu AND LOWER(u.`username`)='nita';
-- Login ulang agar menu pada session diperbarui.


-- Invoice IPL - 28 November 2026. Aman dijalankan ulang.
CREATE TABLE IF NOT EXISTS `invoice_ipl` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `nomor` VARCHAR(100) NOT NULL, `nama` VARCHAR(255) NOT NULL, `alamat` TEXT NOT NULL,
 `tanggal_stu` DATE NOT NULL, `nominal_tahun` BIGINT UNSIGNED NOT NULL, `jatuh_tempo` DATE NOT NULL,
 `nama_rekening` VARCHAR(255) NOT NULL, `bank` VARCHAR(100) NOT NULL, `no_rekening` VARCHAR(100) NOT NULL,
 `penandatangan` VARCHAR(255) NOT NULL, `jabatan` VARCHAR(255) NOT NULL,
 `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
 UNIQUE KEY `invoice_ipl_nomor_unique` (`nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `invoice_ipl_pembayaran` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `id_invoice` BIGINT UNSIGNED NOT NULL,
 `tanggal` DATE NOT NULL, `nominal` BIGINT UNSIGNED NOT NULL, `transaksi` VARCHAR(50) NOT NULL,
 `keterangan` VARCHAR(1000) NULL,
 CONSTRAINT `invoice_ipl_pembayaran_id_invoice_foreign` FOREIGN KEY (`id_invoice`) REFERENCES `invoice_ipl` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
START TRANSACTION;
SET @ipl_urutan = (SELECT COALESCE(MAX(`urutan`),0)+1 FROM `menu`);
INSERT INTO `menu` (`id_parent`,`title`,`route_name`,`icon`,`urutan`,`lihat`,`tambah`,`edit`,`hapus`)
SELECT 0,'Invoice IPL','invoice-ipl.index','fas fa-file-invoice',@ipl_urutan,1,1,1,1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name`='invoice-ipl.index');
SET @ipl_menu=(SELECT `id` FROM `menu` WHERE `route_name`='invoice-ipl.index' LIMIT 1);
UPDATE `hak_akses` h JOIN `users` u ON u.`id`=h.`id_user`
SET h.`lihat`=1,h.`tambah`=1,h.`edit`=1,h.`hapus`=1
WHERE LOWER(u.`username`)='nita' AND h.`id_menu`=@ipl_menu;
INSERT INTO `hak_akses` (`id_user`,`id_menu`,`lihat`,`beranda`,`tambah`,`edit`,`hapus`)
SELECT u.`id`,@ipl_menu,1,0,1,1,1 FROM `users` u WHERE LOWER(u.`username`)='nita'
AND NOT EXISTS (SELECT 1 FROM `hak_akses` h WHERE h.`id_user`=u.`id` AND h.`id_menu`=@ipl_menu);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_28_010000_create_invoice_ipl',b.batch FROM (SELECT COALESCE(MAX(`batch`),0)+1 AS batch FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_28_010000_create_invoice_ipl');
COMMIT;
-- Login ulang sebagai Nita untuk memperbarui menu session.


-- Rekap KPR - 28 November 2026. Aman dijalankan ulang.
CREATE TABLE IF NOT EXISTS `rekap_kpr` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 `bank` VARCHAR(100) NOT NULL, `nama_user` VARCHAR(255) NOT NULL, `blok` VARCHAR(100) NULL,
 `tanggal_booking` DATE NULL, `tanggal_itj` DATE NULL, `tanggal_masuk` DATE NULL,
 `keterangan` TEXT NULL, `perumahan` VARCHAR(150) NOT NULL, `tandai_merah` TINYINT(1) NOT NULL DEFAULT 0,
 `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
 KEY `rekap_kpr_bank_index` (`bank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
START TRANSACTION;
SET @rekap_kpr_urutan=(SELECT COALESCE(MAX(`urutan`),0)+1 FROM `menu`);
INSERT INTO `menu` (`id_parent`,`title`,`route_name`,`icon`,`urutan`,`lihat`,`tambah`,`edit`,`hapus`)
SELECT 0,'Rekap KPR','rekap-kpr.index','fas fa-list-alt',@rekap_kpr_urutan,1,1,1,1
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name`='rekap-kpr.index');
SET @rekap_kpr_menu=(SELECT `id` FROM `menu` WHERE `route_name`='rekap-kpr.index' LIMIT 1);
UPDATE `hak_akses` h JOIN `users` u ON u.`id`=h.`id_user`
SET h.`lihat`=1,h.`tambah`=1,h.`edit`=1,h.`hapus`=1
WHERE LOWER(u.`username`)='tutut' AND h.`id_menu`=@rekap_kpr_menu;
INSERT INTO `hak_akses` (`id_user`,`id_menu`,`lihat`,`beranda`,`tambah`,`edit`,`hapus`)
SELECT u.`id`,@rekap_kpr_menu,1,0,1,1,1 FROM `users` u WHERE LOWER(u.`username`)='tutut'
AND NOT EXISTS (SELECT 1 FROM `hak_akses` h WHERE h.`id_user`=u.`id` AND h.`id_menu`=@rekap_kpr_menu);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_28_020000_create_rekap_kpr',b.batch FROM (SELECT COALESCE(MAX(`batch`),0)+1 AS batch FROM `migrations`) b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_28_020000_create_rekap_kpr');
COMMIT;
-- Login ulang Tutut agar menu tampil.


-- Berikan akses penuh menu Rekap KPR kepada seluruh user yang sudah ada.
-- Prasyarat: menu rekap-kpr.index sudah dibuat melalui SQL/migrasi Rekap KPR.
-- Aman dijalankan ulang; tidak mengubah akses menu lainnya.
START TRANSACTION;
UPDATE `hak_akses` h
JOIN `menu` m ON m.`id` = h.`id_menu`
JOIN `users` u ON u.`id` = h.`id_user`
SET h.`lihat` = 1, h.`tambah` = 1, h.`edit` = 1, h.`hapus` = 1
WHERE m.`route_name` = 'rekap-kpr.index';

INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT u.`id`, m.`id`, 1, 0, 1, 1, 1
FROM `users` u
CROSS JOIN `menu` m
WHERE m.`route_name` = 'rekap-kpr.index'
AND NOT EXISTS (
    SELECT 1 FROM `hak_akses` h WHERE h.`id_user` = u.`id` AND h.`id_menu` = m.`id`
);
COMMIT;

-- Verifikasi: seluruh user harus memiliki lihat/tambah/edit/hapus = 1.
SELECT u.`username`, h.`lihat`, h.`tambah`, h.`edit`, h.`hapus`
FROM `users` u
CROSS JOIN `menu` m
LEFT JOIN `hak_akses` h ON h.`id_user` = u.`id` AND h.`id_menu` = m.`id`
WHERE m.`route_name` = 'rekap-kpr.index'
ORDER BY u.`username`;
-- Login ulang agar menu dalam session diperbarui.

-- 28 November 2026: status manual siteplan dan warna (akses khusus username Safira pada kode).
SET @siteplan_status_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kavling_peta' AND COLUMN_NAME = 'siteplan_status') = 0,
 'ALTER TABLE `kavling_peta` ADD COLUMN `siteplan_status` VARCHAR(10) NULL', 'SELECT 1');
PREPARE siteplan_stmt FROM @siteplan_status_sql;
EXECUTE siteplan_stmt;
DEALLOCATE PREPARE siteplan_stmt;
CREATE TABLE IF NOT EXISTS `siteplan_sales_colors` (`status` VARCHAR(10) NOT NULL PRIMARY KEY, `color` VARCHAR(7) NOT NULL);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_28_030000_add_siteplan_sales_status', (SELECT COALESCE(MAX(m.batch), 0) + 1 FROM `migrations` m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_28_030000_add_siteplan_sales_status');
