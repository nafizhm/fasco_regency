<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TukangReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Names transcribed from the supplied attendance sheet. Phone and wages were not provided.
        $names = ['Pak Wari', 'Tarom', 'Agit', 'ainul', 'Amin', 'Andik', 'Andre', 'Budi', 'Dayat',
            'edi', 'Erdin', 'Farhan', 'Imam tukang', 'Jaka', 'Jamal', 'Jarwo', 'Jumari', 'Koni',
            'Mandrak', 'Null', 'Ponadi', 'Rangga', 'Ridwan', 'Riob / Tirto', 'rifki', 'Roni',
            'Saiful (B)', 'Saiful lama', 'Sallem', 'Samadi', 'Samain', 'Samsul', 'Sodiq', 'Sueb',
            'Sugiono', 'Sunarto', 'Susilo', 'Tirto', 'Wardi', 'Yoga', 'Yudi', 'Yulianto', 'Yusron',
            'khusnul', 'wasis', 'Saiful lama (48)'];
        foreach ($names as $name) {
            if (! DB::table('tukang')->where('nama', $name)->exists()) {
                DB::table('tukang')->insert(['nama' => $name, 'no_telp' => null, 'alamat' => null, 'gaji_per_hari' => 0]);
            }
        }
    }
}
