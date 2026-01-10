<?php

namespace App\Models;

use App\Observers\UserObserver;
use Binafy\LaravelReaction\Traits\Reactor;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([UserObserver::class])]
/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $role
 * @property string|null $avatar_url
 * @property string|null $bio
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $provider_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $otp
 * @property string|null $two_factor_expires_at
 * @property string|null $cover_image
 * @property string|null $skills
 * @property string|null $location
 * @property string|null $website_url
 * @property string|null $pronouns
 * @property string|null $linkedin_username
 * @property string|null $github_username
 * @property string|null $currently_learning
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $followedTags
 * @property-read int|null $followed_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReadingList> $readingLists
 * @property-read int|null $reading_lists_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $savedPosts
 * @property-read int|null $saved_posts_count
 * @property-read \App\Models\UserStatus|null $status
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCoverImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentlyLearning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGithubUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLinkedinUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePronouns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereWebsiteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, softDeletes, Reactor, Searchable;

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
        'otp',
        'two_factor_expires_at',
        'cover_image',
        'education',
        'work_at',
        'skills',
        'deleted_at',
        'location',
        'website_url',
        'pronouns',
        'linkedin_username',
        'github_username',
        'currently_learning'
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

    public function followedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_user', 'user_id', 'tag_id')
            ->withTimestamps();
    }

    public function savedPosts()
    {
        return $this->belongsToMany(Post::class, 'saved_posts')
            ->withTimestamps()
            ->using(\Illuminate\Database\Eloquent\Relations\Pivot::class);
    }

    public function unfollowTag(int $tagId)
    {
        $this->followedTags()->detach($tagId);
        return true;
    }

    public function followTag(int $tagId)
    {
        if ($this->followedTags()->where('tag_id', $tagId)->exists()) {
            return true;
        }
        $this->followedTags()->attach($tagId);
        return true;
    }

    public function status(): HasOne
    {
        return $this->hasOne(UserStatus::class);
    }

// check if following
    public function isFollowingTag(int $tagId)
    {
        return $this->followedTags()->where('tag_id', $tagId)->exists();
    }

    public function readingLists()
    {
        return $this->hasMany(ReadingList::class);
    }
}
