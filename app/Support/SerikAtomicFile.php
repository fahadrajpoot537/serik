<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Crash-safe file writes (temp + rename). Prevents truncated JSON on power loss.
 */
final class SerikAtomicFile
{
    /**
     * @param  array<string, mixed>|list<mixed>  $data
     */
    public static function writeJson(string $path, array $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): bool
    {
        $dir = dirname($path);
        File::ensureDirectoryExists($dir);

        $json = json_encode($data, $flags);
        if ($json === false) {
            return false;
        }

        return self::write($path, $json);
    }

    public static function write(string $path, string $contents): bool
    {
        $dir = dirname($path);
        File::ensureDirectoryExists($dir);

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $bak = $path . '.bak';

        try {
            if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
                @unlink($tmp);

                return false;
            }

            // Keep previous good copy for recovery.
            if (is_file($path)) {
                @copy($path, $bak);
            }

            if (! @rename($tmp, $path)) {
                // Windows: rename over existing may fail — fallback replace.
                if (is_file($path)) {
                    @unlink($path);
                }
                if (! @rename($tmp, $path)) {
                    @unlink($tmp);

                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            @unlink($tmp);

            return false;
        }
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    public static function readJson(string $path): ?array
    {
        foreach ([$path, $path . '.bak'] as $candidate) {
            if (! is_readable($candidate)) {
                continue;
            }
            $raw = @file_get_contents($candidate);
            if ($raw === false || $raw === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
