<?php

namespace App\Support;

final class ImageDimensions
{
    /** @var array<string, array{0:int,1:int}> */
    private const SIZES = [
        'thumb' => [120, 120],
        'small' => [300, 200],
        'medium' => [400, 300],
        'medium-rectangle' => [400, 300],
        'large' => [800, 600],
    ];

    /**
     * @return array<string, int|string>
     */
    public static function htmlAttributes(?string $size = null, bool $lazy = true): array
    {
        $attrs = [
            'decoding' => 'async',
            'loading' => $lazy ? 'lazy' : 'eager',
        ];

        if ($size && isset(self::SIZES[$size])) {
            [$width, $height] = self::SIZES[$size];
            $attrs['width'] = $width;
            $attrs['height'] = $height;
        }

        return $attrs;
    }
}
