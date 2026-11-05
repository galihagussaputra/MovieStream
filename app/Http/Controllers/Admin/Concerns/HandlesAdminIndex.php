<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Request;

trait HandlesAdminIndex
{
    protected function perPage(int $default = 5, int $max = 100): int
    {
        $perPage = Request::integer('perPage', $default);

        return $perPage > 0 ? min($perPage, $max) : $default;
    }

    protected function sortColumn(array $allowedColumns): ?string
    {
        $column = Request::input('column');

        return is_string($column) && in_array($column, $allowedColumns, true)
            ? $column
            : null;
    }

    protected function sortDirection(string $default = 'asc'): string
    {
        $direction = Request::input('direction');

        return in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : $default;
    }
}
