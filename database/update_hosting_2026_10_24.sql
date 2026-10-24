-- Update Naufal Hills: kategori pembayaran dan urutan menu.
-- Pilih database aplikasi di phpMyAdmin sebelum menjalankan.
-- Dapat dijalankan ulang; kategori yang sudah tersedia tidak ditambahkan lagi.

START TRANSACTION;

INSERT INTO `kategori_transaksi` (`kategori`, `jenis_kategori`, `kode`, `stt_fix`)
SELECT 'Pembayaran Piutang', 'PEMASUKAN', 'BAYAR-PIUTANG', 1
WHERE NOT EXISTS (SELECT 1 FROM `kategori_transaksi` WHERE `kategori` = 'Pembayaran Piutang' AND `jenis_kategori` = 'PEMASUKAN');

INSERT INTO `kategori_transaksi` (`kategori`, `jenis_kategori`, `kode`, `stt_fix`)
SELECT 'Pembayaran Hutang', 'PENGELUARAN', 'BAYAR-HUTANG', 1
WHERE NOT EXISTS (SELECT 1 FROM `kategori_transaksi` WHERE `kategori` = 'Pembayaran Hutang' AND `jenis_kategori` = 'PENGELUARAN');

-- Menu utama
UPDATE `menu` SET `urutan` = CASE `title`
    WHEN 'Beranda' THEN 10
    WHEN 'Siteplan' THEN 20
    WHEN 'Transaksi' THEN 30
    WHEN 'Customer' THEN 40
    WHEN 'Keuangan' THEN 50
    WHEN 'Progres Pembangunan' THEN 60
    WHEN 'Legal' THEN 70
    WHEN 'Cetak Berkas' THEN 80
    WHEN 'Mutasi Transaksi' THEN 90
    WHEN 'Pembelian' THEN 100
    WHEN 'Laporan' THEN 110
    WHEN 'Dokumentasi Konten' THEN 120
    WHEN 'Master Data' THEN 130
    WHEN 'Pengaturan' THEN 140
    ELSE `urutan` END
WHERE `id_parent` = 0 AND `title` IN ('Beranda', 'Siteplan', 'Transaksi', 'Customer', 'Keuangan', 'Progres Pembangunan', 'Legal', 'Cetak Berkas', 'Mutasi Transaksi', 'Pembelian', 'Laporan', 'Dokumentasi Konten', 'Master Data', 'Pengaturan');

-- Siteplan
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'siteplan-penjualan.index' THEN 10
    WHEN 'siteplan-listrik.index' THEN 20
    WHEN 'siteplan-air.index' THEN 30
    WHEN 'st-bphtb-ssp.index' THEN 40
    WHEN 'st-balik-nama.index' THEN 50
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Siteplan'
  AND child.`route_name` IN ('siteplan-penjualan.index', 'siteplan-listrik.index', 'siteplan-air.index', 'st-bphtb-ssp.index', 'st-balik-nama.index');

-- Transaksi
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'pengajuan-hold.index' THEN 10
    WHEN 'wawancara.index' THEN 20
    WHEN 'acc-bank.index' THEN 30
    WHEN 'akad.index' THEN 40
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Transaksi'
  AND child.`route_name` IN ('pengajuan-hold.index', 'wawancara.index', 'acc-bank.index', 'akad.index');

-- Customer
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'customer.index' THEN 10
    WHEN 'upload-file.index' THEN 20
    WHEN 'arsip-customer.index' THEN 30
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Customer'
  AND child.`route_name` IN ('customer.index', 'upload-file.index', 'arsip-customer.index');

-- Keuangan
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'pembayaran.index' THEN 10
    WHEN 'piutang.index' THEN 20
    WHEN 'pemasukan.index' THEN 30
    WHEN 'hutang.index' THEN 40
    WHEN 'pengeluaran.index' THEN 50
    WHEN 'mutasi-saldo.index' THEN 60
    WHEN 'keuangan-retensi.index' THEN 70
    WHEN 'laporan-arus-kas.index' THEN 80
    WHEN 'kategori-transaksi.index' THEN 90
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Keuangan'
  AND child.`route_name` IN ('pembayaran.index', 'piutang.index', 'pemasukan.index', 'hutang.index', 'pengeluaran.index', 'mutasi-saldo.index', 'keuangan-retensi.index', 'laporan-arus-kas.index', 'kategori-transaksi.index');

-- Legal
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'pengajuan-berkas.index' THEN 10
    WHEN 'bphtb-ssp.index' THEN 20
    WHEN 'balik-nama.index' THEN 30
    WHEN 'listrik-air.index' THEN 40
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Legal'
  AND child.`route_name` IN ('pengajuan-berkas.index', 'bphtb-ssp.index', 'balik-nama.index', 'listrik-air.index');

-- Cetak Berkas
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'sppr.index' THEN 10
    WHEN 'bast.index' THEN 20
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Cetak Berkas'
  AND child.`route_name` IN ('sppr.index', 'bast.index');

-- Mutasi Transaksi
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'pindah-unit.index' THEN 10
    WHEN 'ganti-nama.index' THEN 20
    WHEN 'pembelian-cancel.index' THEN 30
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Mutasi Transaksi'
  AND child.`route_name` IN ('pindah-unit.index', 'ganti-nama.index', 'pembelian-cancel.index');

-- Master Data
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'perusahaan.index' THEN 10
    WHEN 'lokasi-kavling.index' THEN 20
    WHEN 'kavling.index' THEN 30
    WHEN 'marketing-offline.index' THEN 40
    WHEN 'bank-transaksi.index' THEN 50
    WHEN 'bank-kpr.index' THEN 60
    WHEN 'notaris.index' THEN 70
    WHEN 'jenis-berkas.index' THEN 80
    WHEN 'retensi.index' THEN 90
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Master Data'
  AND child.`route_name` IN ('perusahaan.index', 'lokasi-kavling.index', 'kavling.index', 'marketing-offline.index', 'bank-transaksi.index', 'bank-kpr.index', 'notaris.index', 'jenis-berkas.index', 'retensi.index');

-- Pengaturan
UPDATE `menu` AS child
INNER JOIN `menu` AS parent ON parent.`id` = child.`id_parent`
SET child.`urutan` = CASE child.`route_name`
    WHEN 'pengaturan-profil.index' THEN 10
    WHEN 'pengaturan-media.index' THEN 20
    WHEN 'pengaturan-pengguna.index' THEN 30
    WHEN 'role-user.index' THEN 40
    WHEN 'hak-akses.index' THEN 50
    WHEN 'list-penjualan.index' THEN 60
    WHEN 'log-aktivitas.index' THEN 70
    ELSE child.`urutan` END
WHERE parent.`id_parent` = 0 AND parent.`title` = 'Pengaturan'
  AND child.`route_name` IN ('pengaturan-profil.index', 'pengaturan-media.index', 'pengaturan-pengguna.index', 'role-user.index', 'hak-akses.index', 'list-penjualan.index', 'log-aktivitas.index');

COMMIT;
