<?php

namespace App\Facades\Filters\Purchase;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ByPembayaran
{
    public function handle(Builder $query, Closure $next)
    {
        /** @var string|array|null $raw */
        $raw = request()->input('tanggal_pembayaran_purchase');

        // Jika tidak ada parameter, teruskan pipeline
        if (blank($raw)) {
            return $next($query);
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisasi input → $startDate & $endDate
        |--------------------------------------------------------------------------
        */
        if (is_array($raw) && count($raw) >= 2) {
            // Format: ?tanggal_pembayaran_purchase[]=2024-07-01&tanggal_pembayaran_purchase[]=2024-07-31
            [$start, $end] = $raw;
        } else {
            // Format string: "[2024-07-01, 2024-07-31]" atau "2024-07-01,2024-07-31"
            $clean  = Str::of($raw)->replace(['[', ']'], '')->__toString();
            $parts  = array_map('trim', explode(',', $clean));
            [$start, $end] = $parts + [null, null];
        }

        // Validasi sederhana—hanya terapkan filter jika kedua tanggal ada
        if ($start && $end) {
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate   = Carbon::parse($end)->endOfDay();

            $query->whereBetween('tanggal_pembayaran_purchase', [$startDate, $endDate]);
        }

        return $next($query);
    }
}
