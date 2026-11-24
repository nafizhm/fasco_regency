-- Ganti nama menu menjadi Purchase Order. Route dan hak akses tetap.
UPDATE `menu` SET `title` = 'Purchase Order' WHERE `route_name` = 'pengajuan-pembelian.index';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_24_000000_rename_purchase_order_menu', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_24_000000_rename_purchase_order_menu');
