<?php

namespace App\Services;

class FeeMarketingWords
{
    public static function rupiah(int $number): string
    {
        return ($number === 0 ? 'Nol' : self::spell($number)).' Rupiah';
    }

    private static function spell(int $n): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if ($n < 12) return $words[$n];
        if ($n < 20) return self::spell($n - 10).' Belas';
        if ($n < 100) return trim(self::spell(intdiv($n, 10)).' Puluh '.self::spell($n % 10));
        if ($n < 200) return trim('Seratus '.self::spell($n - 100));
        if ($n < 1000) return trim(self::spell(intdiv($n, 100)).' Ratus '.self::spell($n % 100));
        if ($n < 2000) return trim('Seribu '.self::spell($n - 1000));
        foreach ([1000000000000 => 'Triliun', 1000000000 => 'Miliar', 1000000 => 'Juta', 1000 => 'Ribu'] as $base => $label) {
            if ($n >= $base) return trim(self::spell(intdiv($n, $base)).' '.$label.' '.self::spell($n % $base));
        }
        return '';
    }
}
