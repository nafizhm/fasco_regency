<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\ReceiptNotes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerReceiptNoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $t) {
            $t->id(); $t->string('nama_lengkap'); $t->text('catatan_kwitansi')->nullable();
        });
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->integer('id_menu'); $t->boolean('lihat'); $t->boolean('edit');
        });
        Schema::create('log_aktivitas_pengguna', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->string('user_name'); $t->text('aktivitas');
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'customer-receipt-notes.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1, 'lihat' => 1, 'edit' => 1]);
        DB::table('customer')->insert([
            ['id' => 1, 'nama_lengkap' => 'CUSTOMER SATU'],
            ['id' => 2, 'nama_lengkap' => 'CUSTOMER DUA'],
        ]);
        $this->actingAs((new User)->forceFill(['id' => 1, 'username' => 'tester']));
    }

    public function test_custom_notes_are_saved_per_customer_and_split_into_points(): void
    {
        $this->put(route('customer-receipt-notes.update', 1), ['use_default' => 0, 'notes' => "Catatan khusus\n\nPajak sesuai kesepakatan"])->assertRedirect();
        $this->assertSame(['Catatan khusus', 'Pajak sesuai kesepakatan'], ReceiptNotes::lines(Customer::findOrFail(1)));
        $this->assertNull(Customer::findOrFail(2)->catatan_kwitansi);
        $this->assertCount(6, ReceiptNotes::lines(Customer::findOrFail(2)));
    }

    public function test_blank_custom_notes_hide_notes_and_default_can_be_restored(): void
    {
        $url = route('customer-receipt-notes.update', 1);
        $this->put($url, ['use_default' => 0, 'notes' => ''])->assertRedirect();
        $this->assertSame([], ReceiptNotes::lines(Customer::findOrFail(1)));
        $this->put($url, ['use_default' => 1, 'notes' => 'ignored'])->assertRedirect();
        $this->assertNull(Customer::findOrFail(1)->catatan_kwitansi);
        $this->assertCount(6, ReceiptNotes::lines(Customer::findOrFail(1)));
    }

    public function test_view_only_users_cannot_modify_notes(): void
    {
        DB::table('hak_akses')->update(['edit' => 0]);
        $this->putJson(route('customer-receipt-notes.update', 1), ['use_default' => 0, 'notes' => 'Tidak boleh'])->assertForbidden();
        $this->assertNull(Customer::findOrFail(1)->catatan_kwitansi);
    }

    public function test_note_length_and_default_mode_are_validated(): void
    {
        $this->putJson(route('customer-receipt-notes.update', 1), ['use_default' => 'invalid', 'notes' => str_repeat('x', 10001)])
            ->assertUnprocessable()->assertJsonValidationErrors(['use_default', 'notes']);
    }
}
