<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KprReceivableReport;
use Illuminate\Support\Collection;
use Tests\TestCase;

class KprReceivablePdfTest extends TestCase
{
    private function rows(int $stages = 4): Collection
    {
        return collect([[
            'customer' => (object) ['id' => 1, 'nama_lengkap' => 'Pengguna KPR', 'kavling' => (object) ['nama_cluster' => 'Cluster Hijau', 'kode_kavling' => 'H.4']],
            'akad' => '2026-11-01', 'biaya_kpr' => 2500000, 'plafon' => 300000000, 'sisa' => 200000000, 'bank' => 'BCA',
            'payments' => collect(range(1, $stages))->map(fn ($stage) => (object) [
                'tanggal' => '2026-11-02', 'nominal' => 25000000, 'persentase_termin' => 8.33, 'plafon_sp3k' => 300000000,
            ]),
        ]]);
    }

    public function test_authenticated_pdf_contains_report_in_landscape(): void
    {
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $rows = $this->rows();
        $this->mock(KprReceivableReport::class)->shouldReceive('rows')->once()->andReturn($rows);
        $html = view('admin.dashboard.piutang-kpr-pdf', ['rows' => $rows, 'stages' => 4])->render();
        foreach (['Pengguna KPR', 'Cluster Hijau', '200.000.000', '25000000', '8.33%'] as $text) {
            if ($text === '25000000') {
                $text = '25.000.000';
            }
            $this->assertStringContainsString($text, $html);
        }
        $response = $this->get(route('dashboard.piutang-kpr.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/', $response->getContent(), $size);
        $this->assertNotEmpty($size);
        $this->assertGreaterThan((float) $size[4], (float) $size[3]);
    }

    public function test_pdf_handles_empty_report_and_additional_stages(): void
    {
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $this->mock(KprReceivableReport::class)->shouldReceive('rows')->twice()->andReturn(collect(), $this->rows(5));
        $this->get(route('dashboard.piutang-kpr.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('dashboard.piutang-kpr.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rendered_column_widths_leave_room_for_long_names(): void
    {
        $rows = $this->rows();
        $rows->first()['customer']->nama_lengkap = 'SHOFYAN SANTRIANSYAH';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.dashboard.piutang-kpr-pdf', ['rows' => $rows, 'stages' => 4])->setPaper('a4', 'landscape');
        $cells = [];
        $texts = [];
        $pdf->getDomPDF()->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$cells, &$texts) {
            $node = $frame->get_node();
            if ($node->nodeName === 'td') {
                $class = $node->getAttribute('class');
                if (in_array($class, ['column-number', 'column-block', 'customer-name'])) {
                    $cells[$class] = $frame->get_border_box();
                }
            }
            if ($node->nodeType === XML_TEXT_NODE && trim($node->nodeValue) === 'SHOFYAN SANTRIANSYAH') {
                $texts[] = ['x' => $frame->get_position('x'), 'width' => $frame->get_margin_width()];
            }
        }]]);
        $pdf->output();
        $this->assertLessThan(20, $cells['column-number']['w']);
        $this->assertLessThan(30, $cells['column-block']['w']);
        $this->assertGreaterThan(130, $cells['customer-name']['w']);
        $this->assertNotEmpty($texts);
        foreach ($texts as $text) {
            $this->assertLessThanOrEqual($cells['customer-name']['x'] + $cells['customer-name']['w'], $text['x'] + $text['width']);
        }
    }

    public function test_guest_cannot_print_report(): void
    {
        $this->get(route('dashboard.piutang-kpr.pdf'))->assertRedirect(route('login'));
    }
}
