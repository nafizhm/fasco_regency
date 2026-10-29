-- STU.jpg: 32 matched units; Maria Elisabet and Sonya excluded pending clarification.
-- Customer IDs, names and unit IDs guarded against mismatches. Re-running does not duplicate BAST.
START TRANSACTION;
-- Ani Yunus: KUNING 3, 2025-01-24
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-01-24', '0001/BAST/STU/01/2025' FROM customer c WHERE c.id = 6 AND c.nama_lengkap = 'Ani Yunus' AND c.id_kavling = 65 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-01-24' WHERE c.id = 6 AND c.nama_lengkap = 'Ani Yunus' AND c.id_kavling = 65 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 6 AND c.nama_lengkap = 'Ani Yunus' AND c.id_kavling = 65 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Siti Halimah: KUNING 7, 2025-05-03
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-05-03', '0002/BAST/STU/05/2025' FROM customer c WHERE c.id = 20 AND c.nama_lengkap = 'Siti Halimah' AND c.id_kavling = 69 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-05-03' WHERE c.id = 20 AND c.nama_lengkap = 'Siti Halimah' AND c.id_kavling = 69 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 20 AND c.nama_lengkap = 'Siti Halimah' AND c.id_kavling = 69 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Venda: MERAH 20, 2025-05-06
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-05-06', '0003/BAST/STU/05/2025' FROM customer c WHERE c.id = 16 AND c.nama_lengkap = 'Venda' AND c.id_kavling = 121 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-05-06' WHERE c.id = 16 AND c.nama_lengkap = 'Venda' AND c.id_kavling = 121 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 16 AND c.nama_lengkap = 'Venda' AND c.id_kavling = 121 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Anggie: HIJAU 2, 2025-05-24
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-05-24', '0004/BAST/STU/05/2025' FROM customer c WHERE c.id = 10 AND c.nama_lengkap = 'Anggie' AND c.id_kavling = 46 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-05-24' WHERE c.id = 10 AND c.nama_lengkap = 'Anggie' AND c.id_kavling = 46 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 10 AND c.nama_lengkap = 'Anggie' AND c.id_kavling = 46 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Dinda: KUNING 5, 2025-05-28
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-05-28', '0005/BAST/STU/05/2025' FROM customer c WHERE c.id = 27 AND c.nama_lengkap = 'Dinda' AND c.id_kavling = 67 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-05-28' WHERE c.id = 27 AND c.nama_lengkap = 'Dinda' AND c.id_kavling = 67 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 27 AND c.nama_lengkap = 'Dinda' AND c.id_kavling = 67 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Robby: MERAH 19, 2025-05-28
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-05-28', '0006/BAST/STU/05/2025' FROM customer c WHERE c.id = 22 AND c.nama_lengkap = 'Robby' AND c.id_kavling = 117 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-05-28' WHERE c.id = 22 AND c.nama_lengkap = 'Robby' AND c.id_kavling = 117 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 22 AND c.nama_lengkap = 'Robby' AND c.id_kavling = 117 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Haerman Sahputra: BIRU 20, 2025-06-19
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-06-19', '0007/BAST/STU/06/2025' FROM customer c WHERE c.id = 13 AND c.nama_lengkap = 'Haerman Sahputra' AND c.id_kavling = 45 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-06-19' WHERE c.id = 13 AND c.nama_lengkap = 'Haerman Sahputra' AND c.id_kavling = 45 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 13 AND c.nama_lengkap = 'Haerman Sahputra' AND c.id_kavling = 45 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Devi: KUNING 4, 2025-06-26
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-06-26', '0008/BAST/STU/06/2025' FROM customer c WHERE c.id = 18 AND c.nama_lengkap = 'Devi' AND c.id_kavling = 66 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-06-26' WHERE c.id = 18 AND c.nama_lengkap = 'Devi' AND c.id_kavling = 66 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 18 AND c.nama_lengkap = 'Devi' AND c.id_kavling = 66 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Bakhtiar: KUNING 8, 2025-07-01
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-07-01', '0009/BAST/STU/07/2025' FROM customer c WHERE c.id = 15 AND c.nama_lengkap = 'Bakhtiar' AND c.id_kavling = 70 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-07-01' WHERE c.id = 15 AND c.nama_lengkap = 'Bakhtiar' AND c.id_kavling = 70 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 15 AND c.nama_lengkap = 'Bakhtiar' AND c.id_kavling = 70 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Surya Fajariyanto: PUTIH 1, 2025-08-02
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-08-02', '0010/BAST/STU/08/2025' FROM customer c WHERE c.id = 11 AND c.nama_lengkap = 'Surya Fajariyanto' AND c.id_kavling = 100 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-08-02' WHERE c.id = 11 AND c.nama_lengkap = 'Surya Fajariyanto' AND c.id_kavling = 100 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 11 AND c.nama_lengkap = 'Surya Fajariyanto' AND c.id_kavling = 100 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Istin Guntaryo: MERAH 17, 2025-08-05
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-08-05', '0011/BAST/STU/08/2025' FROM customer c WHERE c.id = 42 AND c.nama_lengkap = 'Istin Guntaryo' AND c.id_kavling = 115 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-08-05' WHERE c.id = 42 AND c.nama_lengkap = 'Istin Guntaryo' AND c.id_kavling = 115 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 42 AND c.nama_lengkap = 'Istin Guntaryo' AND c.id_kavling = 115 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Putra Sakti: KUNING 9, 2025-08-06
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-08-06', '0012/BAST/STU/08/2025' FROM customer c WHERE c.id = 7 AND c.nama_lengkap = 'Putra Sakti' AND c.id_kavling = 79 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-08-06' WHERE c.id = 7 AND c.nama_lengkap = 'Putra Sakti' AND c.id_kavling = 79 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 7 AND c.nama_lengkap = 'Putra Sakti' AND c.id_kavling = 79 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Zidan: PUTIH 10, 2025-08-30
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-08-30', '0013/BAST/STU/08/2025' FROM customer c WHERE c.id = 12 AND c.nama_lengkap = 'Zidan' AND c.id_kavling = 90 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-08-30' WHERE c.id = 12 AND c.nama_lengkap = 'Zidan' AND c.id_kavling = 90 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 12 AND c.nama_lengkap = 'Zidan' AND c.id_kavling = 90 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Mardha: BIRU 1, 2025-09-01
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-09-01', '0014/BAST/STU/09/2025' FROM customer c WHERE c.id = 41 AND c.nama_lengkap = 'Mardha' AND c.id_kavling = 44 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-09-01' WHERE c.id = 41 AND c.nama_lengkap = 'Mardha' AND c.id_kavling = 44 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 41 AND c.nama_lengkap = 'Mardha' AND c.id_kavling = 44 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Doris: KUNING 6, 2025-10-03
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-10-03', '0015/BAST/STU/10/2025' FROM customer c WHERE c.id = 19 AND c.nama_lengkap = 'Doris' AND c.id_kavling = 68 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-10-03' WHERE c.id = 19 AND c.nama_lengkap = 'Doris' AND c.id_kavling = 68 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 19 AND c.nama_lengkap = 'Doris' AND c.id_kavling = 68 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Siti radiyah: HIJAU 6, 2025-11-28
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-11-28', '0016/BAST/STU/11/2025' FROM customer c WHERE c.id = 38 AND c.nama_lengkap = 'Siti radiyah' AND c.id_kavling = 50 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-11-28' WHERE c.id = 38 AND c.nama_lengkap = 'Siti radiyah' AND c.id_kavling = 50 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 38 AND c.nama_lengkap = 'Siti radiyah' AND c.id_kavling = 50 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Chusnul: HIJAU 9, 2025-11-29
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-11-29', '0017/BAST/STU/11/2025' FROM customer c WHERE c.id = 39 AND c.nama_lengkap = 'Chusnul' AND c.id_kavling = 58 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-11-29' WHERE c.id = 39 AND c.nama_lengkap = 'Chusnul' AND c.id_kavling = 58 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 39 AND c.nama_lengkap = 'Chusnul' AND c.id_kavling = 58 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Shofyan: KUNING 17, 2025-12-12
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-12-12', '0018/BAST/STU/12/2025' FROM customer c WHERE c.id = 8 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 77 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-12-12' WHERE c.id = 8 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 77 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 8 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 77 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Shofyan: KUNING 18, 2025-12-12
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2025-12-12', '0019/BAST/STU/12/2025' FROM customer c WHERE c.id = 9 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 81 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2025-12-12' WHERE c.id = 9 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 81 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 9 AND c.nama_lengkap = 'Shofyan' AND c.id_kavling = 81 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Habibur: HIJAU 4, 2026-01-10
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-01-10', '0020/BAST/STU/01/2026' FROM customer c WHERE c.id = 29 AND c.nama_lengkap = 'Habibur' AND c.id_kavling = 48 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-01-10' WHERE c.id = 29 AND c.nama_lengkap = 'Habibur' AND c.id_kavling = 48 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 29 AND c.nama_lengkap = 'Habibur' AND c.id_kavling = 48 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Wendrianto: HIJAU 5, 2026-01-28
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-01-28', '0021/BAST/STU/01/2026' FROM customer c WHERE c.id = 30 AND c.nama_lengkap = 'Wendrianto' AND c.id_kavling = 49 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-01-28' WHERE c.id = 30 AND c.nama_lengkap = 'Wendrianto' AND c.id_kavling = 49 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 30 AND c.nama_lengkap = 'Wendrianto' AND c.id_kavling = 49 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Sibghatullah: HIJAU 7, 2026-02-04
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-02-04', '0022/BAST/STU/02/2026' FROM customer c WHERE c.id = 32 AND c.nama_lengkap = 'Sibghatullah' AND c.id_kavling = 51 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-02-04' WHERE c.id = 32 AND c.nama_lengkap = 'Sibghatullah' AND c.id_kavling = 51 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 32 AND c.nama_lengkap = 'Sibghatullah' AND c.id_kavling = 51 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Supriyadi: HIJAU 3, 2026-05-02
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-05-02', '0023/BAST/STU/05/2026' FROM customer c WHERE c.id = 50 AND c.nama_lengkap = 'Supriyadi' AND c.id_kavling = 47 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-05-02' WHERE c.id = 50 AND c.nama_lengkap = 'Supriyadi' AND c.id_kavling = 47 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 50 AND c.nama_lengkap = 'Supriyadi' AND c.id_kavling = 47 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Dwi Agustin: KUNING 10, 2026-05-23
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-05-23', '0024/BAST/STU/05/2026' FROM customer c WHERE c.id = 37 AND c.nama_lengkap = 'Dwi Agustin' AND c.id_kavling = 80 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-05-23' WHERE c.id = 37 AND c.nama_lengkap = 'Dwi Agustin' AND c.id_kavling = 80 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 37 AND c.nama_lengkap = 'Dwi Agustin' AND c.id_kavling = 80 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Levina: MERAH 18, 2026-05-28
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-05-28', '0025/BAST/STU/05/2026' FROM customer c WHERE c.id = 31 AND c.nama_lengkap = 'Levina' AND c.id_kavling = 116 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-05-28' WHERE c.id = 31 AND c.nama_lengkap = 'Levina' AND c.id_kavling = 116 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 31 AND c.nama_lengkap = 'Levina' AND c.id_kavling = 116 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Yuli Eka: JINGGA 17, 2026-07-04
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-07-04', '0026/BAST/STU/07/2026' FROM customer c WHERE c.id = 44 AND c.nama_lengkap = 'Yuli Eka' AND c.id_kavling = 14 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-07-04' WHERE c.id = 44 AND c.nama_lengkap = 'Yuli Eka' AND c.id_kavling = 14 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 44 AND c.nama_lengkap = 'Yuli Eka' AND c.id_kavling = 14 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Fransiscus: BIRU 19, 2026-07-29
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-07-29', '0027/BAST/STU/07/2026' FROM customer c WHERE c.id = 33 AND c.nama_lengkap = 'Fransiscus' AND c.id_kavling = 43 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-07-29' WHERE c.id = 33 AND c.nama_lengkap = 'Fransiscus' AND c.id_kavling = 43 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 33 AND c.nama_lengkap = 'Fransiscus' AND c.id_kavling = 43 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Sidiq: BIRU 2, 2026-08-10
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-08-10', '0028/BAST/STU/08/2026' FROM customer c WHERE c.id = 35 AND c.nama_lengkap = 'Sidiq' AND c.id_kavling = 26 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-08-10' WHERE c.id = 35 AND c.nama_lengkap = 'Sidiq' AND c.id_kavling = 26 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 35 AND c.nama_lengkap = 'Sidiq' AND c.id_kavling = 26 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Liliana: PUTIH 2, 2026-08-31
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-08-31', '0029/BAST/STU/08/2026' FROM customer c WHERE c.id = 59 AND c.nama_lengkap = 'Liliana' AND c.id_kavling = 82 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-08-31' WHERE c.id = 59 AND c.nama_lengkap = 'Liliana' AND c.id_kavling = 82 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 59 AND c.nama_lengkap = 'Liliana' AND c.id_kavling = 82 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- I Gusti Gede: MERAH 10, 2026-09-01
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-09-01', '0030/BAST/STU/09/2026' FROM customer c WHERE c.id = 43 AND c.nama_lengkap = 'I Gusti Gede' AND c.id_kavling = 119 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-09-01' WHERE c.id = 43 AND c.nama_lengkap = 'I Gusti Gede' AND c.id_kavling = 119 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 43 AND c.nama_lengkap = 'I Gusti Gede' AND c.id_kavling = 119 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Nindya: PUTIH 20, 2026-09-07
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-09-07', '0031/BAST/STU/09/2026' FROM customer c WHERE c.id = 45 AND c.nama_lengkap = 'Nindya' AND c.id_kavling = 101 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-09-07' WHERE c.id = 45 AND c.nama_lengkap = 'Nindya' AND c.id_kavling = 101 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 45 AND c.nama_lengkap = 'Nindya' AND c.id_kavling = 101 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
-- Nindi: MERAH 11, 2026-09-07
INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '2026-09-07', '0032/BAST/STU/09/2026' FROM customer c WHERE c.id = 58 AND c.nama_lengkap = 'Nindi' AND c.id_kavling = 120 AND c.id_lokasi = 1 AND c.stt_arsip = 0 AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);
UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '2026-09-07' WHERE c.id = 58 AND c.nama_lengkap = 'Nindi' AND c.id_kavling = 120 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE c.id = 58 AND c.nama_lengkap = 'Nindi' AND c.id_kavling = 120 AND c.id_lokasi = 1 AND c.stt_arsip = 0;
COMMIT;
