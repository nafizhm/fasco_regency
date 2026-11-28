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
