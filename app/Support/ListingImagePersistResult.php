<?php

namespace App\Support;

/**
 * Result of a single ListingImagePipeline::persist() run.
 */
final class ListingImagePersistResult
{
    public function __construct(
        public readonly bool $changed,
        public readonly bool $imagesChanged,
        public readonly bool $noRemoteImages,
        public readonly array $paths = [],
    ) {
    }

    public static function unchanged(): self
    {
        return new self(false, false, false);
    }

    public static function noRemoteImages(): self
    {
        return new self(false, false, true);
    }

    /**
     * @param  list<string>  $paths
     */
    public static function persisted(array $paths, bool $imagesChanged): self
    {
        return new self(true, $imagesChanged, false, $paths);
    }
}
