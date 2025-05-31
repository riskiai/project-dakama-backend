<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'doc_no'; // Set doc_no as the primary key
    public $incrementing = false; // Indicate that the primary key is not auto-incrementing
    protected $keyType = 'string'; // Indicate that the primary key is of string type

    const ATTACHMENT_FILE = 'attachment/purchase';

    const TAB_SUBMIT = 1;
    const TAB_VERIFIED = 2;
    const TAB_PAYMENT_REQUEST = 3;
    const TAB_PAID = 4;

    const TEXT_EVENT = "Event Purchase";
    const TEXT_OPERATIONAL = "Operational Purchase";

    const TYPE_EVENT = 1;
    const TYPE_OPERATIONAL = 2;

    protected $fillable = [
        'doc_no',
        'doc_type',
        'tab',
        'purchase_id',
        'purchase_category_id',
        'company_id',
        'project_id',
        'purchase_status_id',
        'description',
        'remarks',
        'sub_total_purchase',
        'ppn',
        'pph',
        'date',
        'due_date',
        'reject_note',
        'tanggal_pembayaran_purchase',
        'user_id',
    ];

     protected $dates = ['created_at', 'updated_at'];

     /*  public function getTotalAttribute()
    {
        $total = $this->attributes['sub_total'];

        if ($this->attributes['ppn']) {
            $ppn = ($this->attributes['sub_total'] * $this->attributes['ppn']) / 100;
            $total += $ppn;
        }

       
        if ($this->attributes['pph']) {

            $pph = ($this->attributes['sub_total'] * $this->taxPph->percent) / 100;
            $total -= $pph;
        }
        
        return round($total);
    } */

     public function purchaseCategory(): HasOne
    {
        return $this->hasOne(PurchaseCategory::class, 'id', 'purchase_category_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    public function purchaseStatus(): HasOne
    {
        return $this->hasOne(PurchaseStatus::class, 'id', 'purchase_status_id');
    }

    public function taxPpn(): HasOne
    {
        return $this->hasOne(Tax::class, 'id', 'ppn');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'doc_no', 'doc_no');
    }

    public function document()
    {
        return $this->morphOne(Document::class, 'documentable');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogPurchase::class, 'doc_no', 'doc_no');
    }

     public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function productCompanies(): HasMany
    {
        return $this->hasMany(PurchaseProductCompanies::class, 'doc_no', 'doc_no');
    }



}