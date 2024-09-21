<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    use HasFactory;

    protected $table = 'usermeta';

    protected $fillable = ['user_id', 'meta_key', 'meta_value'];

    /**
     * Get the user that owns the meta.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
