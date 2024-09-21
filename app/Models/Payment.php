<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_package_id', 'payment_method', 'transaction_id', 'amount', 'reference', 'currency', 'description', 'user_id', 'status'
    ];

    public function orderPackage()
    {
        return $this->belongsTo(OrderPackage::class);
    }
}
