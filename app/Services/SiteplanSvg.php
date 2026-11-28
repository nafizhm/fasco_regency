<?php

namespace App\Services;

use DOMDocument;
use RuntimeException;

class SiteplanSvg
{
    public static function salesUnit(\App\Models\KavlingPeta $unit, string $template, string $color): string
    {
        $markup = str_replace(['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
            [$unit->map, $color, $unit->matrik, htmlspecialchars(SiteplanColors::label($unit), ENT_QUOTES, 'UTF-8')], $template);
        if (SiteplanColors::status($unit) === 'ready') return $markup;

        $points = self::outlinePoints($unit->map, $unit->jenis_map);
        if (count($points) < 3) return $markup;
        $angle = 0; $longest = 0;
        foreach ($points as $i => $point) {
            $next = $points[($i + 1) % count($points)];
            $dx = $next[0] - $point[0]; $dy = $next[1] - $point[1];
            $length = hypot($dx, $dy);
            if ($length > $longest) { $longest = $length; $angle = atan2($dy, $dx); }
        }
        // Fit in the unit's own axes, with padding on every side.
        $xs = []; $ys = [];
        foreach ($points as [$x, $y]) {
            $xs[] = $x * cos($angle) + $y * sin($angle);
            $ys[] = -$x * sin($angle) + $y * cos($angle);
        }
        $cx = (min($xs) + max($xs)) / 2; $cy = (min($ys) + max($ys)) / 2;
        $x = $cx * cos($angle) - $cy * sin($angle);
        $y = $cx * sin($angle) + $cy * cos($angle);
        $degrees = rad2deg($angle);
        while ($degrees > 90) $degrees -= 180;
        while ($degrees < -90) $degrees += 180;
        $width = (max($xs) - min($xs)) * 0.72;
        $height = (max($ys) - min($ys)) * 0.42;
        $label = SiteplanColors::label($unit);
        $font = min($height, $width / (strlen($label) * 0.72));
        $text = '<text x="0" y="0" text-anchor="middle" dominant-baseline="central" transform="translate('.$x.' '.$y.') rotate('.$degrees.')" style="font-family:Arial,sans-serif;font-weight:bold;font-size:'.$font.'px;fill:#111827">'.$label.'</text>';
        // The legacy number transform starts at the left edge; replace it for status labels.
        $markup = str_replace('transform="'.$unit->matrik.'"', '', $markup);
        return preg_replace('/<text\b[^>]*>.*?<\/text>/s', $text, $markup, 1);
    }

    private static function outlinePoints(string $map, string $type): array
    {
        if ($type === 'polygon') {
            preg_match_all('/[-+]?(?:\d*\.)?\d+(?:[eE][-+]?\d+)?/', $map, $matches);
            return array_chunk(array_map('floatval', $matches[0]), 2);
        }
        preg_match_all('/([MmLlHhVvCcSsQqTtZz])([^MmLlHhVvCcSsQqTtZz]*)/', $map, $commands, PREG_SET_ORDER);
        $points = []; $x = 0; $y = 0; $start = [0, 0];
        foreach ($commands as $command) {
            $op = strtoupper($command[1]); $relative = ctype_lower($command[1]);
            preg_match_all('/[-+]?(?:\d*\.)?\d+(?:[eE][-+]?\d+)?/', $command[2], $numbers);
            $values = array_map('floatval', $numbers[0]);
            if ($op === 'Z') { [$x, $y] = $start; continue; }
            $size = ['H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4][$op] ?? 2;
            foreach (array_chunk($values, $size) as $v) {
                if (count($v) !== $size) continue;
                if ($op === 'H') $x = $v[0] + ($relative ? $x : 0);
                elseif ($op === 'V') $y = $v[0] + ($relative ? $y : 0);
                else { $x = $v[$size - 2] + ($relative ? $x : 0); $y = $v[$size - 1] + ($relative ? $y : 0); }
                $points[] = [$x, $y];
                if ($op === 'M' && count($points) === 1) $start = [$x, $y];
            }
        }
        return $points;
    }

    public static function normalize(string $svg): string
    {
        // Older master templates can contain an unfinished, duplicate opening tag.
        // Browsers recover from this markup, but SVG converters require valid XML.
        $svg = preg_replace('/<svg\b[^<>]*(?=<svg\b)/i', '', $svg);

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (! $document->loadXML($svg, LIBXML_NONET) || $document->documentElement?->localName !== 'svg') {
                throw new RuntimeException('Data SVG siteplan tidak valid. Periksa template master SVG lokasi ini.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $svg;
    }
}
