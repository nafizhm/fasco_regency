<?php
require __DIR__.'/vendor/autoload.php';$app=require __DIR__.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo=Illuminate\Support\Facades\DB::connection()->getPdo();
$source=$pdo->query('SELECT DATABASE()')->fetchColumn();
$test='nh_sqlcheck_20261105_'.bin2hex(random_bytes(4));
if(!preg_match('/^[a-zA-Z0-9_]+$/',$source)||!preg_match('/^nh_sqlcheck_20261105_[a-f0-9]{8}$/',$test))throw new RuntimeException('Invalid test schema');
$created=false;
function executeFile(PDO $pdo,string $file):void{
 $delimiter=';';$buffer='';foreach(file($file) as $line){$trim=trim($line);if(str_starts_with($trim,'--')||$trim==='')continue;
 if(preg_match('/^DELIMITER\s+(\S+)$/',$trim,$m)){$delimiter=$m[1];continue;}
 $buffer.=$line;if(str_ends_with(rtrim($line),$delimiter)){$statement=substr(rtrim($buffer),0,-strlen($delimiter));$st=$pdo->query($statement);do{if($st->columnCount())$st->fetchAll();}while($st->nextRowset());$st->closeCursor();$buffer='';}}
 if(trim($buffer)!=='')throw new RuntimeException('Unexecuted SQL');
}
try{
 $pdo->exec('CREATE DATABASE `'.$test.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$created=true;
 foreach(['menu','hak_akses','role_user','customer','customer_tempo','pengajuan_hold','kavling_peta','piutang','pemasukan','akad','detail_akad','migrations'] as $table){$pdo->exec("CREATE TABLE `$test`.`$table` LIKE `$source`.`$table`");$pdo->exec("INSERT INTO `$test`.`$table` SELECT * FROM `$source`.`$table`");}
 $pdo->exec("USE `$test`");
 foreach(['customer'=>['harga_jual_awal','diskon','id_booking','catatan_kwitansi'],'customer_tempo'=>['harga_jual_awal','diskon'],'pengajuan_hold'=>['diskon','rincian_biaya_penjualan']] as $table=>$columns){foreach($columns as $column)$pdo->exec("ALTER TABLE `$table` DROP COLUMN `$column`");}
 $menuIds=$pdo->query("SELECT id FROM menu WHERE route_name IN ('customer-receipt-notes.index','notary-deposits.index')")->fetchAll(PDO::FETCH_COLUMN);
 foreach($menuIds as $id){$pdo->exec('DELETE FROM hak_akses WHERE id_menu='.(int)$id);$pdo->exec('DELETE FROM role_user WHERE id_menu='.(int)$id);$pdo->exec('DELETE FROM menu WHERE id='.(int)$id);}
 $pdo->exec("DELETE FROM migrations WHERE migration LIKE '2026_11_05_%'");
 $pdo->exec('DELETE FROM piutang');
 $files=glob(storage_path('app/private/akad-keterangan-before-20261105-*.json'));sort($files);$notes=json_decode(file_get_contents($files[0]),true);
 foreach(['akad','detail_akad'] as $t){$q=$pdo->prepare("UPDATE `$t` SET keterangan=? WHERE id=?");foreach($notes[$t] as $r)$q->execute([$r['keterangan'],$r['id']]);}
 $template=$pdo->query('SELECT * FROM customer ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
 foreach([901,902,903] as $id){$r=$template;$r['id']=$id;$r['kode_customer']='SQLTEST-'.$id;$r['nama_lengkap']='Customer Sqltest '.$id;$r['total_harga']=1000;$r['hrg_jual']=1000;$r['stt_arsip']=0;
 $fields=implode(',',array_map(fn($v)=>'`'.$v.'`',array_keys($r)));$q=$pdo->prepare('INSERT INTO customer('.$fields.') VALUES('.implode(',',array_fill(0,count($r),'?')).')');$q->execute(array_values($r));}
 $pdo->exec("INSERT INTO piutang(id_customer,id_bank,tanggal_piutang,deskripsi,nominal,lampiran,status,terbayar,sisa_bayar,id_kategori_transaksi) VALUES(903,0,'2026-11-01','Existing custom bill',500,'',1,20,480,0)");
 foreach([[600,'2026-11-01',0,'Pembayaran'],[500,'2026-11-02',0,'Pembayaran'],[2000,'2026-11-04',99999999,'Wrong linked bill'],[3000,'2026-11-05',0,'Biaya ganti nama customer']] as [$amount,$date,$linked,$note]){
 $q=$pdo->prepare("INSERT INTO pemasukan(tanggal,id_customer,id_bank,id_piutang,id_kategori_transaksi,no_kwitansi,nominal,keterangan,keterangan_kategori,id_metode_bayar,lampiran) VALUES(?,901,0,?,4,'SQLTEST',?,?,'Booking',1,'')");$q->execute([$date,$linked,$amount,$note]);}
 $file=database_path('sql/update_session_2026_11_05.sql');executeFile($pdo,$file);
 $b=$pdo->query('SELECT * FROM piutang WHERE id_customer=901')->fetch(PDO::FETCH_ASSOC);
 if((int)$b['terbayar']!==1000||(int)$b['sisa_bayar']!==0||$b['tgl_pelunasan']!=='2026-11-02')throw new RuntimeException('Allocation mismatch');
 $b=$pdo->query('SELECT * FROM piutang WHERE id_customer=903')->fetch(PDO::FETCH_ASSOC);if((int)$b['nominal']!==500||(int)$b['terbayar']!==20)throw new RuntimeException('Existing bill changed');
 $counts=[];foreach(['menu','hak_akses','role_user','piutang','migrations'] as $t)$counts[$t]=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
 executeFile($pdo,$file);
 foreach($counts as $t=>$count)if((int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn()!==$count)throw new RuntimeException('Not repeatable: '.$t);
 echo 'PASS: SQL executes against pre-update MySQL schema.'.PHP_EOL;
 echo 'PASS: existing bills preserved; partial/full payments and excluded payments allocated correctly.'.PHP_EOL;
 echo 'PASS: repeat import does not duplicate tables, menus, permissions, bills or migration records.'.PHP_EOL;
}finally{
 $pdo->exec("USE `$source`");
 if($created&&preg_match('/^nh_sqlcheck_20261105_[a-f0-9]{8}$/',$test))$pdo->exec("DROP DATABASE `$test`");
}
