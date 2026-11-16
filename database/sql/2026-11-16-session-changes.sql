-- Update sesi 16 November 2026: pembatalan SPK, rekening hutang, bukti bayar SPK.
-- MySQL / MariaDB: pilih database aplikasi sebelum menjalankan SQL ini.
-- Prasyarat: tabel spk, spk_pembayaran, hutang, dan migrations sudah tersedia.
-- Kolom yang sudah ada dilewati; data dan riwayat pembayaran tetap tersimpan.

SET @session_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

-- spk.dibatalkan_pada
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'spk' AND COLUMN_NAME = 'dibatalkan_pada') = 0,
    'ALTER TABLE `spk` ADD COLUMN `dibatalkan_pada` TIMESTAMP NULL DEFAULT NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_010000_add_spk_cancellation', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_010000_add_spk_cancellation');

-- hutang.no_rekening
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hutang' AND COLUMN_NAME = 'no_rekening') = 0,
    'ALTER TABLE `hutang` ADD COLUMN `no_rekening` VARCHAR(100) NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_020000_add_no_rekening_to_hutang', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_020000_add_no_rekening_to_hutang');

-- hutang.transaksi_pembayaran
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hutang' AND COLUMN_NAME = 'transaksi_pembayaran') = 0,
    'ALTER TABLE `hutang` ADD COLUMN `transaksi_pembayaran` VARCHAR(20) NOT NULL DEFAULT ''transfer''',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_030000_add_transaksi_pembayaran_to_hutang', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_030000_add_transaksi_pembayaran_to_hutang');

-- spk_pembayaran.bukti_bayar
SET @session_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'spk_pembayaran' AND COLUMN_NAME = 'bukti_bayar') = 0,
    'ALTER TABLE `spk_pembayaran` ADD COLUMN `bukti_bayar` VARCHAR(255) NULL',
    'SELECT 1');
PREPARE session_stmt FROM @session_sql;
EXECUTE session_stmt;
DEALLOCATE PREPARE session_stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_040000_add_spk_payment_proof', @session_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_16_040000_add_spk_payment_proof');
