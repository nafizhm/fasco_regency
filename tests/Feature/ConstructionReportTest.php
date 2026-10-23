<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConstructionReportTest extends TestCase
{
    private array $uploads = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_menu');
            $table->boolean('lihat');
            $table->boolean('tambah');
            $table->boolean('hapus')->default(false);
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
            $table->string('kode_kavling');
            $table->string('nama_cluster')->nullable();
        });
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
            $table->integer('urutan')->default(1);
        });
        Schema::create('master_svg', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
        });
        Schema::create('construction_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('upload_id')->unique();
            $table->integer('unit_id');
            $table->integer('user_id');
            $table->date('reported_at');
            $table->decimal('percentage', 5, 2);
            $table->text('notes');
            $table->json('photos');
            $table->timestamps();
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'construction.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1, 'lihat' => 1, 'tambah' => 1]);
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Naufal Hills']);
        DB::table('kavling_peta')->insert(['id' => 1, 'id_lokasi' => 1, 'kode_kavling' => 'A-01']);
        $user = new User();
        $user->id = 1;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        foreach ($this->uploads as $id) {
            File::deleteDirectory(storage_path('app/construction-uploads/'.$id));
        }
        parent::tearDown();
    }

    private function begin(array $changes = []): string
    {
        $response = $this->postJson(route('construction.begin'), array_replace([
            'unit_id' => 1, 'reported_at' => now('Asia/Jakarta')->toDateString(),
            'percentage' => 45.5, 'notes' => 'Pemasangan atap',
            'photos' => [['name' => 'lapangan.png', 'size' => strlen($this->image())]],
        ], $changes))->assertOk();
        return $this->uploads[] = $response->json('id');
    }

    private function image(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII=');
    }

    public function test_upload_can_retry_chunks_and_finish_without_duplicate_reports(): void
    {
        $id = $this->begin();
        foreach ([0, 0, 20] as $offset) {
            $bytes = $offset === 0 ? substr($this->image(), 0, 20) : substr($this->image(), 20);
            $this->post(route('construction.chunk', [$id, 0]), [
                'offset' => (string) $offset,
                'chunk' => UploadedFile::fake()->createWithContent('chunk.bin', $bytes),
            ], ['Accept' => 'application/json'])->assertOk();
        }
        $report = $this->postJson(route('construction.finish', $id))->assertOk()->json('id');
        $this->postJson(route('construction.finish', $id))->assertOk();
        $this->assertDatabaseCount('construction_reports', 1);
        $this->get(route('construction.photo', [$report, 0]))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->getJson(route('construction.history', 1))->assertOk()
            ->assertJsonPath('reports.data.0.notes', 'Pemasangan atap');
    }

    public function test_incomplete_and_non_image_uploads_are_rejected(): void
    {
        $id = $this->begin();
        $this->postJson(route('construction.finish', $id))->assertUnprocessable();
        $id = $this->begin(['photos' => [['name' => 'fake.png', 'size' => 5]]]);
        $this->post(route('construction.chunk', [$id, 0]), [
            'offset' => 0, 'chunk' => UploadedFile::fake()->createWithContent('chunk.bin', 'hello'),
        ])->assertOk();
        $this->postJson(route('construction.finish', $id))->assertUnprocessable();
        $this->assertDatabaseCount('construction_reports', 0);
    }

    public function test_view_only_users_cannot_upload_and_others_cannot_write_upload_chunks(): void
    {
        $id = $this->begin();
        $user = new User();
        $user->id = 2;
        DB::table('hak_akses')->insert(['id_user' => 2, 'id_menu' => 1, 'lihat' => 1, 'tambah' => 1]);
        $this->actingAs($user)->postJson(route('construction.finish', $id))->assertForbidden();
        DB::table('hak_akses')->where('id_user', 2)->update(['tambah' => 0]);
        $this->postJson(route('construction.begin'))->assertForbidden();
        DB::table('hak_akses')->where('id_user', 2)->update(['lihat' => 0]);
        $this->getJson(route('construction.history', 1))->assertForbidden();
    }

    public function test_history_sorts_by_report_date_and_paginates_long_histories(): void
    {
        foreach (range(1, 10) as $day) {
            DB::table('construction_reports')->insert([
                'upload_id' => (string) \Illuminate\Support\Str::uuid(), 'unit_id' => 1, 'user_id' => 1,
                'reported_at' => sprintf('2026-01-%02d', $day), 'percentage' => $day * 10,
                'notes' => 'Laporan '.$day, 'photos' => '[]',
            ]);
        }
        $this->getJson(route('construction.history', 1))->assertOk()
            ->assertJsonPath('reports.total', 10)->assertJsonCount(8, 'reports.data')
            ->assertJsonPath('reports.data.0.notes', 'Laporan 10');
        $this->getJson(route('construction.history', ['unit' => 1, 'page' => 2]))->assertOk()
            ->assertJsonCount(2, 'reports.data')->assertJsonPath('reports.data.0.notes', 'Laporan 2');
    }

    public function test_cluster_filter_and_history_distinguish_identical_unit_numbers(): void
    {
        DB::table('kavling_peta')->where('id', 1)->update(['nama_cluster' => 'JINGGA']);
        DB::table('kavling_peta')->insert(['id' => 2, 'id_lokasi' => 1, 'kode_kavling' => 'A-01', 'nama_cluster' => 'BIRU']);
        $request = \Illuminate\Http\Request::create('/admin/progres-pembangunan', 'GET', ['cluster' => 'BIRU']);
        $data = app(\App\Http\Controllers\ConstructionReportController::class)->index($request)->getData();
        $this->assertSame(1, $data['units']->total());
        $this->assertSame(2, $data['units']->first()->id);
        $this->assertSame('BIRU', $data['units']->first()->nama_cluster);
        $this->getJson(route('construction.history', 1))->assertOk()->assertJsonPath('unit.nama_cluster', 'JINGGA');
        $this->getJson(route('construction.history', 2))->assertOk()->assertJsonPath('unit.nama_cluster', 'BIRU');
    }

    private function previousReport(float $percentage): void
    {
        DB::table('construction_reports')->insert([
            'upload_id' => (string) \Illuminate\Support\Str::uuid(), 'unit_id' => 1, 'user_id' => 1,
            'reported_at' => now('Asia/Jakarta')->toDateString(), 'percentage' => $percentage,
            'notes' => 'Progres sebelumnya', 'photos' => '[]',
        ]);
    }

    public function test_deletion_requires_permission_and_restores_previous_progress(): void
    {
        $this->previousReport(10);
        $this->previousReport(20);
        $id = DB::table('construction_reports')->max('id');
        $this->deleteJson(route('construction.destroy', $id))->assertForbidden();
        DB::table('hak_akses')->where('id_user', 1)->update(['hapus' => 1]);
        $this->getJson(route('construction.history', 1))->assertJsonPath('can_delete', true);
        $this->deleteJson(route('construction.destroy', $id))->assertOk();
        $this->assertDatabaseCount('construction_reports', 1);
        $this->begin(['percentage' => 10]);
    }

    public function test_percentage_cannot_drop_below_latest_report_but_can_equal_it(): void
    {
        $this->previousReport(45.5);
        $this->postJson(route('construction.begin'), [
            'unit_id' => 1, 'reported_at' => now('Asia/Jakarta')->toDateString(),
            'percentage' => 45.49, 'notes' => 'Terlalu rendah',
            'photos' => [['name' => 'lapangan.png', 'size' => strlen($this->image())]],
        ])->assertUnprocessable()->assertJsonValidationErrors('percentage');
        $this->begin(['percentage' => 45.5]);
        $this->begin(['percentage' => 60]);
    }

    public function test_finish_rechecks_percentage_when_another_report_arrives_during_upload(): void
    {
        $id = $this->begin(['percentage' => 45.5]);
        $this->post(route('construction.chunk', [$id, 0]), [
            'offset' => 0, 'chunk' => UploadedFile::fake()->createWithContent('chunk.bin', $this->image()),
        ])->assertOk();
        $this->previousReport(50);
        $this->postJson(route('construction.finish', $id))->assertUnprocessable()->assertJsonValidationErrors('percentage');
        $this->assertDatabaseCount('construction_reports', 1);
    }
}
