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
