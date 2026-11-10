-- UPDATE SESI 5 NOVEMBER 2026 (Asia/Jakarta)
-- Jalankan pada database aplikasi yang benar, setelah backup, saat maintenance.
-- MySQL 5.7+/8.x; tanpa stored procedure, CALL, DELIMITER, atau JSON_TABLE.
-- DDL melakukan implicit COMMIT. Hentikan jika ada error; jangan gunakan --force.
-- SQL ini menggantikan empat migration 2026_11_05_* dan mencatatnya di migrations.
-- Tidak mengimpor transaksi uji atau angka deposit notaris dari gambar.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @nh_previous_timezone = @@session.time_zone;
SET time_zone = '+07:00';

-- Versi khusus phpMyAdmin: tanpa stored procedure, CALL, atau DELIMITER.
-- 1. Struktur diskon, catatan kwitansi, dan snapshot harga penjualan.
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pengajuan_hold' AND column_name='diskon'), 'DO 0', 'ALTER TABLE `pengajuan_hold` ADD COLUMN `diskon` BIGINT UNSIGNED NOT NULL DEFAULT 0');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer' AND column_name='catatan_kwitansi'), 'DO 0', 'ALTER TABLE `customer` ADD COLUMN `catatan_kwitansi` TEXT NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer' AND column_name='harga_jual_awal'), 'DO 0', 'ALTER TABLE `customer` ADD COLUMN `harga_jual_awal` BIGINT UNSIGNED NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer' AND column_name='diskon'), 'DO 0', 'ALTER TABLE `customer` ADD COLUMN `diskon` BIGINT UNSIGNED NOT NULL DEFAULT 0');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer_tempo' AND column_name='harga_jual_awal'), 'DO 0', 'ALTER TABLE `customer_tempo` ADD COLUMN `harga_jual_awal` BIGINT UNSIGNED NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer_tempo' AND column_name='diskon'), 'DO 0', 'ALTER TABLE `customer_tempo` ADD COLUMN `diskon` BIGINT UNSIGNED NOT NULL DEFAULT 0');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='customer' AND column_name='id_booking'), 'DO 0', 'ALTER TABLE `customer` ADD COLUMN `id_booking` BIGINT UNSIGNED NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pengajuan_hold' AND column_name='rincian_biaya_penjualan'), 'DO 0', 'ALTER TABLE `pengajuan_hold` ADD COLUMN `rincian_biaya_penjualan` JSON NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='customer' AND index_name='customer_id_booking_index'), 'DO 0', 'ALTER TABLE customer ADD INDEX customer_id_booking_index (id_booking)');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;

-- 2. Struktur rekening, tagihan per unit, dan mutasi deposit notaris.
CREATE TABLE IF NOT EXISTS `notary_deposit_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notary_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notary_deposit_accounts_notary_id_location_id_unique` (`notary_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notary_deposit_bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `bill_date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `items` json NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `request_token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notary_deposit_bills_request_token_unique` (`request_token`),
  KEY `notary_deposit_bills_account_id_index` (`account_id`),
  KEY `notary_deposit_bills_unit_id_index` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notary_deposit_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned NOT NULL,
  `bill_id` bigint unsigned DEFAULT NULL,
  `item_index` int unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` bigint NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reversal_of` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `request_token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notary_deposit_entries_request_token_unique` (`request_token`),
  UNIQUE KEY `notary_deposit_entries_reversal_of_unique` (`reversal_of`),
  KEY `notary_deposit_entries_account_id_entry_date_id_index` (`account_id`,`entry_date`,`id`),
  KEY `notary_deposit_entries_account_id_index` (`account_id`),
  KEY `notary_deposit_entries_bill_id_index` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membantu instalasi awal yang pernah memiliki tabel mutasi tanpa item_index.
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='notary_deposit_entries' AND column_name='item_index'), 'DO 0', 'ALTER TABLE `notary_deposit_entries` ADD COLUMN `item_index` INT UNSIGNED NULL');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;


