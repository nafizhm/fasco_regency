<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$header= <<<'SQL'
-- UPDATE SESI 5 NOVEMBER 2026 (Asia/Jakarta)
-- Jalankan pada database aplikasi yang benar, setelah backup, saat maintenance.
-- MySQL 5.7+/8.x; tidak memakai JSON_TABLE atau ADD COLUMN IF NOT EXISTS.
-- DDL melakukan implicit COMMIT. Hentikan jika ada error; jangan gunakan --force.
-- SQL ini menggantikan empat migration 2026_11_05_* dan mencatatnya di migrations.
-- Tidak mengimpor transaksi uji atau angka deposit notaris dari gambar.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @nh_previous_timezone = @@session.time_zone;
SET time_zone = '+07:00';

DELIMITER $$
DROP PROCEDURE IF EXISTS nh_20261105_require_parents$$
CREATE PROCEDURE nh_20261105_require_parents()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM menu WHERE route_name='pengaturan-profil.index' AND id_parent>0)
       AND NOT EXISTS (SELECT 1 FROM menu WHERE title='Pengaturan') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu induk Pengaturan tidak ditemukan; hentikan impor.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM menu WHERE route_name='pengeluaran.index' AND id_parent>0)
       AND NOT EXISTS (SELECT 1 FROM menu WHERE title='Keuangan') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu induk Keuangan tidak ditemukan; hentikan impor.';
    END IF;
END$$
CALL nh_20261105_require_parents()$$
DROP PROCEDURE nh_20261105_require_parents$$

DROP PROCEDURE IF EXISTS nh_20261105_column$$
CREATE PROCEDURE nh_20261105_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=p_table AND column_name=p_column) THEN
        SET @nh_ddl = CONCAT('ALTER TABLE `',p_table,'` ADD COLUMN `',p_column,'` ',p_definition);
        PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;
    END IF;
END$$
DELIMITER ;

-- 1. Struktur diskon, catatan kwitansi, dan snapshot harga penjualan.
CALL nh_20261105_column('pengajuan_hold','diskon','BIGINT UNSIGNED NOT NULL DEFAULT 0');
CALL nh_20261105_column('customer','catatan_kwitansi','TEXT NULL');
CALL nh_20261105_column('customer','harga_jual_awal','BIGINT UNSIGNED NULL');
CALL nh_20261105_column('customer','diskon','BIGINT UNSIGNED NOT NULL DEFAULT 0');
CALL nh_20261105_column('customer_tempo','harga_jual_awal','BIGINT UNSIGNED NULL');
CALL nh_20261105_column('customer_tempo','diskon','BIGINT UNSIGNED NOT NULL DEFAULT 0');
CALL nh_20261105_column('customer','id_booking','BIGINT UNSIGNED NULL');
CALL nh_20261105_column('pengajuan_hold','rincian_biaya_penjualan','JSON NULL');
SET @nh_ddl = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='customer' AND index_name='customer_id_booking_index'), 'DO 0', 'ALTER TABLE customer ADD INDEX customer_id_booking_index (id_booking)');
PREPARE nh_stmt FROM @nh_ddl;
EXECUTE nh_stmt;
DEALLOCATE PREPARE nh_stmt;

-- 2. Struktur rekening, tagihan per unit, dan mutasi deposit notaris.
SQL;
$sql=$header."\n";
foreach(['notary_deposit_accounts','notary_deposit_bills','notary_deposit_entries'] as $t){
$r=(array)Illuminate\Support\Facades\DB::selectOne('SHOW CREATE TABLE `'.$t.'`');$ddl=$r['Create Table'];$ddl=preg_replace('/ AUTO_INCREMENT=\d+/','',$ddl);$ddl=str_replace('CREATE TABLE `','CREATE TABLE IF NOT EXISTS `',$ddl);$sql.=$ddl.";\n\n";
}
$sql.= <<<'SQL'
-- Membantu instalasi awal yang pernah memiliki tabel mutasi tanpa item_index.
CALL nh_20261105_column('notary_deposit_entries','item_index','INT UNSIGNED NULL');
DROP PROCEDURE nh_20261105_column;

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

