<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Number;

class TopPostsTable extends BaseWidget
{
    protected static ?string $heading = 'Top Posts by Views';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->with('user:id,name,avatar_url')
                    ->select(['id', 'user_id', 'title', 'slug', 'views', 'status', 'created_at', 'cover_image'])
                    ->where('status', '!=', 'draft')
                    ->orderByDesc('views')
                    ->limit(15)
            )
            ->striped()
            ->paginated(false)
            ->defaultSort('views', 'desc')
            ->columns([
                // Rank column
                TextColumn::make('rank')
                    ->label('Rank')
                    ->state(function ($record) {
                        $allPosts = Post::query()
                            ->select(['id', 'views'])
                            ->where('status', '!=', 'draft')
                            ->orderByDesc('views')
                            ->pluck('id')
                            ->toArray();
                        return array_search($record->id, $allPosts) + 1;
                    })
                    ->badge()
                    ->color('success')
                    ->alignment('center'),

                // Title column with cover image thumbnail
                TextColumn::make('title')
                    ->label('Post Title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (Post $record): string => $record->title)
                    ->description(fn (Post $record): ?string => $record->slug ? "Slug: {$record->slug}" : null)
                    ->weight('medium'),

                // Author column
                TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('Anonymous')
                    ->toggleable()
                    ->icon('heroicon-o-user-circle'),

                // Views column with visual representation
                TextColumn::make('views')
                    ->label('Views')
                    ->formatStateUsing(fn ($state): string => Number::abbreviate((int)$state))
                    ->badge()
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->alignment('center')
                    ->sortable()
                    ->copyable(),

                // Status column
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'published' => 'heroicon-o-check-circle',
                        'draft' => 'heroicon-o-pencil',
                        'archived' => 'heroicon-o-archive-box',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->alignment('center')
                    ->toggleable(),

                // Published date column
                TextColumn::make('created_at')
                    ->label('Published')
                    ->since()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar'),

                // Engagement score (views per day)
                TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->state(function ($record) {
                        $daysSinceCreation = max(1, $record->created_at->diffInDays(now()));
                        $viewsPerDay = (int)($record->views / $daysSinceCreation);
                        return $viewsPerDay;
                    })
                    ->formatStateUsing(fn ($state): string => "{$state} views/day")
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 100 => 'danger',
                        $state >= 50 => 'warning',
                        $state >= 20 => 'info',
                        default => 'gray',
                    })
                    ->alignment('center')
                    ->sortable(),
            ]);
    }
}
