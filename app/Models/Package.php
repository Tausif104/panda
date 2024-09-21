<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sub_title', 'description', 'duration', 'price'];

    public function orderPackages()
    {
        return $this->hasMany(OrderPackage::class);
    }
}