-- 5. Buat piutang hanya untuk customer aktif dengan unit valid dan BELUM memiliki piutang.
-- Nilai mengikuti harga customer; fallback ke rincian harga unit/kolom lama.
-- Pembayaran lama dialokasikan ke tagihan baru (linked dahulu, lalu umum),
-- sesuai CustomerReceivableService + PiutangPaymentService.
DELIMITER $$
DROP PROCEDURE IF EXISTS nh_20261105_complete_receivables$$
CREATE PROCEDURE nh_20261105_complete_receivables()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_customer BIGINT; DECLARE v_total BIGINT; DECLARE v_code VARCHAR(255);
    DECLARE v_date DATE; DECLARE v_json LONGTEXT; DECLARE v_legacy BIGINT;
    DECLARE v_i INT; DECLARE v_bill BIGINT; DECLARE v_remaining BIGINT;
    DECLARE v_last_date DATE; DECLARE v_created INT DEFAULT 0;
    DECLARE customers CURSOR FOR
        SELECT c.id,COALESCE(c.total_harga,0),k.kode_kavling,
          CASE WHEN c.tgl_booking>='1000-01-01' THEN DATE(c.tgl_booking)
               WHEN c.tanggal_verif>='1000-01-01' THEN DATE(c.tanggal_verif) ELSE CURDATE() END,
          k.rincian_biaya,COALESCE(k.hrg_jual,0)+COALESCE(k.biaya_surat,0)+COALESCE(k.peningkatan_mutu,0)
        FROM customer c JOIN kavling_peta k ON k.id=c.id_kavling
        WHERE COALESCE(c.stt_arsip,0)=0 AND c.id_kavling>0
        AND NOT EXISTS(SELECT 1 FROM piutang p WHERE p.id_customer=c.id)
        ORDER BY c.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    START TRANSACTION;
    OPEN customers;
    customer_loop: LOOP
        FETCH customers INTO v_customer,v_total,v_code,v_date,v_json,v_legacy;
        IF done=1 THEN LEAVE customer_loop; END IF;
        IF v_total<=0 THEN
            IF v_json IS NULL OR JSON_VALID(v_json)=0 THEN SET v_total=v_legacy;
            ELSE
                SET v_total=0; SET v_i=0;
                WHILE v_i<JSON_LENGTH(v_json) DO
                    SET v_total=v_total+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(v_json,CONCAT('$[',v_i,'].nilai'))) AS SIGNED),0);
                    SET v_i=v_i+1;
                END WHILE;
                IF v_total<=0 THEN SET v_total=0; END IF;
            END IF;
        END IF;
        IF v_total>0 AND NOT EXISTS(SELECT 1 FROM piutang WHERE id_customer=v_customer) THEN
            INSERT INTO piutang(id_customer,id_bank,tanggal_piutang,deskripsi,nominal,lampiran,status,terbayar,sisa_bayar,tgl_pelunasan,id_kategori_transaksi)
            VALUES(v_customer,0,v_date,CONCAT('Harga Rumah Blok ',COALESCE(v_code,'')),v_total,'',1,0,v_total,NULL,0);
            SET v_bill=LAST_INSERT_ID(); SET v_remaining=v_total; SET v_last_date=NULL;
            BEGIN
                DECLARE payment_done INT DEFAULT 0; DECLARE v_amount BIGINT; DECLARE v_pay_date DATE; DECLARE v_allocate BIGINT;
                DECLARE payments CURSOR FOR
                    SELECT GREATEST(COALESCE(nominal,0),0),DATE(tanggal) FROM pemasukan
                    WHERE (id_customer=v_customer OR id_piutang=v_bill)
                    AND (keterangan IS NULL OR keterangan NOT LIKE 'Biaya ganti nama%')
                    AND (id_piutang IS NULL OR id_piutang=0 OR id_piutang=v_bill)
                    ORDER BY CASE WHEN id_piutang=v_bill THEN 0 ELSE 1 END,tanggal,id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET payment_done=1;
                OPEN payments;
                payment_loop: LOOP
                    FETCH payments INTO v_amount,v_pay_date;
                    IF payment_done=1 OR v_remaining<=0 THEN LEAVE payment_loop; END IF;
                    SET v_allocate=LEAST(v_amount,v_remaining);
                    IF v_allocate>0 THEN
                        SET v_remaining=v_remaining-v_allocate;
                        IF v_pay_date IS NOT NULL THEN SET v_last_date=IF(v_last_date IS NULL,v_pay_date,GREATEST(v_last_date,v_pay_date)); END IF;
                    END IF;
                END LOOP;
                CLOSE payments;
            END;
            UPDATE piutang SET terbayar=v_total-v_remaining,sisa_bayar=v_remaining,status=IF(v_remaining=0,2,1),
              tgl_pelunasan=IF(v_remaining=0,v_last_date,NULL) WHERE id=v_bill;
            SET v_created=v_created+1;
        END IF;
    END LOOP;
    CLOSE customers;
    COMMIT;
    SELECT v_created AS tagihan_baru_dibuat;
