<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'fa'], true), 404);

        $request->session()->put('locale', $locale);

        $target = $request->input('return_to', '/');
        if (! is_string($target)
            || ! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || parse_url($target, PHP_URL_HOST) !== null
            || preg_match('/[\r\n]/', $target) === 1) {
            $target = '/';
        }

        return redirect($target);
    }
}
