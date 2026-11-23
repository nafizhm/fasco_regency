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
        (require database_path('migrations/2026_11_23_020000_add_delegated_to_to_work_priorities.php'))->up();
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

    public function test_delegation_requires_employee_and_has_separate_tasks_and_status(): void
    {
        $controller = app(WorkPriorityController::class);
        $controller->store(Request::create('/', 'POST', ['title'=>'Tugas internal', 'priority'=>'normal']));
        try {
            $controller->storeDelegation(Request::create('/', 'POST', ['title'=>'Cek lokasi', 'priority'=>'tinggi']));
            $this->fail('Delegation without employee was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delegated_to', $exception->errors());
            $this->assertSame('delegationWork', $exception->errorBag);
        }
        $response = $controller->storeDelegation(Request::create('/', 'POST', [
            'title'=>'Cek lokasi', 'priority'=>'tinggi', 'notes'=>'Foto progres unit', 'delegated_to'=>'Naufal',
        ]));
        $this->assertStringEndsWith('#work-delegations', $response->getTargetUrl());
        $delegation = WorkPriority::whereNotNull('delegated_to')->firstOrFail();
        $this->assertSame('Naufal', $delegation->delegated_to);
        $this->assertSame('proses', $delegation->status);
        $this->assertSame(1, WorkPriority::whereNull('delegated_to')->count());
        foreach (['selesai','proses'] as $status) {
            $controller->updateDelegation(Request::create('/', 'PATCH', ['status'=>$status]), $delegation);
            $this->assertSame($status, $delegation->fresh()->status);
        }
        $this->view('admin.beranda.work-priorities', [
            'errors'=>new \Illuminate\Support\ViewErrorBag,
            'workPriorities'=>WorkPriority::whereNull('delegated_to')->get(),
            'workDelegations'=>WorkPriority::whereNotNull('delegated_to')->get(),
        ])->assertSee('Prioritas Pekerjaan')->assertSee('Delegasi Pekerjaan')
            ->assertSee('Naufal')->assertSee('Nama Karyawan yang Didelegasikan')
            ->assertSee(route('work-delegations.store'));
        (require database_path('migrations/2026_11_23_020000_add_delegated_to_to_work_priorities.php'))->up();
        $this->assertSame(2, WorkPriority::count());
    }

    public function test_delegated_task_cannot_be_updated_through_priority_route(): void
    {
        $work = WorkPriority::create(['title'=>'Tugas', 'delegated_to'=>'Naufal']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkPriorityController::class)->update(Request::create('/', 'PATCH', ['status'=>'selesai']), $work);
    }
}
