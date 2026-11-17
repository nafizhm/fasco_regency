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
        $data = $request->validate([
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
        $data = $request->validate(['status' => ['required', 'in:proses,selesai']]);
        $workPriority->update($data);
        $this->logEdit('Prioritas Pekerjaan', $workPriority->id);

        return redirect()->to(route('beranda.index') . '#work-priorities')->with('work_success', 'Status pekerjaan berhasil diperbarui.');
    }
}
