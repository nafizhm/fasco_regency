<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\KavlingController;
use App\Models\KomponenBiaya;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KavlingExcelTest extends TestCase
{
    public function test_excel_deduplicates_costs_and_roundtrips_separate_numeric_dimensions(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('lokasi_kavling', function (Blueprint $table) { $table->id(); $table->string('nama_kavling'); });
        Schema::create('komponen_biaya', function (Blueprint $table) {
            $table->id(); $table->string('nama'); $table->string('kode_unik'); $table->boolean('aktif'); $table->integer('urutan'); $table->timestamps();
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id(); $table->integer('id_lokasi'); $table->string('kode_kavling');
            $table->string('nama_cluster')->nullable();
            foreach (['panjang', 'lebar', 'luas_tanah', 'luas_bangunan', 'hrg_jual', 'biaya_surat', 'peningkatan_mutu'] as $field) $table->double($field)->default(0);
            $table->text('rincian_biaya')->nullable();
        });
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Perumahan']);
        foreach (['KELEBIHAN TANAH', 'Lekebihan Tanah', 'Kelebihan Tanah', 'Harga Rumah'] as $index => $name) {
            DB::table('komponen_biaya')->insert(['nama' => $name, 'kode_unik' => 'cost_' . $index, 'aktif' => 1, 'urutan' => $index]);
        }
        DB::table('kavling_peta')->insert(['id' => 1, 'id_lokasi' => 1, 'kode_kavling' => 'B-01',
            'nama_cluster' => 'JINGGA', 'panjang' => 12.5, 'lebar' => 6, 'luas_tanah' => 75, 'luas_bangunan' => 45,
            'rincian_biaya' => json_encode([['nama' => 'KELEBIHAN TANAH', 'nilai' => 5000000], ['nama' => 'Kelebihan Tanah', 'nilai' => 5000000], ['nama' => 'Harga Rumah', 'nilai' => 600000000]])]);
        $response = app(KavlingController::class)->cetakExcel(new Request(), 0);
        ob_start(); $response->sendContent(); $bytes = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'kavling_excel_');
        try {
            file_put_contents($path, $bytes);
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $headers = $sheet->rangeToArray('A2:K2')[0];
            $this->assertSame(['No', 'Perumahan', 'Nama Cluster', 'Kode Kavling', 'Panjang', 'Lebar', 'Luas Tanah', 'Luas Bangunan', 'Kelebihan Tanah', 'Harga Rumah', 'Total Harga'], $headers);
            $this->assertSame('n', $sheet->getCell('E3')->getDataType());
            $this->assertEquals(12.5, $sheet->getCell('E3')->getValue());
            $this->assertSame('JINGGA', $sheet->getCell('C3')->getValue());
            $this->assertEquals(5000000, $sheet->getCell('I3')->getValue());
            $this->assertSame('=SUM(I3:J3)', $sheet->getCell('K3')->getValue());
            $sheet->setCellValue('C3', ' BIRU ');
            $sheet->setCellValue('G3', 84);
            $sheet->setCellValue('H3', 50);
            (new Xlsx($book))->save($path);
            $request = Request::create('/import', 'POST', [], [], ['file' => new UploadedFile($path, 'kavling.xlsx', null, null, true)]);
            app(KavlingController::class)->importExcel($request);
            $this->assertDatabaseHas('kavling_peta', ['id' => 1, 'nama_cluster' => 'BIRU', 'luas_tanah' => 84, 'luas_bangunan' => 50, 'hrg_jual' => 600000000]);
            $this->assertDatabaseCount('komponen_biaya', 4);

            // File lama tanpa kolom cluster tidak menghapus cluster yang tersimpan.
            $sheet->removeColumn('C');
            $sheet->setCellValue('F3', 90);
            (new Xlsx($book))->save($path);
            app(KavlingController::class)->importExcel($request);
            $this->assertDatabaseHas('kavling_peta', ['id' => 1, 'nama_cluster' => 'BIRU', 'luas_tanah' => 90]);

            DB::table('kavling_peta')->insert([
                ['id' => 2, 'id_lokasi' => 1, 'kode_kavling' => 'A-10', 'nama_cluster' => 'JINGGA'],
                ['id' => 3, 'id_lokasi' => 1, 'kode_kavling' => 'A-2', 'nama_cluster' => 'JINGGA'],
                ['id' => 4, 'id_lokasi' => 1, 'kode_kavling' => 'A-1', 'nama_cluster' => 'JINGGA'],
            ]);
            $response = app(KavlingController::class)->cetakExcel(new Request(), 0);
            ob_start(); $response->sendContent(); $sortedBytes = ob_get_clean();
            file_put_contents($path, $sortedBytes);
            $sortedSheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame([
                ['BIRU', 'B-01'],
                ['JINGGA', 'A-1'],
                ['JINGGA', 'A-2'],
                ['JINGGA', 'A-10'],
            ], $sortedSheet->rangeToArray('C3:D6'));
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    public function test_duplicate_cost_names_are_rejected_even_with_case_spaces_or_known_typo(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        Schema::create('komponen_biaya', function (Blueprint $table) { $table->id(); $table->string('nama'); });
        DB::table('komponen_biaya')->insert(['nama' => 'KELEBIHAN TANAH']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        KomponenBiaya::create(['nama' => '  Lekebihan   Tanah  ']);
    }
}
