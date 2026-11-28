# Titik awal SQL berikutnya

## Sesi baru 20 November 2026

Permintaan pengguna: catat perubahan SQL mulai sesi ini. Paket sesi ini berada di `database/sql/2026-11-20-session-changes.sql`; tidak mengulang perubahan sesi sebelumnya.

1. Migrasi `2026_11_20_000000_add_biaya_kpr_to_detail_akad`: tambah `detail_akad.biaya_kpr` bertipe `DECIMAL(18,2) NULL` untuk isian Biaya KPR opsional pada tambah dan edit akad. Data lama tetap NULL.

Tambahkan perubahan SQL berikutnya pada sesi ini ke paket tersebut.

2. Migrasi `2026_11_20_010000_add_laporan_tukang_menu`: tambah submenu **Laporan Tukang** di bawah Tukang, route `laporan-tukang.index`, dengan hak lihat diwarisi dari Absen Tukang (fallback Data Tukang). Laporan mingguan dan bulanan memakai absensi, tarif historis, dan tambahan gaji yang sudah tersimpan; tidak menambah tabel transaksi. Periode bulanan berdasarkan tanggal Sabtu akhir pekan kerja sesuai pilihan pengguna. Preview dan PDF memakai data serta template tabel yang sama.

3. Migrasi `2026_11_20_020000_add_rekap_transaksi_menu`: tambah menu **Rekap Transaksi** tepat setelah SPK dengan menggeser urutan menu berikutnya; hak lihat diwarisi dari SPK. Filter tanggal awal–akhir inklusif, pilihan ringkasan per kategori atau detail seluruh transaksi, preview dan cetak PDF. Sumber: `pemasukan`, `pengeluaran`, `barang_masuk_pembayaran`, `spk_pembayaran`. Tidak menambah tabel transaksi. SQL deployment ditambahkan ke paket sesi ini.

4. Migrasi `2026_11_23_000000_create_fee_marketing` (23 November 2026): tambah tabel `fee_marketing` (nomor unik, tanggal, jumlah rupiah bulat, data JSON dalam LONGTEXT, timestamps), menu **Fee Marketing** setelah Pemasukan pada kelompok yang sama, serta hak lihat/tambah/edit/hapus yang diwarisi dari Pemasukan. Data JSON menyimpan kop, penerima, rincian fee per level, riwayat, rekening dan tanda tangan. Komisi dihitung persentase × harga deal; total fee = BLT + komisi; jumlah bukti = jumlah fee saat ini. Modul ini menyimpan bukti mandiri dan tidak otomatis membuat transaksi pengeluaran. SQL hosting yang dapat dijalankan ulang ditambahkan ke `database/sql/2026-11-20-session-changes.sql`.

5. Migrasi `2026_11_23_010000_move_fee_marketing_to_main_menu`: ubah `menu.id_parent` menjadi `0` untuk `fee-marketing.index` agar tampil sebagai menu utama. Data dan hak akses tetap. Query khusus tersedia di `database/sql/2026-11-23-fee-marketing-menu-utama.sql` dan juga ditambahkan ke paket SQL sesi.

6. Migrasi `2026_11_23_020000_add_delegated_to_to_work_priorities`: tambah `work_priorities.delegated_to VARCHAR(200) NULL` beserta indeks. Nilai NULL tetap menjadi Prioritas Pekerjaan; nama terisi menjadi Delegasi Pekerjaan. Dashboard menampilkan dua panel 50:50 pada desktop dan bertumpuk pada layar kecil, dengan tambah tugas dan pembaruan status masing-masing. SQL khusus hosting: `database/sql/2026-11-23-delegasi-pekerjaan.sql`, juga digabung ke paket SQL sesi. Prasyarat: tabel `work_priorities` dari fitur Prioritas Pekerjaan sudah tersedia.

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

## 24 November 2026 - Purchase Order

Migrasi 2026_11_24_000000_rename_purchase_order_menu mengganti title menu pengajuan-pembelian.index menjadi Purchase Order. Route dan hak akses tetap. SQL hosting: database/sql/2026-11-24-purchase-order.sql, juga ditambahkan ke paket sesi 20 November. PDF memakai logo Logo Login seperti kwitansi serta area Order By, tanda tangan dan nama lengkap; tidak menambah kolom transaksi.

## 24 November 2026 - Tampilkan riwayat kwitansi

Migrasi `2026_11_24_010000_add_tampilkan_kwitansi_to_pemasukan` menambah `pemasukan.tampilkan_kwitansi` BOOLEAN NOT NULL DEFAULT 1. Centang Tampilkan pada tambah/edit pembayaran menentukan baris riwayat kwitansi beserta total riwayat. Data lama dan pembayaran baru default tampil; saldo transaksi dan jumlah utama kwitansi tetap menggunakan seluruh pembayaran. SQL deployment idempoten dicatat dalam paket sesi 20 November.


## 28 November 2026 - Pengajuan Tagihan

