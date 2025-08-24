<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    const BUKTI_PEMBELIAN  = 1;
    const BUKTI_PEMBAYARAN = 2;

    protected $fillable = [
        'doc_no',
        'file_name',
        'file_path',
        'type_file',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'doc_no', 'doc_no');
    }
}
