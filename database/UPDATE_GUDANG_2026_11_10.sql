-- Pembaruan Gudang, 10 November 2026
-- Pilih database aplikasi di phpMyAdmin sebelum menjalankan.
-- Stok master baru = 0; data transaksi project referensi tidak disalin.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `satuan` (`id` bigint unsigned not null auto_increment primary key, `nama_satuan` varchar(255) null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `supplier` (`id` bigint unsigned not null auto_increment primary key, `kode` varchar(50) null, `nama` varchar(255) null, `no_telp` varchar(50) null, `alamat` text null, `keterangan` text null, `status` tinyint unsigned not null default '1') default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang` (`id` bigint unsigned not null auto_increment primary key, `sku` varchar(100) null, `nama` varchar(255) null, `id_satuan` bigint unsigned null, `stok_awal` int not null default '0', `stok_minimal` int not null default '0', `stok` int not null default '0', `harga_beli` bigint unsigned not null default '0', `deskripsi` text null, index `barang_id_satuan_index`(`id_satuan`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_masuk` (`id` bigint unsigned not null auto_increment primary key, `id_po` bigint unsigned null, `id_supplier` bigint unsigned null, `tanggal` date null, `nama_penerima` varchar(255) null, `total_harga` bigint unsigned not null default '0', `jum_item` int unsigned not null default '0', `terbayar` bigint unsigned not null default '0', `status` tinyint unsigned not null default '0', index `barang_masuk_id_supplier_index`(`id_supplier`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_masuk_detail` (`id` bigint unsigned not null auto_increment primary key, `id_masuk` bigint unsigned null, `id_barang` bigint unsigned null, `jumlah` int unsigned not null default '0', `harga_beli` bigint unsigned not null default '0', `sub_total` bigint unsigned not null default '0', `keterangan` text null, index `barang_masuk_detail_id_masuk_index`(`id_masuk`), index `barang_masuk_detail_id_barang_index`(`id_barang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_masuk_lampiran` (`id` bigint unsigned not null auto_increment primary key, `id_barang_masuk` bigint unsigned null, `file_name` varchar(255) null, `file_original_name` varchar(255) null, `file_extension` varchar(10) null, index `barang_masuk_lampiran_id_barang_masuk_index`(`id_barang_masuk`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_masuk_pembayaran` (`id` bigint unsigned not null auto_increment primary key, `id_barang_masuk` bigint unsigned null, `tanggal_pembayaran` date null, `jumlah_bayar` bigint unsigned not null default '0', `keterangan` text null, `bukti_bayar` varchar(255) null, `bukti_bayar_original_name` varchar(255) null, `bukti_bayar_extension` varchar(10) null, index `barang_masuk_pembayaran_id_barang_masuk_index`(`id_barang_masuk`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `tukang` (`id` bigint unsigned not null auto_increment primary key, `nama` varchar(255) null, `alamat` text null, `no_telp` varchar(50) null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `master_project` (`id` bigint unsigned not null auto_increment primary key, `jenis_proyek` varchar(255) null, `jenis_pekerjaan` varchar(30) not null default 'pembangunan_unit') default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `master_project_pekerjaan` (`id` bigint unsigned not null auto_increment primary key, `id_master_project` bigint unsigned null, `nama_pekerjaan` varchar(255) null, index `master_project_pekerjaan_id_master_project_index`(`id_master_project`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `master_project_detail` (`id` bigint unsigned not null auto_increment primary key, `id_master_project` bigint unsigned null, `id_master_project_pekerjaan` bigint unsigned null, `tipe` varchar(20) null, `id_barang` bigint unsigned null, `nama_item` varchar(255) null, `satuan` varchar(255) null, `qty` decimal(18, 2) not null default '0', `harga_satuan` bigint unsigned not null default '0', `sub_total` bigint unsigned not null default '0', index `master_project_detail_id_master_project_index`(`id_master_project`), index `master_project_detail_id_master_project_pekerjaan_index`(`id_master_project_pekerjaan`), index `master_project_detail_id_barang_index`(`id_barang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `setting_proyek` (`id` bigint unsigned not null auto_increment primary key, `id_lokasi` bigint unsigned null, `id_kavling` bigint unsigned null, `id_master_project` bigint unsigned null, `id_tukang` bigint unsigned null, `status` tinyint unsigned not null default '1', index `setting_proyek_id_lokasi_index`(`id_lokasi`), unique `setting_proyek_id_kavling_unique`(`id_kavling`), index `setting_proyek_id_master_project_index`(`id_master_project`), index `setting_proyek_id_tukang_index`(`id_tukang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_keluar` (`id` bigint unsigned not null auto_increment primary key, `tanggal` date null, `id_project` bigint unsigned null, `id_lokasi` bigint unsigned null, `id_kavling` bigint unsigned null, `id_tukang` bigint unsigned null, `keterangan` text null, `lampiran` varchar(255) not null default '', index `barang_keluar_id_project_index`(`id_project`), index `barang_keluar_id_lokasi_index`(`id_lokasi`), index `barang_keluar_id_kavling_index`(`id_kavling`), index `barang_keluar_id_tukang_index`(`id_tukang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `barang_keluar_detail` (`id` bigint unsigned not null auto_increment primary key, `id_barang_keluar` bigint unsigned null, `id_barang` bigint unsigned null, `id_master_project_pekerjaan` bigint unsigned null, `jumlah` int unsigned not null default '0', `keterangan` text null, index `barang_keluar_detail_id_barang_keluar_index`(`id_barang_keluar`), index `barang_keluar_detail_id_barang_index`(`id_barang`), index `barang_keluar_detail_id_master_project_pekerjaan_index`(`id_master_project_pekerjaan`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `mutasi_stok` (`id` bigint unsigned not null auto_increment primary key, `tanggal` date null, `id_barang_masuk` bigint unsigned null, `id_barang_keluar` bigint unsigned null, `id_realisasi_bahan` bigint unsigned null, `id_barang` bigint unsigned null, `jumlah` int not null default '0', `stok_sisa` int not null default '0', index `mutasi_stok_id_barang_masuk_index`(`id_barang_masuk`), index `mutasi_stok_id_barang_keluar_index`(`id_barang_keluar`), index `mutasi_stok_id_barang_index`(`id_barang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `history_barang` (`id` bigint unsigned not null auto_increment primary key, `tanggal` date null, `id_barang` bigint unsigned null, `id_barang_masuk` bigint unsigned null, `id_barang_keluar` bigint unsigned null, `id_mutasi_stok` bigint unsigned null, `tipe` varchar(20) null, `masuk` int not null default '0', `keluar` int not null default '0', `stok` int not null default '0', index `history_barang_id_barang_index`(`id_barang`), index `history_barang_id_barang_masuk_index`(`id_barang_masuk`), index `history_barang_id_barang_keluar_index`(`id_barang_keluar`), index `history_barang_id_mutasi_stok_index`(`id_mutasi_stok`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `log_harga_barang` (`id` bigint unsigned not null auto_increment primary key, `id_barang` bigint unsigned null, `id_barang_masuk` bigint unsigned null, `id_user` bigint unsigned null, `proses` varchar(20) null, `stok_sebelum` int not null default '0', `harga_sebelum` bigint not null default '0', `qty_lama` int null, `harga_lama` bigint null, `qty_baru` int null, `harga_baru_input` bigint null, `stok_sesudah` int not null default '0', `harga_sesudah` bigint not null default '0', `keterangan` text null, `created_at` timestamp null default CURRENT_TIMESTAMP, index `log_harga_barang_id_barang_index`(`id_barang`), index `log_harga_barang_id_barang_masuk_index`(`id_barang_masuk`), index `log_harga_barang_id_user_index`(`id_user`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `pengajuan_barang` (`id` bigint unsigned not null auto_increment primary key, `tanggal` date null, `id_supplier` bigint unsigned null, `total_harga` bigint unsigned not null default '0', `jum_item` int unsigned not null default '0', `status` tinyint unsigned not null default '0', `keterangan` text null, index `pengajuan_barang_id_supplier_index`(`id_supplier`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

CREATE TABLE IF NOT EXISTS `pengajuan_barang_detail` (`id` bigint unsigned not null auto_increment primary key, `id_pengajuan` bigint unsigned null, `id_barang` bigint unsigned null, `jumlah` int unsigned not null default '0', `harga_beli` bigint unsigned not null default '0', `sub_total` bigint unsigned not null default '0', `keterangan` text null, index `pengajuan_barang_detail_id_pengajuan_index`(`id_pengajuan`), index `pengajuan_barang_detail_id_barang_index`(`id_barang`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

-- Tambahkan kategori lampiran jika belum ada (aman dijalankan ulang).
SET @inventory_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'barang_masuk_lampiran' AND COLUMN_NAME = 'jenis_lampiran') = 0, 'ALTER TABLE `barang_masuk_lampiran` ADD COLUMN `jenis_lampiran` VARCHAR(30) NULL', 'SELECT 1');
PREPARE inventory_stmt FROM @inventory_ddl;
EXECUTE inventory_stmt;
DEALLOCATE PREPARE inventory_stmt;

START TRANSACTION;

-- Hapus tiga menu yang tidak dipakai beserta hak aksesnya; tabel dan data tetap ada.
CREATE TEMPORARY TABLE inventory_removed_menus (`id` BIGINT UNSIGNED PRIMARY KEY);
INSERT INTO inventory_removed_menus (`id`)
SELECT `id` FROM `menu` WHERE `route_name` IN ('master-project.index','setting-proyek.index','pengajuan-barang.index');
DELETE a FROM `hak_akses` a JOIN inventory_removed_menus r ON r.id = a.id_menu;
DELETE a FROM `role_user` a JOIN inventory_removed_menus r ON r.id = a.id_menu;
DELETE m FROM `menu` m JOIN inventory_removed_menus r ON r.id = m.id;
DROP TEMPORARY TABLE inventory_removed_menus;

-- Menu akhir: Barang Masuk, Barang Keluar, Gudang dan submenunya.
SET @inventory_order = (SELECT COALESCE(MAX(urutan), 0) + 1 FROM menu WHERE id_parent = 0);
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT 0,'Gudang','#','fas fa-warehouse',@inventory_order,1,0,0,0
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE id_parent = 0 AND title = 'Gudang');
SET @inventory_parent = (SELECT id FROM menu WHERE id_parent = 0 AND title = 'Gudang' ORDER BY id LIMIT 1);
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT 0,'Barang Masuk','barang-masuk.index','fas fa-truck-loading',@inventory_order + 1,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'barang-masuk.index');
UPDATE menu SET id_parent=0, title='Barang Masuk', icon='fas fa-truck-loading', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='barang-masuk.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT 0,'Barang Keluar','barang-keluar.index','fas fa-dolly',@inventory_order + 2,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'barang-keluar.index');
UPDATE menu SET id_parent=0, title='Barang Keluar', icon='fas fa-dolly', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='barang-keluar.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Supplier','supplier.index','fas fa-truck',1,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'supplier.index');
UPDATE menu SET id_parent=@inventory_parent, title='Supplier', icon='fas fa-truck', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='supplier.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Satuan','satuan.index','fas fa-ruler-combined',2,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'satuan.index');
UPDATE menu SET id_parent=@inventory_parent, title='Satuan', icon='fas fa-ruler-combined', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='satuan.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Barang','barang.index','fas fa-box-open',3,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'barang.index');
UPDATE menu SET id_parent=@inventory_parent, title='Barang', icon='fas fa-box-open', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='barang.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Riwayat Mutasi','riwayat-mutasi.index','fas fa-exchange-alt',4,1,0,0,0 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'riwayat-mutasi.index');
UPDATE menu SET id_parent=@inventory_parent, title='Riwayat Mutasi', icon='fas fa-exchange-alt', lihat=1,tambah=0,edit=0,hapus=0 WHERE route_name='riwayat-mutasi.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Tukang','tukang.index','fas fa-hard-hat',5,1,1,1,1 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'tukang.index');
UPDATE menu SET id_parent=@inventory_parent, title='Tukang', icon='fas fa-hard-hat', lihat=1,tambah=1,edit=1,hapus=1 WHERE route_name='tukang.index';
INSERT INTO menu (id_parent,title,route_name,icon,urutan,lihat,tambah,edit,hapus)
SELECT @inventory_parent,'Stok Barang','stok-barang.index','fas fa-boxes',8,1,0,0,0 WHERE NOT EXISTS (SELECT 1 FROM menu WHERE route_name = 'stok-barang.index');
UPDATE menu SET id_parent=@inventory_parent, title='Stok Barang', icon='fas fa-boxes', lihat=1,tambah=0,edit=0,hapus=0 WHERE route_name='stok-barang.index';

-- Beri akses awal hanya kepada administrator yang sudah mengelola Hak Akses.
INSERT INTO `hak_akses` (`id_user`,id_menu,lihat,beranda,tambah,edit,hapus)
SELECT DISTINCT a.`id_user`,m.id,1,0,m.tambah,m.edit,m.hapus
FROM `hak_akses` a JOIN menu permission ON permission.id=a.id_menu
JOIN menu m ON (m.id=@inventory_parent OR m.route_name IN ('barang-masuk.index','barang-keluar.index','supplier.index','satuan.index','barang.index','riwayat-mutasi.index','tukang.index','stok-barang.index'))
WHERE permission.route_name='hak-akses.index' AND a.lihat=1 AND a.edit=1
AND NOT EXISTS (SELECT 1 FROM `hak_akses` existing_access WHERE existing_access.`id_user`=a.`id_user` AND existing_access.id_menu=m.id);
INSERT INTO `role_user` (`id_role`,id_menu,lihat,beranda,tambah,edit,hapus)
SELECT DISTINCT a.`id_role`,m.id,1,0,m.tambah,m.edit,m.hapus
FROM `role_user` a JOIN menu permission ON permission.id=a.id_menu
JOIN menu m ON (m.id=@inventory_parent OR m.route_name IN ('barang-masuk.index','barang-keluar.index','supplier.index','satuan.index','barang.index','riwayat-mutasi.index','tukang.index','stok-barang.index'))
WHERE permission.route_name='hak-akses.index' AND a.lihat=1 AND a.edit=1
AND NOT EXISTS (SELECT 1 FROM `role_user` existing_access WHERE existing_access.`id_role`=a.`id_role` AND existing_access.id_menu=m.id);

-- 18 satuan dari project referensi.
INSERT INTO satuan (nama_satuan) SELECT 'Pcs' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Pcs');
INSERT INTO satuan (nama_satuan) SELECT 'Set' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Set');
INSERT INTO satuan (nama_satuan) SELECT 'Unit' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Unit');
INSERT INTO satuan (nama_satuan) SELECT 'Sak' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Sak');
INSERT INTO satuan (nama_satuan) SELECT 'Kaleng' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Kaleng');
INSERT INTO satuan (nama_satuan) SELECT 'Kilo' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Kilo');
INSERT INTO satuan (nama_satuan) SELECT 'Truk' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Truk');
INSERT INTO satuan (nama_satuan) SELECT 'Pickup' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Pickup');
INSERT INTO satuan (nama_satuan) SELECT 'Batang' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Batang');
INSERT INTO satuan (nama_satuan) SELECT 'Keping' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Keping');
INSERT INTO satuan (nama_satuan) SELECT 'ember' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='ember');
INSERT INTO satuan (nama_satuan) SELECT 'Dus' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Dus');
INSERT INTO satuan (nama_satuan) SELECT 'Kotak' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Kotak');
INSERT INTO satuan (nama_satuan) SELECT 'Bungkus' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Bungkus');
INSERT INTO satuan (nama_satuan) SELECT 'Roll' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Roll');
INSERT INTO satuan (nama_satuan) SELECT 'Gulung' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Gulung');
INSERT INTO satuan (nama_satuan) SELECT 'Kodi' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Kodi');
INSERT INTO satuan (nama_satuan) SELECT 'Lembar' WHERE NOT EXISTS (SELECT 1 FROM satuan WHERE nama_satuan='Lembar');

-- 77 barang: stok awal dan stok saat ini NOL. Barang yang sudah ada tidak ditimpa.
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Semen hitam',(SELECT id FROM satuan WHERE nama_satuan='Sak' ORDER BY id LIMIT 1),0,5,0,3238,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Semen hitam');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Semen putih/plamir',(SELECT id FROM satuan WHERE nama_satuan='Sak' ORDER BY id LIMIT 1),0,2,0,135000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Semen putih/plamir');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'semen kornies',(SELECT id FROM satuan WHERE nama_satuan='Sak' ORDER BY id LIMIT 1),0,5,0,78000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='semen kornies');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'semen kasting',(SELECT id FROM satuan WHERE nama_satuan='Sak' ORDER BY id LIMIT 1),0,3,0,58000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='semen kasting');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'besi cincin',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,18000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='besi cincin');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'besi 10 inch',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,55000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='besi 10 inch');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'seng 2x4',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,5,0,25000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='seng 2x4');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'perabung',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,5,0,18000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='perabung');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pintu WC',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,2,0,230000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pintu WC');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'closet',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,3,0,13000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='closet');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'lisplank',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,5,0,38000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='lisplank');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'reng baja',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,28500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='reng baja');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'kanal baja',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,5351,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='kanal baja');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'holow',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,0,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='holow');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pipa listrik',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,7000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pipa listrik');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pipa 4 inch',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,0,0,0,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pipa 4 inch');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pipa 3 inch',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,4,0,15556,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pipa 3 inch');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pipa 1½ inc',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,4,0,48000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pipa 1½ inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'pipa ½inc',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,16000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='pipa ½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'cat tembok',(SELECT id FROM satuan WHERE nama_satuan='ember' ORDER BY id LIMIT 1),0,2,0,230000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='cat tembok');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'gypsum',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,5,0,66000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='gypsum');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'keramik lantai utama',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,5,0,61000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='keramik lantai utama');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'keramik teras',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,4,0,70951,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='keramik teras');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'keramik lantai WC',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,8,0,71000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='keramik lantai WC');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'keramik dinding WC',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,5,0,70000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='keramik dinding WC');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'listmolding',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,9000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='listmolding');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lbow ½inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,3000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lbow ½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lbow 1½inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,6000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lbow 1½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sockdrat ½inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,4000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sockdrat ½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Ldrat ½inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,4000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Ldrat ½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Tee ½inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,5000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Tee ½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Tutup Dop 4inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,3500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Tutup Dop 4inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lbow 3inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,12000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lbow 3inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lbow 4inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,22000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lbow 4inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Tee 3inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,25000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Tee 3inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Kassa plafon',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,8900,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Kassa plafon');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Keran leher angsa',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,16550,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Keran leher angsa');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Floor drain',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,7500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Floor drain');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sekrup hitam 1½inc',(SELECT id FROM satuan WHERE nama_satuan='Kotak' ORDER BY id LIMIT 1),0,5,0,35000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sekrup hitam 1½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Paku beton 1½inc',(SELECT id FROM satuan WHERE nama_satuan='Kotak' ORDER BY id LIMIT 1),0,5,0,15000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Paku beton 1½inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Natt putih',(SELECT id FROM satuan WHERE nama_satuan='Bungkus' ORDER BY id LIMIT 1),0,4,0,20000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Natt putih');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lem Fox',(SELECT id FROM satuan WHERE nama_satuan='Bungkus' ORDER BY id LIMIT 1),0,3,0,26000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lem Fox');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Selang elastis',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,6,0,16000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Selang elastis');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Clear bata tempel',(SELECT id FROM satuan WHERE nama_satuan='Kaleng' ORDER BY id LIMIT 1),0,3,0,88000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Clear bata tempel');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Cat minyak hitam',(SELECT id FROM satuan WHERE nama_satuan='Kaleng' ORDER BY id LIMIT 1),0,5,0,68000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Cat minyak hitam');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Hak angin',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,2941,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Hak angin');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Engsel 3inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,3125,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Engsel 3inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Engsel 4inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,3666,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Engsel 4inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Grendel 2inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,6731,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Grendel 2inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sink/Bak cuci piring',(SELECT id FROM satuan WHERE nama_satuan='Unit' ORDER BY id LIMIT 1),0,3,0,110000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sink/Bak cuci piring');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Handle pintu besar/Utama',(SELECT id FROM satuan WHERE nama_satuan='Set' ORDER BY id LIMIT 1),0,5,0,75000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Handle pintu besar/Utama');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Handle pintu kecil',(SELECT id FROM satuan WHERE nama_satuan='Set' ORDER BY id LIMIT 1),0,5,0,58000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Handle pintu kecil');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Teedus',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,1144,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Teedus');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Mangkok listrik',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,12900,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Mangkok listrik');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Box MCB',(SELECT id FROM satuan WHERE nama_satuan='Kotak' ORDER BY id LIMIT 1),0,5,0,2200,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Box MCB');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'MCB',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,5500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='MCB');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Fitting lampu gantung',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,3350,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Fitting lampu gantung');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Fitting lampu plafon',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,1973,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Fitting lampu plafon');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sakelar stop kontak',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,10,0,83300,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sakelar stop kontak');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sakelar engkel/tunggal',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,15509,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sakelar engkel/tunggal');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Sakelar seri',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,76700,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Sakelar seri');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Isolasi listrik',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,5500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Isolasi listrik');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Lampu gapura',(SELECT id FROM satuan WHERE nama_satuan='Kotak' ORDER BY id LIMIT 1),0,5,0,68621,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Lampu gapura');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Kabel NYA 1,5mm',(SELECT id FROM satuan WHERE nama_satuan='Roll' ORDER BY id LIMIT 1),0,2,0,180000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Kabel NYA 1,5mm');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Seng 7kaki',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,5,0,44000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Seng 7kaki');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Benang',(SELECT id FROM satuan WHERE nama_satuan='Gulung' ORDER BY id LIMIT 1),0,5,0,6000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Benang');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Besi 8 ksty',(SELECT id FROM satuan WHERE nama_satuan='Batang' ORDER BY id LIMIT 1),0,5,0,55000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Besi 8 ksty');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Papan 2x20x4',(SELECT id FROM satuan WHERE nama_satuan='Keping' ORDER BY id LIMIT 1),0,0,0,32000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Papan 2x20x4');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Granit hitam 60x60',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,5,0,190000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Granit hitam 60x60');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Keran air biasa',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,7500,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Keran air biasa');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'No Drop',(SELECT id FROM satuan WHERE nama_satuan='Kaleng' ORDER BY id LIMIT 1),0,1,0,82000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='No Drop');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Alkali',(SELECT id FROM satuan WHERE nama_satuan='Kaleng' ORDER BY id LIMIT 1),0,1,0,175000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Alkali');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Dempul kayu',(SELECT id FROM satuan WHERE nama_satuan='Kaleng' ORDER BY id LIMIT 1),0,1,0,30000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Dempul kayu');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Tutup Dop 3inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,1,0,10000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Tutup Dop 3inc');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Granit brown 60x60',(SELECT id FROM satuan WHERE nama_satuan='Dus' ORDER BY id LIMIT 1),0,0,0,220000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Granit brown 60x60');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Ampelas tempel P80',(SELECT id FROM satuan WHERE nama_satuan='Lembar' ORDER BY id LIMIT 1),0,20,0,1666,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Ampelas tempel P80');
INSERT INTO barang (sku,nama,id_satuan,stok_awal,stok_minimal,stok,harga_beli,deskripsi)
SELECT NULL,'Grendel 4inc',(SELECT id FROM satuan WHERE nama_satuan='Pcs' ORDER BY id LIMIT 1),0,5,0,8000,NULL WHERE NOT EXISTS (SELECT 1 FROM barang WHERE nama='Grendel 4inc');

-- Tandai migrasi yang sudah dicakup SQL ini agar artisan migrate tidak mengulanginya.
SET @inventory_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration,batch) SELECT '2026_11_10_010000_restore_inventory_modules',@inventory_batch WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_11_10_010000_restore_inventory_modules');
INSERT INTO migrations (migration,batch) SELECT '2026_11_10_020000_simplify_inventory_menus_and_import_master',@inventory_batch WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_11_10_020000_simplify_inventory_menus_and_import_master');
INSERT INTO migrations (migration,batch) SELECT '2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran',@inventory_batch WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration='2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran');
COMMIT;

-- Verifikasi hasil
SELECT COUNT(*) AS jumlah_satuan FROM satuan;
SELECT COUNT(*) AS jumlah_barang, SUM(stok) AS total_stok FROM barang;
SELECT title,route_name FROM menu WHERE id_parent=@inventory_parent ORDER BY urutan;
