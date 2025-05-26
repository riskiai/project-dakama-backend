<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ByUpdated
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('updated_at')) {
            return $next($query);
        }

        $dates = explode(", ", str_replace(['[', ']'], '', request('updated_at')));
        
        // Gunakan Carbon untuk memanipulasi tanggal
        $startDate = Carbon::parse($dates[0])->startOfDay(); // Mulai dari awal hari
        $endDate = Carbon::parse($dates[1])->endOfDay();     // Hingga akhir hari (23:59:59)

        // Filter data berdasarkan updated_at antara startDate dan endDate
        $query->whereBetween('updated_at', [$startDate, $endDate]);

        return $next($query);
    }
}
