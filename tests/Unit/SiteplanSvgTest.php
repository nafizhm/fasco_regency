<?php

namespace Tests\Unit;

use App\Services\SiteplanSvg;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SiteplanSvgTest extends TestCase
{
    public function test_it_repairs_the_duplicate_svg_opening_tag_in_legacy_templates(): void
    {
        $valid = '<svg id="svg-image-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40000 35000"><path fill="#94a3b8" d="M0 0h10v10z"/></svg>';

        $this->assertSame($valid, SiteplanSvg::normalize('<svg id="svg-image-1" '.$valid));
    }

    public function test_it_preserves_valid_svg_including_nested_svg_and_sales_colors(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><svg x="10"><polygon fill="#ffffff" points="0,0 10,0 10,10"/></svg></svg>';

        $this->assertSame($svg, SiteplanSvg::normalize($svg));
    }

    public function test_it_rejects_other_invalid_xml_before_uploading_to_the_converter(): void
    {
        $this->expectException(RuntimeException::class);

        SiteplanSvg::normalize('<svg xmlns="http://www.w3.org/2000/svg"><path></svg>');
    }
}
