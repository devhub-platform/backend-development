<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReadingList whereUserId($value)
 * @mixin \Eloquent
 */
class ReadingList extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->belongsToMany(
            Post::class,
            'reading_list_story',
            'reading_list_id',
            'post_id'
        )->withTimestamps();
    }

}
