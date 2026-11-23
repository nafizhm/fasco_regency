<?php

namespace App\Http\Controllers;

use App\Models\WorkPriority;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;

class WorkPriorityController extends Controller
{
    use LogAktivitasTrait;

    public function store(Request $request)
    {
        $data = $request->validateWithBag('priorityWork', [
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:tinggi,normal'],
        ]);
        $work = WorkPriority::create($data);
        $this->logCreate('Prioritas Pekerjaan', $work->id);

        return redirect()->to(route('beranda.index') . '#work-priorities')->with('work_success', 'Pekerjaan berhasil ditambahkan.');
    }

    public function update(Request $request, WorkPriority $workPriority)
    {
        abort_unless($workPriority->delegated_to === null, 404);
        $data = $request->validateWithBag('priorityWork', ['status' => ['required', 'in:proses,selesai']]);
        $workPriority->update($data);
        $this->logEdit('Prioritas Pekerjaan', $workPriority->id);

        return redirect()->to(route('beranda.index') . '#work-priorities')->with('work_success', 'Status pekerjaan berhasil diperbarui.');
    }

    public function storeDelegation(Request $request)
    {
        $data = $request->validateWithBag('delegationWork', [
            'title' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:tinggi,normal'],
            'delegated_to' => ['required', 'string', 'max:200'],
        ]);
        $work = WorkPriority::create($data);
        $this->logCreate('Delegasi Pekerjaan', $work->id);
        return redirect()->to(route('beranda.index').'#work-delegations')->with('work_success', 'Delegasi pekerjaan berhasil ditambahkan.');
    }

    public function updateDelegation(Request $request, WorkPriority $workPriority)
    {
        abort_if($workPriority->delegated_to === null, 404);
        $data = $request->validateWithBag('delegationWork', ['status' => ['required', 'in:proses,selesai']]);
        $workPriority->update($data);
        $this->logEdit('Delegasi Pekerjaan', $workPriority->id);
        return redirect()->to(route('beranda.index').'#work-delegations')->with('work_success', 'Status delegasi berhasil diperbarui.');
    }
}