END$$
CALL nh_20261105_complete_receivables()$$
DROP PROCEDURE nh_20261105_complete_receivables$$
DELIMITER ;

-- 6. Kosongkan hanya keterangan akad lama yang benar-benar dibersihkan pada sesi ini.
-- Guard ID + teks lama mencegah penimpaan catatan baru yang telah diubah di server.
START TRANSACTION;
SQL;
$files=glob(storage_path('app/private/akad-keterangan-before-20261105-*.json'));sort($files);$backup=json_decode(file_get_contents($files[0]),true);
foreach(['akad','detail_akad'] as $t){foreach($backup[$t] as $r){$hex=bin2hex($r['keterangan']);$sql.="\nUPDATE `{$t}` SET keterangan='' WHERE id=".(int)$r['id']." AND BINARY keterangan=BINARY CONVERT(0x{$hex} USING utf8mb4);";}}
$sql.= <<<'SQL'

COMMIT;

-- 7. Tandai migration selesai agar artisan migrate berikutnya tidak menambah kolom/menu lagi.
SET @nh_batch = (SELECT COALESCE(MAX(batch),0)+1 FROM migrations);
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_100000_add_diskon_to_pengajuan_hold',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_100000_add_diskon_to_pengajuan_hold');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_110000_add_customer_receipt_notes',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_110000_add_customer_receipt_notes');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_120000_add_customer_sale_pricing',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_120000_add_customer_sale_pricing');
INSERT INTO migrations(migration,batch) SELECT '2026_11_05_130000_create_notary_deposits',@nh_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='2026_11_05_130000_create_notary_deposits');

-- 8. Pemeriksaan setelah impor.
SELECT migration,batch FROM migrations WHERE migration LIKE '2026_11_05_%' ORDER BY migration;
SELECT title,route_name FROM menu WHERE route_name IN('customer-receipt-notes.index','notary-deposits.index');
SELECT COUNT(*) AS customer_aktif_tanpa_piutang FROM customer c JOIN kavling_peta k ON k.id=c.id_kavling
WHERE COALESCE(c.stt_arsip,0)=0 AND c.total_harga>0 AND NOT EXISTS(SELECT 1 FROM piutang p WHERE p.id_customer=c.id);
SET time_zone = @nh_previous_timezone;
SQL;
file_put_contents(database_path('sql/update_session_2026_11_05.sql'),$sql."\n");
echo 'SQL package generated ('.strlen($sql).' bytes).'.PHP_EOL;
