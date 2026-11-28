<?php
namespace Tests\Feature;

use App\Models\KavlingPeta;
use App\Models\User;
use App\Services\SiteplanColors;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteplanSalesStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('kavling_peta', function (Blueprint $t) { $t->id(); $t->string('siteplan_status')->nullable(); });
        Schema::create('siteplan_sales_colors', function (Blueprint $t) { $t->string('status')->primary(); $t->string('color'); });
    }
    private function loginAs(string $username): void
    {
        $user = new User; $user->id = 1; $user->username = $username;
        $this->actingAs($user);
    }
    public function test_manual_status_and_colors_are_restricted_to_safira(): void
    {
        $unit = KavlingPeta::create();
        $this->loginAs('other');
        $this->get(route('siteplan-status.index'))->assertForbidden();
        $this->patch(route('siteplan-status.update', $unit->id), ['status' => 'sold'])->assertForbidden();
        $this->patch(route('siteplan-status.colors'), ['sold' => '#ffffff', 'proses' => '#ffffff'])->assertForbidden();
        $this->loginAs('SaFiRa');
        $this->patch(route('siteplan-status.update', $unit->id), ['status' => 'hold'])->assertSessionHasErrors('status');
        $this->patch(route('siteplan-status.update', $unit->id), ['status' => 'sold'])->assertRedirect();
        $this->assertSame('sold', $unit->fresh()->siteplan_status);
        $this->patch(route('siteplan-status.colors'), ['sold' => 'red', 'proses' => '#ffffff'])->assertSessionHasErrors('sold');
        $this->patch(route('siteplan-status.colors'), ['sold' => '#123456', 'proses' => '#abcdef'])->assertRedirect();
        $this->assertSame('#123456', SiteplanColors::colors()['sold']);
    }
    public function test_status_labels_colors_and_override(): void
    {
        $unit = new KavlingPeta(['kode_kavling' => 'A1']);
        $unit->setRelation('customer', null); $unit->is_booked = false;
        $this->assertSame('ready', SiteplanColors::status($unit));
        $this->assertSame('A1', SiteplanColors::label($unit));
        $unit->is_booked = true;
        $this->assertSame('proses', SiteplanColors::status($unit));
        $this->assertSame('PROSES', SiteplanColors::label($unit));
        $unit->setRelation('customer', new \App\Models\Customer);
        $this->assertSame('sold', SiteplanColors::status($unit));
        $this->assertSame('SOLD', SiteplanColors::label($unit));
        $this->assertSame('#ef4444', SiteplanColors::sales($unit, '#000000'));
        $unit->siteplan_status = 'ready';
        $this->assertSame('A1', SiteplanColors::label($unit));
        $this->assertSame('#ffffff', SiteplanColors::sales($unit, '#000000', true));
    }
}
