<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /** jika ingin tetap diperlakukan numerik di kode */
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
     */
    protected function calcSubtotal(): float
    {
        // harga * stok
        $base = bcmul($this->harga ?? 0, $this->stok ?? 0, 2);

        // konversi ppn varchar → float rate (0–1)
        $rate = $this->ppnRate();

        // PPN amount = base * rate
        $ppnAmount = bcmul($base, $rate, 2);

        // subtotal = base + ppn
        return bcadd($base, $ppnAmount, 2);
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
        $rate = floatval($clean);

        // jika >1, artinya masih persen (11 ⇒ 0.11)
        return $rate > 1 ? $rate / 100 : $rate;
    }

    /* protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->subtotal_harga_product = bcmul($m->harga, $m->stok, 2);
        });

        static::updating(function ($m) {
            $m->subtotal_harga_product = bcmul($m->harga, $m->stok, 2);
        });
    } */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'doc_no', 'doc_no');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
