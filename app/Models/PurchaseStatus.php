<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStatus extends Model
{
    use HasFactory;

    const AWAITING = 1;
    const OPEN = 2;
    const OVERDUE = 3;
    const DUEDATE = 4;
    const REJECTED = 5;
    const PAID = 6;

    const TEXT_AWAITING = "Awaiting";
    const TEXT_OPEN = "Open";
    const TEXT_OVERDUE = "Over Due";
    const TEXT_DUEDATE = "Due Date";
    const TEXT_REJECTED = "Rejected";
    const TEXT_PAID = "Paid";

    protected $table = 'purchase_status';

    protected $fillable = [
        'name',
    ];
}
