<?php

namespace App\Facades\Filters\Purchase;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ByDueDate
{
    public function handle(Builder $query, Closure $next)
    {
        $raw = request()->input('due_date');

        if (blank($raw)) {
            return $next($query);
        }

        [$startDate, $endDate] = $this->parse($raw);

        if ($startDate && $endDate) {
            $query->whereBetween('due_date', [$startDate->startOfDay(), $endDate->endOfDay()]);
        } elseif ($startDate) {
            $query->whereDate('due_date', $startDate->toDateString());
        }

        return $next($query);
    }

    protected function parse(string|array $raw): array
    {
        if (is_array($raw)) {
            return [
                Carbon::parse($raw[0] ?? null),
                Carbon::parse($raw[1] ?? null),
            ];
        }

        $clean = Str::of($raw)->replace(['[', ']'], '')->__toString();
        $parts = array_filter(array_map('trim', explode(',', $clean)));

        return [
            isset($parts[0]) ? Carbon::parse($parts[0]) : null,
            isset($parts[1]) ? Carbon::parse($parts[1]) : null,
        ];
    }
}
