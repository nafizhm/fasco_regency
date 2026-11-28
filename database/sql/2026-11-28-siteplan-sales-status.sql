
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
