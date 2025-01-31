<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'gender',
        'phone_number',
        'avatar_dir',
        'city_id',
        'specialty_id',
        'active',
        'password',
    ];

    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'avatar',
    ];

    public function getAvatarAttribute()
    {
        // If a profile photo path is available, return it
        if ($this->avatar_dir) {
            return asset(Storage::url($this->avatar_dir));
        }
        // Otherwise, return the default avatar generated by Jetstream
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Scope for inactive users
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function metas()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function meta($key, $value = null)
    {
        if ($value === null) {
            // Retrieve the meta value
            $meta = $this->metas()->where('meta_key', $key)->first();
            return $meta ? $meta->meta_value : null;
        }

        // Update or create the meta value
        return $this->metas()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function deleteMeta($key)
    {
        return $this->metas()->where('meta_key', $key)->delete();
    }

    public function exams()
    {
        return $this->hasMany(UserExam::class);
    }
}
