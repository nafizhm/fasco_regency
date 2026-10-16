<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Sp3kDpService
{
    public function calculate(Request $request): array
    {
        $request->merge(['dp_persen' => str_replace(',', '.', (string) $request->input('dp_persen', ''))]);
        $request->validate([
            'dp_mode' => 'required|in:persentase,nominal',
            'acc_plafon' => ['required', 'regex:/^\d+(?:\.\d{3})*$/'],
            'dp_persen' => ['exclude_unless:dp_mode,persentase', 'required', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,3}(?:\.\d{1,4})?$/'],
            'dp_nominal' => ['exclude_unless:dp_mode,nominal', 'required', 'regex:/^\d+(?:\.\d{3})*$/'],
        ]);
        $base = (int) str_replace('.', '', $request->acc_plafon);
        $percent = $request->dp_mode === 'persentase' ? (float) $request->dp_persen : null;
        if ($percent !== null && $base <= 0) {
            throw ValidationException::withMessages(['acc_plafon' => 'Plafon harus lebih dari nol untuk menghitung DP berdasarkan persentase.']);
        }
        $amount = $percent !== null ? (int) round($base * $percent / 100)
            : (int) str_replace('.', '', $request->dp_nominal);
        return ['dp_mode' => $request->dp_mode, 'dp_persen' => $percent, 'dp_nilai' => $amount,
            'dp_dasar' => $percent !== null ? $base : null];
    }
}
