<?php

namespace App\Services;

use DOMDocument;
use RuntimeException;

class SiteplanSvg
{
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
