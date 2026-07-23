<?php

namespace Tests\Unit;

use App\Support\TrebMediaFilter;
use PHPUnit\Framework\TestCase;

class TrebMediaFilterTest extends TestCase
{
    public function test_skips_pdf_amp_media(): void
    {
        $this->assertFalse(TrebMediaFilter::isPhotoAmpMedia([
            'MediaURL' => 'https://trreb-image.ampre.ca/example/brochure.pdf',
            'MediaCategory' => 'Document',
            'ImageSizeDescription' => '',
        ]));
    }

    public function test_accepts_photo_amp_media(): void
    {
        $this->assertTrue(TrebMediaFilter::isPhotoAmpMedia([
            'MediaURL' => 'https://trreb-image.ampre.ca/rs:fit:1920:1920/L3RycmViL2xpc3RpbmdzL3Bob3RvLmpwZw.jpg',
            'MediaCategory' => 'Photo',
            'ImageSizeDescription' => 'LargestNoWatermark',
        ]));
    }

    public function test_skips_video_url(): void
    {
        $this->assertFalse(TrebMediaFilter::isPhotoMediaUrl('https://cdn.example.com/listing-tour.mp4'));
    }

    public function test_filters_mixed_url_list(): void
    {
        $urls = [
            'https://trreb-image.ampre.ca/photo1.jpg',
            'https://trreb-image.ampre.ca/floorplan.pdf',
            'https://trreb-image.ampre.ca/walkthrough.mp4',
            'https://trreb-image.ampre.ca/photo2.jpg',
        ];

        $this->assertSame([
            'https://trreb-image.ampre.ca/photo1.jpg',
            'https://trreb-image.ampre.ca/photo2.jpg',
        ], TrebMediaFilter::filterPhotoUrls($urls));
    }
}
