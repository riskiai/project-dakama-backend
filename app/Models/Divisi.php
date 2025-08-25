<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Divisi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'divisis';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'kode_divisi',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate kode_divisi saat create
        static::creating(function (Divisi $model) {
            // generate candidate pertama
            $model->kode_divisi = $model->generateKodeDivisi();

            // Safety net: kalau unik masih bentrok (race), auto naikkan nomor
            $tries = 0;
            while (self::withTrashed()->where('kode_divisi', $model->kode_divisi)->exists()) {
                $model->kode_divisi = $model->generateKodeDivisi(true); // mode bump
                if (++$tries > 5) break; // hindari loop tak berujung
            }
        });

        // static::restoring(function (Divisi $divisi) {
        //     $divisi->deleted_by = null;
        // });
    }

    /**
     * Generate kode_divisi unik berbasis prefix (3 huruf dari name).
     * Jika $bumpNext = true, paksa ambil max+1 lagi (untuk mengatasi race).
     */
    public function generateKodeDivisi(bool $bumpNext = false): string
    {
        // Prefix: 3 huruf pertama dari name (tanpa spasi). Pad kalau < 3.
        $prefix = strtoupper(substr(str_replace(' ', '', (string) $this->name), 0, 3));
        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        // Ambil semua kode dengan prefix sama (ikut soft-deleted)
        $existing = self::withTrashed()
            ->where('kode_divisi', 'like', $prefix.'-%')
            ->pluck('kode_divisi');

        // Cari max nomor untuk prefix tsb
        $max = 0;
        foreach ($existing as $kd) {
            // format diharapkan PREFIX-###, ambil angka bagian akhir
            $parts = explode('-', $kd);
            $num = (int) (end($parts) ?? 0);
            if ($num > $max) $max = $num;
        }

        // Jika bumpNext diminta (mis. collision), naikkan lagi
        $next = $bumpNext ? $max + 2 : $max + 1;

        return sprintf('%s-%03d', $prefix, $next);
    }
}
