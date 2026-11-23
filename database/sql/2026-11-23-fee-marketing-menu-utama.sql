-- Khusus memindahkan Fee Marketing menjadi menu utama.
-- Jalankan setelah menu Fee Marketing tersedia.
UPDATE `menu`
SET `id_parent` = 0
WHERE `route_name` = 'fee-marketing.index';
