<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopPostsTable extends BaseWidget
{
    protected static ?string $heading = 'Top posts by views';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->with('user:id,name')
                    ->select(['id', 'user_id', 'title', 'slug', 'views', 'status', 'created_at'])
                    ->orderByDesc('views')
                    ->limit(10)
            )
            ->striped()
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Post')
                    ->searchable()
                    ->limit(58)
                    ->tooltip(fn (Post $record): string => $record->title)
                    ->description(fn (Post $record): ?string => $record->slug ? "@{$record->slug}" : null),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->icon('heroicon-o-eye')
                    ->color('primary'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Published')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ]);
    }
}