-- 3. Menu dan hak akses. ID mengikuti server, tidak memakai ID lokal.
START TRANSACTION;
SET @nh_profile = (SELECT MIN(id) FROM menu WHERE route_name='pengaturan-profil.index');
SET @nh_settings = COALESCE(NULLIF((SELECT id_parent FROM menu WHERE id=@nh_profile),0),(SELECT MIN(id) FROM menu WHERE title='Pengaturan'));
SET @nh_expenses = (SELECT MIN(id) FROM menu WHERE route_name='pengeluaran.index');
SET @nh_finance = COALESCE(NULLIF((SELECT id_parent FROM menu WHERE id=@nh_expenses),0),(SELECT MIN(id) FROM menu WHERE title='Keuangan'));
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @nh_settings,'Catatan Kwitansi Customer','customer-receipt-notes.index','fas fa-file-alt',s.n,1,0,1,0
FROM (SELECT COALESCE(MAX(urutan),0)+1 AS n FROM menu WHERE id_parent=@nh_settings) s
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name='customer-receipt-notes.index');
SET @nh_notes_menu = (SELECT MIN(id) FROM menu WHERE route_name='customer-receipt-notes.index');
INSERT INTO hak_akses (id_user,id_menu,lihat,beranda,tambah,edit,hapus)
SELECT a.id_user,@nh_notes_menu,a.lihat,0,0,a.edit,0 FROM hak_akses a
WHERE a.id_menu=@nh_profile AND NOT EXISTS (SELECT 1 FROM hak_akses b WHERE b.id_menu=@nh_notes_menu AND b.id_user=a.id_user);
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @nh_finance,'Deposit Notaris','notary-deposits.index','fas fa-wallet',s.n,1,1,1,1
FROM (SELECT COALESCE(MAX(urutan),0)+1 AS n FROM menu WHERE id_parent=@nh_finance) s
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name='notary-deposits.index');
SET @nh_deposit_menu = (SELECT MIN(id) FROM menu WHERE route_name='notary-deposits.index');
INSERT INTO hak_akses (id_user,id_menu,lihat,beranda,tambah,edit,hapus)
SELECT a.id_user,@nh_deposit_menu,a.lihat,0,a.tambah,a.edit,a.hapus FROM hak_akses a
WHERE a.id_menu=@nh_expenses AND NOT EXISTS (SELECT 1 FROM hak_akses b WHERE b.id_menu=@nh_deposit_menu AND b.id_user=a.id_user);
INSERT INTO role_user (id_role,id_menu,lihat,beranda,tambah,edit,hapus)
SELECT a.id_role,@nh_deposit_menu,a.lihat,0,a.tambah,a.edit,a.hapus FROM role_user a
WHERE a.id_menu=@nh_expenses AND NOT EXISTS (SELECT 1 FROM role_user b WHERE b.id_menu=@nh_deposit_menu AND b.id_role=a.id_role);
COMMIT;

-- 4. Isi metadata harga hanya bila belum pernah diinisialisasi.
-- Harga/diskon/catatan khusus yang telah disimpan di server tidak ditimpa.
DROP TEMPORARY TABLE IF EXISTS nh_20261105_booking_links;
CREATE TEMPORARY TABLE nh_20261105_booking_links AS
SELECT c.id AS customer_id,COUNT(h.id) AS matched,MIN(h.id) AS booking_id,MAX(h.diskon) AS discount
FROM customer c LEFT JOIN pengajuan_hold h ON h.id_kavling=c.id_kavling AND h.stt_reg=2
AND (c.nik IS NULL OR c.nik='' OR c.nik='0' OR h.nik=c.nik)
GROUP BY c.id;
START TRANSACTION;
UPDATE customer c JOIN nh_20261105_booking_links b ON b.customer_id=c.id
SET c.harga_jual_awal=GREATEST(COALESCE(c.hrg_jual,0),0)+IF(b.matched=1,COALESCE(b.discount,0),0),
    c.diskon=IF(b.matched=1,COALESCE(b.discount,0),0),
    c.id_booking=IF(b.matched=1,b.booking_id,NULL)
WHERE c.harga_jual_awal IS NULL;
UPDATE customer SET nama_lengkap=UPPER(nama_lengkap) WHERE nama_lengkap IS NOT NULL;
COMMIT;
DROP TEMPORARY TABLE nh_20261105_booking_links;

