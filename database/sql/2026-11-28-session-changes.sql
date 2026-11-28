-- Paket perubahan SQL sesi 28 November 2026.
-- Pengajuan Tagihan dan Invoice IPL beserta akses CRUD untuk Nita.
-- Prasyarat: database aplikasi dan tabel menu, hak_akses, users, migrations sudah tersedia.
-- Perubahan tampilan dan cetak PDF tidak membutuhkan SQL tambahan.

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
