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

