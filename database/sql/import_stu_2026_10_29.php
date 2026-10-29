<?php
// Run from project root: php database/sql/import_stu_2026_10_29.php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

// Matched by existing customer name AND cluster/unit. Unresolved: Maria Elisabet and Sonya.
$rows = [
 [6,'KUNING',3,'2025-01-24'],[20,'KUNING',7,'2025-05-03'],
 [16,'MERAH',20,'2025-05-06'],[10,'HIJAU',2,'2025-05-24'],
 [27,'KUNING',5,'2025-05-28'],[22,'MERAH',19,'2025-05-28'],
 [13,'BIRU',20,'2025-06-19'],[18,'KUNING',4,'2025-06-26'],
 [15,'KUNING',8,'2025-07-01'],[11,'PUTIH',1,'2025-08-02'],
 [42,'MERAH',17,'2025-08-05'],[7,'KUNING',9,'2025-08-06'],
 [12,'PUTIH',10,'2025-08-30'],[41,'BIRU',1,'2025-09-01'],
 [19,'KUNING',6,'2025-10-03'],[38,'HIJAU',6,'2025-11-28'],
 [39,'HIJAU',9,'2025-11-29'],[8,'KUNING',17,'2025-12-12'],
 [9,'KUNING',18,'2025-12-12'],[29,'HIJAU',4,'2026-01-10'],
 [30,'HIJAU',5,'2026-01-28'],[32,'HIJAU',7,'2026-02-04'],
 [50,'HIJAU',3,'2026-05-02'],[37,'KUNING',10,'2026-05-23'],
 [31,'MERAH',18,'2026-05-28'],[44,'JINGGA',17,'2026-07-04'],
 [33,'BIRU',19,'2026-07-29'],[35,'BIRU',2,'2026-08-10'],
 [59,'PUTIH',2,'2026-08-31'],[43,'MERAH',10,'2026-09-01'],
 [45,'PUTIH',20,'2026-09-07'],[58,'MERAH',11,'2026-09-07'],
];
$sql = ["-- STU.jpg: 32 matched units; Maria Elisabet and Sonya excluded pending clarification.",
 "-- Customer IDs, names and unit IDs guarded against mismatches. Re-running does not duplicate BAST.","START TRANSACTION;"];
$backup = [];
DB::transaction(function () use ($rows, &$sql, &$backup) {
 $status = DB::table('progres_list_penjualan')->whereRaw('LOWER(TRIM(status_progres)) = ?', ['serah terima'])->sole()->id;
 foreach ($rows as $i => [$id,$cluster,$unit,$date]) {
  $c = App\Models\Customer::with('kavling','lokasi')->lockForUpdate()->findOrFail($id);
  if ($c->stt_arsip || strtoupper($c->kavling->nama_cluster) !== $cluster || (int)$c->kavling->kode_kavling !== $unit) {
   throw new RuntimeException("Unit mismatch for customer $id");
  }
  $existing = DB::table('bast')->where('id_customer',$id)->lockForUpdate()->get();
  if ($existing->count() > 1) throw new RuntimeException("Duplicate BAST for $id");
  $backup[] = ['customer'=>$c->only(['id','nama_lengkap','id_kavling','id_status_progres']), 'bast'=>$existing->toArray()];
  // Source provides dates but no document numbers. Use stable import numbers, with source month/year.
  $number = sprintf('%04d/BAST/STU/%s/%s',$i+1, date('m',strtotime($date)),substr($date,0,4));
  $number = $existing->first()->no_bast ?? $number;
  $pdo = DB::connection()->getPdo();
  $name = $pdo->quote($c->nama_lengkap);
  $no = $pdo->quote($number);
  $guard = "c.id = $id AND c.nama_lengkap = $name AND c.id_kavling = {$c->id_kavling} AND c.id_lokasi = {$c->id_lokasi} AND c.stt_arsip = 0";
  $sql[] = "-- {$c->nama_lengkap}: $cluster $unit, $date";
  $sql[] = "INSERT INTO bast (id_customer, tanggal_bast, no_bast) SELECT c.id, '$date', $no FROM customer c WHERE $guard AND NOT EXISTS (SELECT 1 FROM bast b WHERE b.id_customer = c.id);";
  $sql[] = "UPDATE bast b JOIN customer c ON c.id = b.id_customer SET b.tanggal_bast = '$date' WHERE $guard;";
  $sql[] = "UPDATE customer c JOIN progres_list_penjualan p ON LOWER(TRIM(p.status_progres)) = 'serah terima' SET c.id_status_progres = p.id WHERE $guard;";
 }
 // Persist backup and reviewable SQL before database mutations.
 file_put_contents(__DIR__.'/stu_2026_10_29_before.json', json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 $sql[] = 'COMMIT;';
 file_put_contents(__DIR__.'/update_bast_stu_2026_10_29.sql',implode(PHP_EOL,$sql).PHP_EOL);
 foreach ($rows as $i => [$id,$cluster,$unit,$date]) {
  $number = sprintf('%04d/BAST/STU/%s/%s',$i+1,date('m',strtotime($date)),substr($date,0,4));
  if (!DB::table('bast')->where('id_customer',$id)->exists()) {
   DB::table('bast')->insert(['id_customer'=>$id,'tanggal_bast'=>$date,'no_bast'=>$number]);
  } else DB::table('bast')->where('id_customer',$id)->update(['tanggal_bast'=>$date]);
  DB::table('customer')->where('id',$id)->update(['id_status_progres'=>$status]);
 }
 foreach ($rows as [$id,$cluster,$unit,$date]) {
  if (DB::table('bast')->where('id_customer',$id)->where('tanggal_bast',$date)->count() !== 1 || (int)DB::table('customer')->where('id',$id)->value('id_status_progres') !== (int)$status) {
   throw new RuntimeException("Verification failed: $id");
  }
 }
});
echo 'Verified '.count($rows).' BAST dates and Serah Terima statuses.'.PHP_EOL;
