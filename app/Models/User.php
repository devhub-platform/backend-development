<?php

namespace App\Models;

use App\Observers\UserObserver;
use Binafy\LaravelReaction\Traits\Reactor;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, softDeletes, Reactor , Searchable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function toSearchableArray()
    {
        return [
            'username' => $this->username,
            'name' => $this->name,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'role',
        'avatar_url',
        'bio',
        'email',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'provider_id',
        'otp'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

//    public function followers(): HasMany
//    {
//        return $this->hasMany(UserRelationship::class , 'followee_id');
//    }
//
//    public function followees(): HasMany
//    {
//        return $this->hasMany(UserRelationship::class , 'follower_id');
//    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Users who are following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function savedPosts()
    {
        return $this->belongsToMany(Post::class, 'saved_posts')
            ->withTimestamps()
            ->using(\Illuminate\Database\Eloquent\Relations\Pivot::class);
    }
}
