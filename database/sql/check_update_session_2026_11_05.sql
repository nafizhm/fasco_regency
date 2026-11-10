-- Jalankan TERPISAH setelah impor utama berhasil, melalui tab SQL phpMyAdmin.
SET FOREIGN_KEY_CHECKS = 1;
SELECT migration,batch FROM migrations WHERE migration LIKE '2026_11_05_%' ORDER BY migration;
SELECT title,route_name FROM menu WHERE route_name IN('customer-receipt-notes.index','notary-deposits.index');
SELECT COUNT(*) AS customer_aktif_tanpa_piutang FROM customer c JOIN kavling_peta k ON k.id=c.id_kavling
WHERE COALESCE(c.stt_arsip,0)=0 AND c.total_harga>0 AND NOT EXISTS(SELECT 1 FROM piutang p WHERE p.id_customer=c.id);
