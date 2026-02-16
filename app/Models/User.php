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
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
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
        'currently_learning',
        'alt_email',
        'alt_email_verified_at',
        'alt_email_otp',
        'alt_email_otp_expires_at',
        'otp_expires_at',
        'orcid_username',
        'notification_preferences'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'provider_id',
        'alt_email_otp',
        'alt_email_otp_expires_at',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'alt_email_verified_at' => 'datetime',
            'alt_email_otp_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'skills' => 'array',
            'notification_preferences' => 'array',
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

    public function isFollowingTag(int $tagId)
    {
        return $this->followedTags()->where('tag_id', $tagId)->exists();
    }

    public function readingLists()
    {
        return $this->hasMany(ReadingList::class);
    }


    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'reports',
            'reporter_id',
            'reported_user_id'
        )->withTimestamps();
    }

    public function blockers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'reports',
            'reported_user_id',
            'reporter_id'
        )->withTimestamps();
    }

    public function viewedPosts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'post_views',
            'user_id',
            'post_id'
        )
            ->withPivot('viewed_at')
            ->withTimestamps()
            ->orderByPivot('viewed_at', 'desc');
    }


    public function isBlockedBy(User $other): bool
    {
        return $this->blockers()->where('users.id', $other->id)->exists();
    }

    public function hasBlocked(User $other): bool
    {
        return $this->blockedUsers()->where('users.id', $other->id)->exists();
    }

    public function isBlockedWith(User $other): bool
    {
        return $this->hasBlocked($other) || $this->isBlockedBy($other);
    }

    public static function getDefaultNotificationPreferences(): array
    {
        return [
            'new_follower' => true,
            'new_comment' => true,
            'new_reaction' => true,
            'new_post_from_following' => true,
            'mention' => true,
        ];
    }

    public function getNotificationPreferences(): array
    {
        return array_merge(
            self::getDefaultNotificationPreferences(),
            $this->notification_preferences ?? []
        );
    }

    public function isNotificationEnabled(string $type): bool
    {
        $preferences = $this->getNotificationPreferences();
        return $preferences[$type] ?? true;
    }

    public function updateNotificationPreference(string $type, bool $enabled): void
    {
        $preferences = $this->getNotificationPreferences();
        $preferences[$type] = $enabled;
        $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Get the user's reaction on a reactable model.
     */
    public function myReaction($reactable): ?\Binafy\LaravelReaction\Models\Reaction
    {
        $userForeignName = config('laravel-reaction.user.foreign_key', 'user_id');

        return $reactable->reactions()
            ->where($userForeignName, $this->getKey())
            ->first();
    }

    /**
     * Update the user's reaction on a reactable model.
     */
    public function updateReaction(string $type, $reactable): ?\Binafy\LaravelReaction\Models\Reaction
    {
        $userForeignName = config('laravel-reaction.user.foreign_key', 'user_id');

        $reaction = $reactable->reactions()
            ->where($userForeignName, $this->getKey())
            ->first();

        if ($reaction) {
            $reaction->update(['type' => $type]);
            return $reaction->fresh();
        }

        return null;
    }

}
