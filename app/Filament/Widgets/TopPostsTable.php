<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopPostsTable extends BaseWidget
{
    protected static ?string $heading = 'Top posts by views';

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
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('-'),
                TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}

