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