Migrasi `2026_11_28_000000_create_pengajuan_tagihan`: tabel header `pengajuan_tagihan` dan rincian `pengajuan_tagihan_detail`, nomor unik, tanggal, status (Draft/Diajukan/Disetujui/Ditolak/Dibayar), keterangan, tanggal nota maksimal, nama, uraian, nominal rupiah bulat untuk Fasco Regency/Tegalgondo/Ruko/Bedali/Lumajang, rekening serta lampiran privat per baris. Total dihitung dari rincian. Menu utama **Pengajuan Tagihan**, route `pengajuan-tagihan.index`, dengan akses lihat/tambah/edit/hapus untuk username `nita`. Tidak membuat transaksi pengeluaran otomatis.

SQL khusus sesi ini untuk hosting: `database/sql/2026-11-28-pengajuan-tagihan.sql`; juga ditambahkan ke paket `database/sql/2026-11-20-session-changes.sql`. Gunakan salah satu sesuai baseline hosting. SQL dapat dijalankan ulang dan mencatat migrasi. Setelah deploy kode dan SQL, login ulang sebagai nita untuk menyegarkan menu session. Lampiran disimpan pada disk local (`storage/app/private` sesuai konfigurasi aplikasi); tidak membutuhkan storage link publik.


Penyesuaian alur Pengajuan Tagihan (28 November 2026): tambah dan edit melalui modal, satu pengajuan memuat banyak baris tagihan. Validasi disajikan di modal tanpa menghilangkan isian/lampiran yang dipilih. Tidak ada perubahan struktur database atau SQL tambahan.

Penyesuaian form Pengajuan Tagihan: satu kali input/simpan satu tagihan, layout vertikal dengan label kiri dan isian kanan, tanpa tombol tambah baris di dalam form. Tambah tagihan berikutnya dari halaman detail pengajuan; edit satu tagihan mempertahankan tagihan lainnya. Tidak ada perubahan SQL.

Daftar Pengajuan Tagihan diperbarui menjadi DataTables satu baris per tagihan, seluruh kolom detail, filter status dan total mengikuti pencarian/filter. Aksi lampiran mendukung preview PDF/gambar; Word/Excel diunduh. Simpan kembali ke daftar, tanpa tombol Buka Pengajuan atau jumlah tagihan. Hapus pada daftar hanya menghapus tagihan terpilih (header dihapus jika tagihan terakhir). Tidak ada perubahan SQL.

Penyempurnaan daftar Pengajuan Tagihan: nomor dan tanggal pengajuan digabung satu kolom, tanggal nota maksimal tidak ditampilkan pada daftar, lampiran dibuka dalam modal (PDF/gambar) dengan tombol unduh. Indeks filter status dan total DataTables disesuaikan. Tidak ada perubahan SQL.

Cetak PDF Pengajuan Tagihan: tombol di samping Tambah Pengajuan, A4 landscape, seluruh baris hasil filter status/pencarian DataTables beserta urutannya (semua halaman), total hanya data tercetak. Endpoint POST pengajuan-tagihan.pdf memeriksa hak lihat. Tidak ada perubahan SQL.


## 28 November 2026 - Invoice IPL

Migrasi `2026_11_28_010000_create_invoice_ipl`: tabel `invoice_ipl` (nomor unik, nama penghuni, alamat rumah, tanggal STU, nominal tahunan bulat, jatuh tempo, rekening pembayaran dan penandatangan), tabel `invoice_ipl_pembayaran` (tanggal, nominal, transaksi, keterangan), menu utama **Invoice IPL**, akses lihat/tambah/edit/hapus untuk username `nita`. Form tambah/edit halaman terpisah; riwayat pembayaran opsional dan bisa beberapa baris. Total pembayaran, sisa dan status dihitung dari rincian. Cetak PDF A4 portrait mengikuti contoh bukti IPL, logo dari Logo Login. Tidak otomatis membuat transaksi pemasukan.

Hosting: jalankan `database/sql/2026-11-28-invoice-ipl.sql`, atau paket kumulatif `database/sql/2026-11-20-session-changes.sql` sesuai baseline. SQL dapat diulang dan mencatat migrasi. Deploy kode lalu login ulang Nita untuk menampilkan menu.

Invoice IPL: rekening perusahaan dan penandatangan dibuat tetap sesuai contoh (Laji Siswanto, BCA, 3681655211, Marianita, Staff Administrasi). Isian tersebut dihapus dari form; server menetapkan nilainya dan tampilan detail/PDF selalu memakai data tetap, termasuk invoice lama. Tidak ada perubahan struktur atau SQL tambahan.

Dashboard Piutang KPR: tombol Cetak PDF, laporan landscape (A4 hingga empat termin, A3 untuk lebih banyak termin), sumber data dan tabel bersama dengan halaman laporan. Memuat rincian termin, persentase dan total piutang. Tidak ada perubahan SQL.

Dashboard Piutang: tombol Cetak PDF A4 landscape, mengikuti filter jatuh tempo serta pencarian/urutan DataTables pada semua halaman. Sumber perhitungan tetap DashboardReceivableService, total hanya customer tercetak. Tidak ada perubahan SQL.
