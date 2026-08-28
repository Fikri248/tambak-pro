<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PageSize
{
    public const DEFAULT = 25;

    /** @var array<int, int> */
    public const OPTIONS = [25, 50, 100, 500];

    public static function resolve(Request $request): int
    {
        $requested = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);
        $perPage = in_array($requested, self::OPTIONS, true) ? $requested : self::DEFAULT;

        if ($request->query->has('per_page') && $requested !== $perPage) {
            $request->query->set('per_page', (string) $perPage);
        }

        return $perPage;
    }
}
