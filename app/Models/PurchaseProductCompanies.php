<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseProductCompanies extends Model
{
    use HasFactory;

    protected $table = 'purchase_products';

    protected $fillable = [
        'doc_no',
        'product_name',
        'harga',
        'stok',
        'subtotal_harga_product',
    ];

    /**
     * Relasi ke model Purchase (Many-to-One)
     * Banyak product dimiliki oleh satu purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'doc_no', 'doc_no');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
