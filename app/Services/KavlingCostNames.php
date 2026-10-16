<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KavlingCostNames
{
    public static function canonical(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim(str_replace("\u{00A0}", ' ', $name)));
        return match (Str::lower($name)) {
            'kelebihan tanah', 'lekebihan tanah' => 'Kelebihan Tanah',
            'harga rumah' => 'Harga Rumah',
            'biaya surat' => 'Biaya Surat',
            'peningkatan mutu' => 'Peningkatan Mutu',
            default => $name,
        };
    }

    public static function key(string $name): string
    {
        return Str::lower(self::canonical($name));
    }

    public static function uniqueNames($names): array
    {
        $result = [];
        foreach ($names as $name) $result[self::key($name)] ??= self::canonical($name);
        return array_values($result);
    }

    public static function mergeValues(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $name = self::canonical($item['nama']);
            $key = self::key($name);
            $value = (int) $item['nilai'];
            if (isset($result[$key]) && $result[$key]['nilai'] !== 0 && $value !== 0 && $result[$key]['nilai'] !== $value) {
                throw ValidationException::withMessages(['file' => "Komponen {$name} memiliki nilai berbeda pada kolom/nama duplikat. Samakan nilainya sebelum melanjutkan."]);
            }
            if (!isset($result[$key]) || $value !== 0) $result[$key] = ['nama' => $name, 'nilai' => $value];
        }
        return array_values($result);
    }
}
