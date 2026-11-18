-- Jalankan setelah 2026-11-18-session-2-manajemen-barang.sql.
-- Harga rencana pengajuan; subtotal dan total dihitung dari jumlah x harga.
SET @price_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengajuan_pembelian_detail' AND COLUMN_NAME = 'harga') = 0,
    'ALTER TABLE `pengajuan_pembelian_detail` ADD COLUMN `harga` BIGINT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1');
PREPARE price_stmt FROM @price_sql;
EXECUTE price_stmt;
DEALLOCATE PREPARE price_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000000_add_harga_to_pengajuan_pembelian_detail', next_batch.batch
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS batch FROM `migrations`) next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_19_000000_add_harga_to_pengajuan_pembelian_detail');