-- 5. Piutang hanya untuk customer aktif yang belum mempunyai tagihan.
-- Semua tabel pembantu TEMPORARY, tidak menulis transaksi pembayaran.
START TRANSACTION;
DROP TEMPORARY TABLE IF EXISTS nh_new_customers;
CREATE TEMPORARY TABLE nh_new_customers AS
SELECT c.id AS customer_id, COALESCE(c.total_harga,0) AS amount,k.kode_kavling,
 CASE WHEN c.tgl_booking>='1000-01-01' THEN DATE(c.tgl_booking)
 WHEN c.tanggal_verif>='1000-01-01' THEN DATE(c.tanggal_verif) ELSE CURDATE() END AS bill_date,
 k.rincian_biaya AS costs,COALESCE(k.hrg_jual,0)+COALESCE(k.biaya_surat,0)+COALESCE(k.peningkatan_mutu,0) AS legacy_amount
FROM customer c JOIN kavling_peta k ON k.id=c.id_kavling
WHERE COALESCE(c.stt_arsip,0)=0 AND c.id_kavling>0
 AND NOT EXISTS(SELECT 1 FROM piutang p WHERE p.id_customer=c.id);

-- Deret indeks JSON dibangun tanpa JSON_TABLE agar kompatibel MySQL 5.7.
SET @nh_max_costs = (SELECT COALESCE(MAX(JSON_LENGTH(CASE WHEN JSON_VALID(costs) THEN costs ELSE '[]' END)),0) FROM nh_new_customers WHERE amount<=0);
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers;
CREATE TEMPORARY TABLE nh_cost_numbers(n INT UNSIGNED PRIMARY KEY);
INSERT INTO nh_cost_numbers VALUES(0);
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+1 FROM nh_cost_numbers_copy WHERE n+1<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+2 FROM nh_cost_numbers_copy WHERE n+2<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+4 FROM nh_cost_numbers_copy WHERE n+4<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+8 FROM nh_cost_numbers_copy WHERE n+8<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+16 FROM nh_cost_numbers_copy WHERE n+16<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+32 FROM nh_cost_numbers_copy WHERE n+32<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+64 FROM nh_cost_numbers_copy WHERE n+64<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+128 FROM nh_cost_numbers_copy WHERE n+128<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+256 FROM nh_cost_numbers_copy WHERE n+256<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+512 FROM nh_cost_numbers_copy WHERE n+512<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+1024 FROM nh_cost_numbers_copy WHERE n+1024<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+2048 FROM nh_cost_numbers_copy WHERE n+2048<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+4096 FROM nh_cost_numbers_copy WHERE n+4096<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+8192 FROM nh_cost_numbers_copy WHERE n+8192<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+16384 FROM nh_cost_numbers_copy WHERE n+16384<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+32768 FROM nh_cost_numbers_copy WHERE n+32768<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+65536 FROM nh_cost_numbers_copy WHERE n+65536<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+131072 FROM nh_cost_numbers_copy WHERE n+131072<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+262144 FROM nh_cost_numbers_copy WHERE n+262144<@nh_max_costs;
DROP TEMPORARY TABLE IF EXISTS nh_cost_numbers_copy;
CREATE TEMPORARY TABLE nh_cost_numbers_copy AS SELECT n FROM nh_cost_numbers;
INSERT INTO nh_cost_numbers SELECT n+524288 FROM nh_cost_numbers_copy WHERE n+524288<@nh_max_costs;
DROP TEMPORARY TABLE nh_cost_numbers_copy;
DROP TEMPORARY TABLE IF EXISTS nh_unit_costs;
CREATE TEMPORARY TABLE nh_unit_costs AS
SELECT c.customer_id,SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(CASE WHEN JSON_VALID(c.costs) THEN c.costs ELSE '[]' END,CONCAT('$[',n.n,'].nilai'))) AS SIGNED),0)) AS amount
FROM nh_new_customers c JOIN nh_cost_numbers n ON n.n<JSON_LENGTH(CASE WHEN JSON_VALID(c.costs) THEN c.costs ELSE '[]' END)
WHERE c.amount<=0 GROUP BY c.customer_id;
UPDATE nh_new_customers c LEFT JOIN nh_unit_costs u ON u.customer_id=c.customer_id
SET c.amount=CASE WHEN c.costs IS NULL OR JSON_VALID(c.costs)=0 THEN c.legacy_amount ELSE COALESCE(u.amount,0) END
WHERE c.amount<=0;
INSERT INTO piutang(id_customer,id_bank,tanggal_piutang,deskripsi,nominal,lampiran,status,terbayar,sisa_bayar,tgl_pelunasan,id_kategori_transaksi)
SELECT c.customer_id,0,c.bill_date,CONCAT('Harga Rumah Blok ',COALESCE(c.kode_kavling,'')),c.amount,'',1,0,c.amount,NULL,0
FROM nh_new_customers c WHERE c.amount>0
 AND NOT EXISTS(SELECT 1 FROM piutang p WHERE p.id_customer=c.customer_id);
