<?php

namespace App\Supports;

class WagesMaintenance
{
    protected static function flagPath(): string
    {
        return storage_path('app/wages-maintenance.flag');
    }

    public static function isActive(): bool
    {
        return is_file(static::flagPath());
    }

    public static function enable(): void
    {
        file_put_contents(static::flagPath(), (string) now());
    }

    public static function disable(): void
    {
        if (is_file(static::flagPath())) {
            unlink(static::flagPath());
        }
    }

    public static function htmlResponse(): \Illuminate\Http\Response
    {
        return response(
            '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please pay my dues</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f8fafc; }
        h1 { color: #161e2d; font-size: 2.5rem; text-align: center; padding: 0 1rem; }
    </style>
</head>
<body>
    <h1>Please pay my dues</h1>
</body>
</html>',
            503,
            ['Content-Type' => 'text/html; charset=UTF-8', 'Retry-After' => '3600']
        );
    }
}
