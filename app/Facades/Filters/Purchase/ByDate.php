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

        /*─────────────────────────────────────────────
        | Jika endDate masih null DAN ada param due_date,
        | anggap due_date (hanya elemen pertama) sebagai
        | batas akhir range.
        ─────────────────────────────────────────────*/
        if (!$endDate && request()->filled('due_date')) {
            $endDate = $this->firstDate(request()->input('due_date'));
        }

        // Range  /  Single  (seperti sebelumnya)
        if ($startDate && $endDate) {
            $query->whereBetween('date', [
                $startDate->startOfDay(),
                $endDate->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->whereDate('date', $startDate->toDateString());
        }

        return $next($query);
    }

    /** @return array{Carbon|null, Carbon|null} */
    protected function parse(string|array $raw): array
    {
        if (is_array($raw)) {
            return [
                Carbon::parse($raw[0] ?? null),
                Carbon::parse($raw[1] ?? null),
            ];
        }

        $clean  = Str::of($raw)->replace(['[', ']'], '')->__toString();
        $parts  = array_filter(array_map('trim', explode(',', $clean)));

        return [
            isset($parts[0]) ? Carbon::parse($parts[0]) : null,
            isset($parts[1]) ? Carbon::parse($parts[1]) : null,
        ];
    }

    /** Ambil tanggal pertama (string|array) lalu ubah ke Carbon|null */
    protected function firstDate(string|array $value): ?Carbon
    {
        if (is_array($value)) {
            return isset($value[0]) ? Carbon::parse($value[0]) : null;
        }

        $clean = Str::of($value)->replace(['[', ']'], '')->__toString();
        $first = trim(Str::of($clean)->before(','));   // ambil sebelum koma bila ada

        return $first !== '' ? Carbon::parse($first) : null;
    }
}
