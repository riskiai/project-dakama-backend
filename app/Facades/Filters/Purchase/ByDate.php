<?php

namespace App\Facades\Filters\Purchase;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ByDate
{
    public function handle(Builder $query, Closure $next)
    {
        $raw = request()->input('date');

        if (blank($raw)) {
            return $next($query);
        }

        [$startDate, $endDate] = $this->parse($raw);

        // Hanya satu tanggal  → whereDate
        // Dua tanggal        → whereBetween
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate->startOfDay(), $endDate->endOfDay()]);
        } elseif ($startDate) {
            $query->whereDate('date', $startDate->toDateString());
        }

        return $next($query);
    }

    /**
     * Normalisasi input menjadi [$start, $end] berupa Carbon|null.
     *
     * @param  string|array  $raw
     * @return array{Carbon|null, Carbon|null}
     */
    protected function parse(string|array $raw): array
    {
        if (is_array($raw)) {
            return [
                Carbon::parse($raw[0] ?? null),
                Carbon::parse($raw[1] ?? null),
            ];
        }

        // Bersihkan bracket & spasi, lalu pecah dengan koma
        $clean  = Str::of($raw)->replace(['[', ']'], '')->__toString();
        $parts  = array_filter(array_map('trim', explode(',', $clean)));

        return [
            isset($parts[0]) ? Carbon::parse($parts[0]) : null,
            isset($parts[1]) ? Carbon::parse($parts[1]) : null,
        ];
    }
}
