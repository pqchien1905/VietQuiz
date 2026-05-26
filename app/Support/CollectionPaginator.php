<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class CollectionPaginator
{
    public static function make(Collection $items, Request $request, int $perPage = 12, string $pageName = 'page'): LengthAwarePaginator
    {
        $requestedPerPage = (int) $request->query('per_page', $perPage);
        if (in_array($requestedPerPage, [10, 20, 50, 100], true)) {
            $perPage = $requestedPerPage;
        }

        $page = Paginator::resolveCurrentPage($pageName);

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ]
        );
    }
}
