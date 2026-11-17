<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerDueDateTest extends TestCase
{
    public function test_due_date_is_saved_for_the_selected_customer_and_invalid_dates_are_rejected(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_jatuh_tempo')->nullable();
        });
        $customer = Customer::create();
        $other = Customer::create();
        $controller = app(PembayaranController::class);
        $controller->updateJatuhTempo(Request::create('/', 'PUT', ['tanggal_jatuh_tempo' => '2026-11-20']), $customer->id);
        $this->assertSame('2026-11-20', $customer->fresh()->tanggal_jatuh_tempo);
        $this->assertNull($other->fresh()->tanggal_jatuh_tempo);

        foreach (['', '2026-02-30', 'invalid'] as $invalid) {
            try {
                $controller->updateJatuhTempo(Request::create('/', 'PUT', ['tanggal_jatuh_tempo' => $invalid]), $customer->id);
                $this->fail('Invalid due date was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('tanggal_jatuh_tempo', $exception->errors());
            }
        }
        $this->assertSame('2026-11-20', $customer->fresh()->tanggal_jatuh_tempo);
    }

    public function test_due_date_labels_cover_future_today_overdue_and_unset_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-17 23:30:00', 'Asia/Jakarta'));
        try {
            foreach ([
                ['2026-11-20', '3 hari lagi'],
                ['2026-11-17', 'Jatuh tempo hari ini'],
                ['2026-11-15', 'Terlambat 2 hari'],
                [null, 'Belum diatur'],
            ] as [$date, $label]) {
                $customer = new Customer();
                $customer->tanggal_jatuh_tempo = $date;
                $html = view('admin.dashboard.receivable-due-date', compact('customer'))->render();
                $this->assertStringContainsString($label, $html);
                if ($date) {
                    $this->assertStringContainsString(Carbon::parse($date)->format('d-m-Y'), $html);
                }
            }
        } finally {
            Carbon::setTestNow();
        }
    }
}
