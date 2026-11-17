<?php

namespace Tests\Feature;

use App\Http\Controllers\WorkPriorityController;
use App\Models\WorkPriority;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkPriorityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_11_17_050000_create_work_priorities_table.php'))->up();
    }

    public function test_work_is_persisted_and_can_be_completed_and_reopened(): void
    {
        $controller = app(WorkPriorityController::class);
        $controller->store(Request::create('/', 'POST', ['title' => 'Follow up KPR', 'notes' => 'Hubungi bank', 'priority' => 'tinggi']));
        $work = WorkPriority::firstOrFail();
        $this->assertSame('proses', $work->status);
        $this->assertSame('Follow up KPR', $work->title);
        $this->assertSame('Hubungi bank', $work->notes);
        foreach (['selesai', 'proses'] as $status) {
            $controller->update(Request::create('/', 'PATCH', ['status' => $status]), $work);
            $this->assertSame($status, $work->fresh()->status);
        }
    }

    public function test_invalid_input_is_rejected(): void
    {
        $controller = app(WorkPriorityController::class);
        try {
            $controller->store(Request::create('/', 'POST', ['title' => '', 'priority' => 'invalid']));
            $this->fail('Invalid work was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
            $this->assertArrayHasKey('priority', $exception->errors());
        }
        $this->assertSame(0, WorkPriority::count());
        $work = WorkPriority::create(['title' => 'Pekerjaan', 'priority' => 'normal']);
        $this->expectException(ValidationException::class);
        $controller->update(Request::create('/', 'PATCH', ['status' => 'invalid']), $work);
    }
}
