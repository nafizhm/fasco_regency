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
