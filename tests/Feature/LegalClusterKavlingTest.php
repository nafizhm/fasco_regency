<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegalClusterKavlingTest extends TestCase
{
    public function test_forms_reject_a_kavling_from_another_cluster(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->withoutMiddleware();
        Schema::create('lokasi_kavling', fn (Blueprint $table) => $table->id());
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_lokasi');
        });
        DB::table('lokasi_kavling')->insert([['id' => 1], ['id' => 2]]);
        DB::table('kavling_peta')->insert(['id' => 10, 'id_lokasi' => 2]);

        foreach (['balik-nama.store', 'bphtb-ssp.store', 'listrik-air.store'] as $route) {
            $this->postJson(route($route), ['id_lokasi' => 1, 'id_kavling' => 10])
                ->assertUnprocessable()->assertJsonValidationErrors('id_kavling');
            $this->postJson(route($route), ['id_lokasi' => 2, 'id_kavling' => 10])
                ->assertUnprocessable()->assertJsonMissingValidationErrors(['id_lokasi', 'id_kavling']);
        }
    }
}
