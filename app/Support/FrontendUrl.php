<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class FrontendUrl
{
    public static function to(string $path = ''): string
    {
        $base = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        return $path === '' ? $base : $base.'/'.ltrim($path, '/');
    }

    public static function redirect(string $path = ''): RedirectResponse
    {
        return redirect()->away(self::to($path));
    }
}
