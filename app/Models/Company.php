<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes; 

    /**
     * Variable global yang bisa digunakan untuk menyimpan path attachment.
     * Berfungsi untuk mengurangi duplikasi path.
     */
    const ATTACHMENT_NPWP = 'attachment/contact/npwp';
    const ATTACHMENT_FILE = 'attachment/contact/file';
    protected $dates = ['deleted_at'];
    /**
     * Kolom yang dapat diisi secara massal
     */
    protected $fillable = [
        'contact_type_id',
        'name',
        'address',
        'npwp',
        'pic_name',
        'phone',
        'email',
        'file',
        'bank_name',
        'branch',
        'account_name',
        'currency',
        'account_number',
        'swift_code',
    ];

    public function contactType()
    {
        return $this->belongsTo(ContactType::class, 'contact_type_id');
    }
}
