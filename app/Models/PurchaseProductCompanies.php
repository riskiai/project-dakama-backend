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
    ];

    /** jika ingin tetap diperlakukan numerik di kode */
    protected $casts = [
        'harga'                  => 'float',
        'stok'                   => 'integer',
        'subtotal_harga_product' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->subtotal_harga_product = bcmul($m->harga, $m->stok, 2);
        });

        static::updating(function ($m) {
            $m->subtotal_harga_product = bcmul($m->harga, $m->stok, 2);
        });
    }

    /* ───────── relations ───────── */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'doc_no', 'doc_no');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
