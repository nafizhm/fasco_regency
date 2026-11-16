-- Jalankan satu kali sebelum mengunggah kode terbaru.
-- Data lama tetap kosong sampai jenis dipilih melalui Edit Tukang.
ALTER TABLE `tukang` ADD COLUMN `jenis` VARCHAR(20) NULL DEFAULT NULL;
