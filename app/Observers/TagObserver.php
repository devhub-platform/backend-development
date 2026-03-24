<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Str;

class TagObserver
{
    public function creating(Tag $tag): void
    {
        $tag->name = Str::lower(Str::trim($tag->name));
    }

    public function updating(Tag $tag): void
    {
        $tag->name = Str::lower(Str::trim($tag->name));
    }
}
