<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\SPPRController;
use App\Models\SPPR;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpprTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('id_kavling')->nullable();
            $table->integer('id_lokasi');
        });
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_cluster')->default(false);
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kavling');
            $table->integer('luas_bangunan');
            $table->integer('luas_tanah');
        });
        (require database_path('migrations/2026_06_06_083533_create_sppr_table.php'))->up();
        (require database_path('migrations/2026_07_14_100000_add_fields_to_sppr_table.php'))->up();
        Schema::table('sppr', function (Blueprint $table) {
            $table->string('no_sppr')->nullable();
            $table->string('penandatangan')->nullable();
            $table->integer('id_marketing')->nullable();
            $table->text('keterangan')->nullable();
        });
        (require database_path('migrations/2026_10_03_100000_remove_unused_columns_from_sppr_table.php'))->up();
        DB::table('lokasi_kavling')->insert(['id' => 1]);
        DB::table('kavling_peta')->insert(['id' => 1, 'kode_kavling' => 'B-7', 'luas_bangunan' => 80, 'luas_tanah' => 84]);
        DB::table('customer')->insert(['id' => 1, 'id_kavling' => 1, 'id_lokasi' => 1]);
        Route::post('/_test/sppr', [SPPRController::class, 'store']);
        Route::put('/_test/sppr/{id}', [SPPRController::class, 'update']);
        Route::get('/_test/sppr/{id}/edit', [SPPRController::class, 'edit']);
    }

    private function payload(): array
    {
        return [
            'id_customer' => 1, 'no_sppr' => '007', 'nama' => 'Pembeli & Keluarga',
            'alamat' => 'Jalan Sukamanah RT 06 RW 12 Kelurahan Sukamanah Kecamatan Cipedes Tasikmalaya',
            'nik' => '3278021103060003', 'no_telp' => '081234567890',
            'harga_jual' => 655000000, 'nominal_dp' => 65500000,
            'asumsi_plafon_kpr' => 589500000,
            'luas_bangunan' => 999, 'luas_tanah' => 999,
            'biaya_surat_surat' => 999999,
        ];
    }

    public function test_add_and_edit_only_need_template_fields_and_use_current_kavling(): void
    {
        $this->postJson('/_test/sppr', $this->payload())->assertOk();
        $this->assertFalse(Schema::hasColumn('sppr', 'biaya_surat_surat'));
        $this->assertFalse(Schema::hasColumn('sppr', 'total_yang_harus_dibayar'));
        $this->assertDatabaseHas('sppr', ['id' => 1, 'luas_bangunan' => 80, 'luas_tanah' => 84]);
        DB::table('kavling_peta')->where('id', 1)->update(['luas_tanah' => 90]);
        $this->getJson('/_test/sppr/1/edit')->assertOk()->assertJsonPath('data.luas_tanah', 90);
        $this->putJson('/_test/sppr/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('sppr', ['id' => 1, 'luas_tanah' => 90]);
    }

    public function test_customer_without_kavling_cannot_save(): void
    {
        DB::table('customer')->where('id', 1)->update(['id_kavling' => null]);
        $this->postJson('/_test/sppr', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('id_customer');
    }

    public function test_print_escapes_text_fills_all_placeholders_and_keeps_word_layout(): void
    {
        $this->postJson('/_test/sppr', $this->payload())->assertOk();
        SPPR::findOrFail(1)->update(['created_at' => '2026-10-03 08:00:00']);
        DB::table('kavling_peta')->where('id', 1)->update(['luas_tanah' => 90]);
        $response = app(SPPRController::class)->cetak(1);
        $path = $response->getFile()->getPathname();
        try {
            $zip = new \ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('word/document.xml');
            $document = new \DOMDocument;
            $this->assertTrue($document->loadXML($xml));
            $this->assertStringNotContainsString('${', $xml);
            $this->assertStringContainsString('Pembeli &amp; Keluarga', $xml);
            $this->assertStringContainsString('80/90', $xml);
            $this->assertStringContainsString('007/SPR-PSR/X/2026', $xml);
            $this->assertStringContainsString('3 Oktober 2026', $xml);
            foreach (['655.000.000', '65.500.000', '589.500.000'] as $amount) {
                $this->assertStringContainsString($amount, $xml);
            }
            $template = new \ZipArchive;
            $template->open(public_path('templates/template_sppr/template_sppr.docx'));
            $original = $template->getFromName('word/document.xml');
            // Only text content may change; table geometry, tabs, runs and paragraph properties stay intact.
            $stripText = fn ($value) => preg_replace('/(<w:t\b[^>]*>).*?(<\/w:t>)/s', '$1$2', $value);
            $this->assertSame($stripText($original), $stripText($xml));
            foreach (['word/styles.xml', 'word/header1.xml', 'word/footer1.xml'] as $part) {
                $this->assertSame($template->getFromName($part), $zip->getFromName($part));
            }
            $template->close();
            $zip->close();
        } finally {
            unlink($path);
        }
    }
}
