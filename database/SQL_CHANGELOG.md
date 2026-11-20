# Titik awal SQL berikutnya

## Sesi baru 20 November 2026

Permintaan pengguna: catat perubahan SQL mulai sesi ini. Paket sesi ini berada di `database/sql/2026-11-20-session-changes.sql`; tidak mengulang perubahan sesi sebelumnya.

1. Migrasi `2026_11_20_000000_add_biaya_kpr_to_detail_akad`: tambah `detail_akad.biaya_kpr` bertipe `DECIMAL(18,2) NULL` untuk isian Biaya KPR opsional pada tambah dan edit akad. Data lama tetap NULL.

Tambahkan perubahan SQL berikutnya pada sesi ini ke paket tersebut.

2. Migrasi `2026_11_20_010000_add_laporan_tukang_menu`: tambah submenu **Laporan Tukang** di bawah Tukang, route `laporan-tukang.index`, dengan hak lihat diwarisi dari Absen Tukang (fallback Data Tukang). Laporan mingguan dan bulanan memakai absensi, tarif historis, dan tambahan gaji yang sudah tersimpan; tidak menambah tabel transaksi. Periode bulanan berdasarkan tanggal Sabtu akhir pekan kerja sesuai pilihan pengguna. Preview dan PDF memakai data serta template tabel yang sama.

3. Migrasi `2026_11_20_020000_add_rekap_transaksi_menu`: tambah menu **Rekap Transaksi** tepat setelah SPK dengan menggeser urutan menu berikutnya; hak lihat diwarisi dari SPK. Filter tanggal awal–akhir inklusif, pilihan ringkasan per kategori atau detail seluruh transaksi, preview dan cetak PDF. Sumber: `pemasukan`, `pengeluaran`, `barang_masuk_pembayaran`, `spk_pembayaran`. Tidak menambah tabel transaksi. SQL deployment ditambahkan ke paket sesi ini.

## Catatan baseline sebelumnya

Permintaan pengguna pada 10 November 2026: SQL berikutnya harus dimulai dari penambahan jatuh tempo pembayaran Barang Masuk pada sesi ini.

Baseline sebelumnya: `UPDATE_GUDANG_2026_11_10.sql`, sampai migrasi `2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran`. Jangan masukkan ulang perubahan sebelum titik awal ini kecuali diminta pengguna.

Perubahan sejak titik awal:

1. Migrasi `2026_11_10_040000_add_jatuh_tempo_to_barang_masuk`: tambah `barang_masuk.jatuh_tempo` bertipe `DATE NULL`. Kolom opsional untuk tanggal jatuh tempo pembayaran supplier.
2. Migrasi `2026_11_10_050000_add_manual_work_type_to_barang_keluar_detail`: tambah `barang_keluar_detail.jenis_pekerjaan` bertipe `VARCHAR(255) NULL` untuk pekerjaan ketik manual. Unit kini diambil berdasarkan lokasi tanpa mewajibkan Setting Proyek.

Perubahan tampilan berikutnya (tanpa perubahan DB): jatuh tempo digabung ke Status dengan sisa hari/terlambat, urutan belum lunas berdasarkan jatuh tempo lalu lunas di bawah, tombol aksi berupa ikon dengan tooltip.

Pilihan Barang Keluar ditambah filter Cluster sebelum Lokasi Unit, memakai kolom `kavling_peta.nama_cluster` yang sudah ada. Tidak ada SQL tambahan untuk filter ini.

```sql
ALTER TABLE `barang_masuk` ADD COLUMN `jatuh_tempo` DATE NULL;
ALTER TABLE `barang_keluar_detail` ADD COLUMN `jenis_pekerjaan` VARCHAR(255) NULL;
```

Tambahkan perubahan DB berikutnya ke daftar ini agar paket SQL lanjutan lengkap. SQL di atas belum diberikan sebagai paket deployment kepada pengguna; migrasi lokal dijalankan saat implementasi fitur.
