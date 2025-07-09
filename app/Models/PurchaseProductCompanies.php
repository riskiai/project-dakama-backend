<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseProductCompanies extends Model
{
    use HasFactory;

    /** ⇢ sesuaikan ke nama tabel di DB */
    protected $table = 'purchase_products_companies';

    protected $fillable = [
        'doc_no',
        'company_id',
        'product_name',
        'harga',
        'stok',
        'subtotal_harga_product',
        'ppn',
    ];

    /**
     * Gunakan float cast karena nilai uang sudah dibulatkan ke 2 desimal.
     * Jika Anda ingin presisi absolut, pertimbangkan DECIMAL di DB
     *   + menyimpan sebagai string & Money library.
     */
    protected $casts = [
        'harga'                  => 'float',
        'stok'                   => 'integer',
        'subtotal_harga_product' => 'float',
        'ppn'                    => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->subtotal_harga_product = $m->calcSubtotal());
        static::updating(fn ($m) => $m->subtotal_harga_product = $m->calcSubtotal());
    }

    /* ──────────────── HELPERS ──────────────── */

    /**
     * Hitung subtotal = (harga × stok) + PPN.
     * Menggunakan aritmatika float + round(2) agar tidak perlu ekstensi BCMath.
     */
    protected function calcSubtotal(): float
    {
        $price  = (float) ($this->harga ?? 0);
        $qty    = (int)   ($this->stok  ?? 0);

        // harga * stok dengan pembulatan 2 desimal
        $base = round($price * $qty, 2);

        // konversi ppn varchar → float rate (0–1)
        $rate = $this->ppnRate();

        // PPN amount = base * rate
        $ppnAmount = round($base * $rate, 2);

        return round($base + $ppnAmount, 2);
    }

    /**
     * Parse field ppn (string) menjadi angka desimal tarif.
     *   "11%" atau "11"  → 0.11
     *   "0.11"           → 0.11
     *   null / ""        → 0
     */
    protected function ppnRate(): float
    {
        if (!$this->ppn) {
            return 0.0;
        }

        // hapus spasi & simbol %
        $clean = str_replace('%', '', trim($this->ppn));

        // konversi ke float
        $rate = (float) $clean;

        // jika >1, artinya masih persen (11 ⇒ 0.11)
        return $rate > 1 ? $rate / 100 : $rate;
    }

    /* ──────────────── RELATIONS ──────────────── */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'doc_no', 'doc_no');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