DROP TEMPORARY TABLE IF EXISTS nh_new_bills;
CREATE TEMPORARY TABLE nh_new_bills AS
SELECT p.id,p.id_customer,p.nominal FROM piutang p JOIN nh_new_customers c ON c.customer_id=p.id_customer WHERE c.amount>0;
DROP TEMPORARY TABLE IF EXISTS nh_payments;
CREATE TEMPORARY TABLE nh_payments AS
SELECT b.id AS bill_id,b.nominal,p.id AS payment_id,GREATEST(COALESCE(p.nominal,0),0) AS amount,DATE(p.tanggal) AS paid_date,
 CASE WHEN p.id_piutang=b.id THEN 0 ELSE 1 END AS priority
FROM nh_new_bills b JOIN pemasukan p ON (p.id_customer=b.id_customer OR p.id_piutang=b.id)
WHERE (p.keterangan IS NULL OR p.keterangan NOT LIKE 'Biaya ganti nama%')
AND (p.id_piutang IS NULL OR p.id_piutang=0 OR p.id_piutang=b.id);
DROP TEMPORARY TABLE IF EXISTS nh_payments_copy;
CREATE TEMPORARY TABLE nh_payments_copy AS SELECT * FROM nh_payments;
DROP TEMPORARY TABLE IF EXISTS nh_allocations;
CREATE TEMPORARY TABLE nh_allocations AS
SELECT p.bill_id,p.payment_id,p.paid_date,
 LEAST(p.amount,GREATEST(0,p.nominal-COALESCE(SUM(q.amount),0))) AS allocated
FROM nh_payments p LEFT JOIN nh_payments_copy q ON q.bill_id=p.bill_id AND
 (q.priority<p.priority OR (q.priority=p.priority AND
 (COALESCE(q.paid_date,'1000-01-01')<COALESCE(p.paid_date,'1000-01-01') OR
 (COALESCE(q.paid_date,'1000-01-01')=COALESCE(p.paid_date,'1000-01-01') AND q.payment_id<p.payment_id))))
GROUP BY p.bill_id,p.payment_id,p.paid_date,p.amount,p.nominal;
DROP TEMPORARY TABLE IF EXISTS nh_paid_totals;
CREATE TEMPORARY TABLE nh_paid_totals AS
SELECT bill_id,SUM(allocated) AS paid,MAX(CASE WHEN allocated>0 THEN paid_date ELSE NULL END) AS last_paid
FROM nh_allocations GROUP BY bill_id;
UPDATE piutang p JOIN nh_new_bills b ON b.id=p.id LEFT JOIN nh_paid_totals t ON t.bill_id=p.id
SET p.terbayar=COALESCE(t.paid,0),p.sisa_bayar=GREATEST(0,p.nominal-COALESCE(t.paid,0)),
 p.status=IF(p.nominal<=COALESCE(t.paid,0),2,1),p.tgl_pelunasan=IF(p.nominal<=COALESCE(t.paid,0),t.last_paid,NULL);
