<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'user_exam_id', 'subscription_start_date', 'subscription_end_date'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function userExam()
    {
        return $this->belongsTo(UserExam::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
