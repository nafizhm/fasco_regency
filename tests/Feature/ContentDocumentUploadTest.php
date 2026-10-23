<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentDocumentUploadTest extends TestCase
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
        });
        Schema::create('content_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->string('original_name');
            $table->string('path')->unique();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'content-documents.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1, 'lihat' => 1, 'tambah' => 1]);
        $user = new User();
        $user->id = 1;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        foreach ($this->uploads as $id) {
            File::delete(storage_path('app/content-uploads/'.$id.'.json'),
                storage_path('app/content-uploads/'.$id.'.part'), storage_path('app/content-documents/'.$id));
        }
        parent::tearDown();
    }

    public function test_multipart_upload_finishes_with_string_size_and_retried_chunk(): void
    {
        $content = "%PDF-1.4\n".str_repeat('document content ', 80000)."\n%%EOF";
        $start = $this->post(route('content-documents.begin'), [
            'title' => 'Dokumentasi', 'category' => 'Progres', 'name' => 'document.pdf', 'size' => (string) strlen($content),
        ], ['Accept' => 'application/json'])->assertOk();
        $id = $start->json('id');
        $this->uploads[] = $id;
        $first = substr($content, 0, 1048576);
        foreach ([0, 0, 1048576] as $offset) {
            $part = $offset === 0 ? $first : substr($content, $offset);
            $this->post(route('content-documents.chunk', $id), [
                'offset' => (string) $offset, 'chunk' => UploadedFile::fake()->createWithContent('chunk.bin', $part),
            ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('offset', $offset + strlen($part));
        }
        $this->postJson(route('content-documents.finish', $id))->assertOk();
        $this->assertDatabaseHas('content_documents', ['path' => $id, 'size' => strlen($content)]);
        $this->assertSame($content, File::get(storage_path('app/content-documents/'.$id)));
        $this->postJson(route('content-documents.finish', $id))->assertOk();
        $this->assertSame(1, DB::table('content_documents')->count());
    }

    public function test_incomplete_upload_is_still_rejected(): void
    {
        $start = $this->post(route('content-documents.begin'), [
            'title' => 'Dokumentasi', 'category' => 'Progres', 'name' => 'document.pdf', 'size' => '100',
        ], ['Accept' => 'application/json'])->assertOk();
        $id = $start->json('id');
        $this->uploads[] = $id;
        $this->post(route('content-documents.chunk', $id), [
            'offset' => '0', 'chunk' => UploadedFile::fake()->createWithContent('chunk.bin', '%PDF-1.4'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->postJson(route('content-documents.finish', $id))->assertUnprocessable();
        $this->assertSame(0, DB::table('content_documents')->count());
    }
}
