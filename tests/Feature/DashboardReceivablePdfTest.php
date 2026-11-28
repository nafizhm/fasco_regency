<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\DashboardReceivableService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class DashboardReceivablePdfTest extends TestCase
{
    public function test_pdf_follows_due_filter_and_selected_rows(): void
    {
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $a = new Customer;
        $a->forceFill(['id' => 1, 'nama_lengkap' => 'Customer A', 'total_tagihan' => 3000000, 'total_bayar' => 1000000, 'sisa_piutang' => 2000000, 'tanggal_jatuh_tempo' => '2026-11-01']);
        $a->setRelation('kavling', null);
        $b = clone $a;
        $b->id = 2;
        $b->nama_lengkap = 'Customer B';
        $this->mock(DashboardReceivableService::class)->shouldReceive('customers')->once()->with(['lokasi', 'kavling'], true)->andReturn(new Collection([$a, $b]));
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $values) {
            $this->assertSame('admin.dashboard.piutang-pdf', $view);
            $this->assertSame([2], $values['customers']->pluck('id')->all());
            $this->assertTrue($values['dueOnly']);
            $html = view($view, $values)->render();
            $this->assertStringContainsString('Customer B', $html);
            $this->assertStringNotContainsString('Customer A', $html);
            $this->assertStringContainsString('2.000.000', $html);

            return true;
        })->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        $this->post(route('dashboard.piutang.pdf'), ['filtered' => 1, 'ids' => [2], 'jatuh_tempo' => 1, 'search' => 'Customer B'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_empty_filtered_pdf_is_valid_and_guest_cannot_print(): void
    {
        $this->post(route('dashboard.piutang.pdf'))->assertRedirect(route('login'));
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $this->mock(DashboardReceivableService::class)->shouldReceive('customers')->once()->with(['lokasi', 'kavling'], false)->andReturn(new Collection);
        $response = $this->post(route('dashboard.piutang.pdf'), ['filtered' => 1])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-',$response->getContent());
    }
}
