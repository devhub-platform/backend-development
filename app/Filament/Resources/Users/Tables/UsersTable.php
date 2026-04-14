<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->username),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'moderator' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'success',
                        'away' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'moderator' => 'Moderator',
                        'user' => 'User',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'away' => 'Away',
                        'offline' => 'Offline',
                    ]),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
