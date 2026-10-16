<?php

namespace Tests\Feature;

use App\Models\KavlingPeta;
use App\Services\DocumentDataContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KavlingDimensionsTest extends TestCase
{
    public function test_migration_preserves_dimensions_and_old_document_placeholders(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Storage::fake('local');
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            foreach (['panjang_kanan', 'panjang_kiri', 'lebar_depan', 'lebar_belakang'] as $column) {
                $table->double($column)->nullable();
            }
        });
        DB::table('kavling_peta')->insert([
            'id' => 1, 'panjang_kanan' => 12.5, 'panjang_kiri' => 13,
            'lebar_depan' => 6.5, 'lebar_belakang' => 7,
        ]);
        $migration = require database_path('migrations/2026_10_07_000000_simplify_kavling_dimensions.php');
        $migration->up();
        $this->assertTrue(Schema::hasColumn('kavling_peta', 'panjang'));
        $this->assertTrue(Schema::hasColumn('kavling_peta', 'lebar'));
        $this->assertFalse(Schema::hasColumn('kavling_peta', 'panjang_kiri'));
        $this->assertFalse(Schema::hasColumn('kavling_peta', 'lebar_belakang'));
        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $backup = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertEquals(13, $backup[0]['panjang_kiri']);
        $this->assertEquals(7, $backup[0]['lebar_belakang']);
        $kavling = KavlingPeta::findOrFail(1);
        $this->assertEquals(12.5, $kavling->panjang);
        $this->assertEquals(6.5, $kavling->lebar);
        $kavling->update(['panjang' => 14.5, 'lebar' => 8]);
        $context = DocumentDataContext::fromKavlingPeta($kavling->fresh());
        $this->assertSame('14.5', $context['panjang']);
        $this->assertEquals(8, $context['lebar']);
        $this->assertSame($context['panjang'], $context['panjang_kanan']);
        $this->assertSame($context['panjang'], $context['panjang_kiri']);
        $this->assertSame($context['lebar'], $context['lebar_depan']);
        $this->assertSame($context['lebar'], $context['lebar_belakang']);
        // Already-renamed databases (manual SQL) must be accepted too.
        $migration->up();
        $this->assertEquals(14.5, KavlingPeta::findOrFail(1)->panjang);
    }
}
