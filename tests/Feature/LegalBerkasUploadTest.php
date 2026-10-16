<?php

namespace Tests\Feature;

use App\Http\Controllers\Legal\BerkasPengajuanController;
use App\Services\LegalBerkasPdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalBerkasUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        Storage::fake('local');
        Schema::create('jenis_berkas', function (Blueprint $table) {
            $table->id(); $table->string('nama'); $table->integer('urutan'); $table->boolean('aktif')->default(true);
        });
        Schema::create('persyaratan_legal', function (Blueprint $table) {
            $table->id(); $table->integer('id_customer')->nullable(); $table->json('status_jenis_berkas')->nullable(); $table->json('file_jenis_berkas')->nullable();
            $table->text('catatan_kekurangan')->nullable(); $table->string('percakapan_wa')->nullable();
        });
        DB::table('jenis_berkas')->insert([
            ['id' => 1, 'nama' => 'Dokumen A', 'urutan' => 2],
            ['id' => 2, 'nama' => 'Dokumen B', 'urutan' => 1],
        ]);
        DB::table('persyaratan_legal')->insert(['id' => 1, 'id_customer' => 1]);
        Schema::create('customer', function (Blueprint $table) {
            $table->id(); $table->string('nama_lengkap')->nullable(); $table->string('nik')->nullable(); $table->string('no_telp')->nullable(); $table->integer('id_lokasi')->nullable(); $table->integer('id_kavling')->nullable();
        });
        Schema::create('lokasi_kavling', function (Blueprint $table) { $table->id(); });
        Schema::create('kavling_peta', function (Blueprint $table) { $table->id(); });
        Schema::create('upload_file', function (Blueprint $table) {
            $table->id(); $table->integer('id_customer'); $table->string('nama_file'); $table->string('lampiran');
        });
        DB::table('customer')->insert(['id' => 1, 'nama_lengkap' => 'Pembeli']);
        Route::put('/_test/customer/{id}/upload', [\App\Http\Controllers\Customer\UploudFileController::class, 'update']);
        Route::get('/_test/customer/{id}/files', [\App\Http\Controllers\Customer\UploudFileController::class, 'edit']);
        Route::delete('/_test/legal/{id}/file/{jenis}', [BerkasPengajuanController::class, 'deleteFile']);
        Route::put('/_test/legal/{id}', [BerkasPengajuanController::class, 'update']);
        Route::get('/_test/legal/{id}/print', [BerkasPengajuanController::class, 'print']);
    }

    public function test_upload_sets_status_preserves_files_and_prints_in_master_order(): void
    {
        $builder = app(LegalBerkasPdf::class);
        $pdf = $builder->create();
        $pdf->AddPage(); $pdf->Text(10, 10, 'FIRST DOCUMENT');
        $pdf->AddPage(); $pdf->Text(10, 10, 'SECOND PAGE');
        $content = $pdf->Output('', 'S');
        $this->putJson('/_test/legal/1', [
            'status_berkas' => [1 => 0, 2 => 0],
            'file_berkas' => [1 => UploadedFile::fake()->image('scan.png'), 2 => UploadedFile::fake()->createWithContent('pages.pdf', $content)],
        ])->assertOk();
        $row = DB::table('persyaratan_legal')->first();
        $files = json_decode($row->file_jenis_berkas, true);
        $this->assertSame([1 => 1, 2 => 1], json_decode($row->status_jenis_berkas, true));
        Storage::disk('local')->assertExists($files[1]['path']);
        $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 0, 2 => 0]])->assertOk();
        $this->assertSame($files, json_decode(DB::table('persyaratan_legal')->value('file_jenis_berkas'), true));
        $response = $this->get('/_test/legal/1/print')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Storage::disk('local')->put('combined.pdf', $response->getContent());
        $reader = $builder->create();
        $this->assertSame(3, $reader->setSourceFile(Storage::disk('local')->path('combined.pdf')));
        $reader->importPage(1); $reader->importPage(2); $reader->importPage(3);
        // Verify the controller traverses master order rather than upload key order.
        $seen = [];
        $spy = \Mockery::mock(LegalBerkasPdf::class)->makePartial();
        $spy->shouldReceive('append')->twice()->andReturnUsing(function ($pdf, $path) use (&$seen, $builder) {
            $seen[] = $path; $builder->append($pdf, $path);
        });
        $this->app->instance(LegalBerkasPdf::class, $spy);
        $this->get('/_test/legal/1/print')->assertOk();
        $this->assertSame([Storage::disk('local')->path($files[2]['path']), Storage::disk('local')->path($files[1]['path'])], $seen);
    }

    public function test_invalid_kind_and_file_are_rejected_and_empty_print_is_clear(): void
    {
        $this->putJson('/_test/legal/1', ['status_berkas' => [999 => 1]])->assertUnprocessable();
        $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 0], 'file_berkas' => [1 => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')]])->assertUnprocessable();
        $this->get('/_test/legal/1/print')->assertStatus(422);
    }
    public function test_customer_upload_appears_in_legal_and_customer_list_and_delete_clears_both(): void
    {
        $this->putJson('/_test/customer/1/upload', [
            'jenis_berkas_id' => 1, 'lampiran' => UploadedFile::fake()->image('shared.png'),
        ])->assertOk();
        $row = DB::table('persyaratan_legal')->first();
        $files = json_decode($row->file_jenis_berkas, true);
        $this->assertSame(1, json_decode($row->status_jenis_berkas, true)[1]);
        $this->getJson('/_test/customer/1/files?type=files', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.nama_file', 'Dokumen A (Legal)');
        $this->deleteJson('/_test/legal/1/file/1')->assertOk();
        Storage::disk('local')->assertMissing($files[1]['path']);
        $this->assertSame(0, json_decode(DB::table('persyaratan_legal')->value('status_jenis_berkas'), true)[1]);
        $this->getJson('/_test/customer/1/files?type=files', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_original_size_is_centered_on_a4_and_full_page_enlarges_small_pdf(): void
    {
        $builder = app(LegalBerkasPdf::class);
        $source = $builder->create();
        $source->AddPage('P', [50, 80]); $source->Text(5, 5, 'Small document');
        Storage::disk('local')->put('small.pdf', $source->Output('', 'S'));
        $path = Storage::disk('local')->path('small.pdf');
        $original = $builder->create(); $builder->append($original, $path, true);
        $full = $builder->create(); $builder->append($full, $path, false);
        $this->assertEqualsWithDelta(210, $original->getPageWidth(), 0.1);
        $this->assertEqualsWithDelta(297, $original->getPageHeight(), 0.1);
        $original->SetCompression(false); $full->SetCompression(false);
        $this->assertNotSame($original->Output('', 'S'), $full->Output('', 'S'));
    }

    public function test_existing_customer_file_can_be_selected_but_other_customer_file_cannot(): void
    {
        $name = 'test-legal-' . uniqid() . '.pdf';
        $path = public_path('assets/customer/' . $name);
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $pdf = app(LegalBerkasPdf::class)->create(); $pdf->AddPage(); $pdf->Text(10, 10, 'Customer attachment');
        file_put_contents($path, $pdf->Output('', 'S'));
        try {
            DB::table('upload_file')->insert(['id' => 1, 'id_customer' => 2, 'nama_file' => 'Existing file', 'lampiran' => $name]);
            $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 0], 'pilih_berkas' => [1 => 1]])->assertUnprocessable();
            DB::table('upload_file')->where('id', 1)->update(['id_customer' => 1]);
            $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 0], 'pilih_berkas' => [1 => 1]])->assertOk();
            $files = json_decode(DB::table('persyaratan_legal')->value('file_jenis_berkas'), true);
            Storage::disk('local')->assertExists($files[1]['path']);
            $this->get('/_test/legal/1/print?ukuran_asli=1')->assertOk();
            $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 1], 'hapus_berkas' => [1]])->assertOk();
            Storage::disk('local')->assertMissing($files[1]['path']);
            $this->assertFileExists($path);
            $this->assertDatabaseHas('upload_file', ['id' => 1]);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

}
