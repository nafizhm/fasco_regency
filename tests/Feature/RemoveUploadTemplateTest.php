<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveUploadTemplateTest extends TestCase
{
    public function test_removal_drops_templates_and_only_removes_related_menu_permissions(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('route_name');
        });
        foreach (['hak_akses', 'role_user'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_menu')->constrained('menu');
            });
        }
        (require database_path('migrations/2026_07_05_000001_create_document_templates_table.php'))->up();
        DB::table('menu')->insert([
            ['id' => 1, 'title' => 'Upload Template', 'route_name' => 'upload-template.index'],
            ['id' => 2, 'title' => 'Customer', 'route_name' => 'customer.index'],
        ]);
        foreach (['hak_akses', 'role_user'] as $name) {
            DB::table($name)->insert([['id_menu' => 1], ['id_menu' => 2]]);
        }

        $migration = require database_path('migrations/2026_10_08_000000_remove_upload_template_module.php');
        $migration->up();
        $migration->up();

        $this->assertFalse(Schema::hasTable('document_templates'));
        $this->assertDatabaseMissing('menu', ['id' => 1]);
        $this->assertDatabaseHas('menu', ['id' => 2]);
        foreach (['hak_akses', 'role_user'] as $name) {
            $this->assertDatabaseMissing($name, ['id_menu' => 1]);
            $this->assertDatabaseHas($name, ['id_menu' => 2]);
        }
        $this->assertFalse(Route::has('upload-template.index'));
        $this->assertFalse(Route::has('customer.print-document'));
    }
}