DROP TEMPORARY TABLE nh_paid_totals;
DROP TEMPORARY TABLE nh_allocations;
DROP TEMPORARY TABLE nh_payments_copy;
DROP TEMPORARY TABLE nh_payments;
DROP TEMPORARY TABLE nh_new_bills;
DROP TEMPORARY TABLE nh_unit_costs;
DROP TEMPORARY TABLE nh_cost_numbers;
DROP TEMPORARY TABLE nh_new_customers;
COMMIT;

-- 6. Kosongkan hanya keterangan akad lama yang benar-benar dibersihkan pada sesi ini.
-- Guard ID + teks lama mencegah penimpaan catatan baru yang telah diubah di server.
START TRANSACTION;
UPDATE `akad` SET keterangan='' WHERE id=3 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732036 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=4 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732037 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=5 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732038 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=6 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732039 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=7 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203130 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=8 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203131 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=9 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203132 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=10 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203133 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=11 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203134 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=12 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203135 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=13 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203136 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=14 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203137 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=15 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203230 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=16 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203231 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=17 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203232 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=18 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203233 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=19 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203234 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=20 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203235 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=21 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203236 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=22 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732032373b2074616e6767616c20626f6f6b696e672f616b61642062656c756d207465727365646961 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=23 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203238 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=24 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203331 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=25 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203332 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=26 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203333 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=27 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203334 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=28 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203335 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=29 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203336 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=30 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203337 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=31 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203338 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=32 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203339 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=33 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203430 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=34 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203431 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=35 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203434 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=36 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203435 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=37 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203436 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=38 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203437 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=39 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203438 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=40 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203439 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=41 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203530 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=42 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203531 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=43 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203532 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=44 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203533 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=45 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203534 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=46 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203535 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=47 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203538 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=48 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203539 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=49 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203630 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=50 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203631 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=51 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203632 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=52 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203633 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=53 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203636 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=54 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203637 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=55 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203638 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=56 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203639 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=57 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203730 USING utf8mb4);
UPDATE `akad` SET keterangan='' WHERE id=58 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203731 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=2 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732036 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=3 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732037 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=4 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732038 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=5 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732039 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=6 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203130 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=7 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203131 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=8 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203132 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=9 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203133 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=10 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203134 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=11 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203135 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=12 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203136 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=13 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203137 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=14 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203230 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=15 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203231 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=16 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203232 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=17 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203233 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=18 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203234 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=19 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203235 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=20 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203236 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=21 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c2062617269732032373b2074616e6767616c20626f6f6b696e672f616b61642062656c756d207465727365646961 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=22 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203238 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=23 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203331 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=24 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203332 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=25 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203333 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=26 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203334 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=27 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203335 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=28 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203336 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=29 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203337 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=30 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203338 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=31 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203339 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=32 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203430 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=33 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203431 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=34 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203434 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=35 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203435 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=36 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203436 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=37 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203437 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=38 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203438 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=39 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203439 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=40 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203530 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=41 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203531 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=42 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203532 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=43 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203533 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=44 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203534 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=45 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203535 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=46 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203538 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=47 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203539 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=48 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203630 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=49 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203631 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=50 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203632 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=51 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203633 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=52 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203636 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=53 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203637 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=54 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203638 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=55 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203639 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=56 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203730 USING utf8mb4);
UPDATE `detail_akad` SET keterangan='' WHERE id=57 AND BINARY keterangan=BINARY CONVERT(0x496d706f72206461746120686973746f72697320457863656c2c206261726973203731 USING utf8mb4);
COMMIT;

-- 7. Tandai migration selesai agar artisan migrate berikutnya tidak menambah kolom/menu lagi.
SET @nh_batch = (SELECT COALESCE(MAX(batch),0)+1 FROM migrations);
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_100000_add_diskon_to_pengajuan_hold',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_100000_add_diskon_to_pengajuan_hold');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_110000_add_customer_receipt_notes',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_110000_add_customer_receipt_notes');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_120000_add_customer_sale_pricing',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_120000_add_customer_sale_pricing');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_130000_create_notary_deposits',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_130000_create_notary_deposits');

-- 8. Pemeriksaan dipisahkan agar impor tidak menghasilkan result set.
-- Sesudah impor selesai, jalankan check_update_session_2026_11_05.sql secara terpisah.
SET time_zone = @nh_previous_timezone;
