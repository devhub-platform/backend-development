<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('user.email')
                    ->label('User Email'),
                TextEntry::make('title')
                    ->label('Title')
                    ->columnSpanFull(),
                TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bug' => 'danger',
                        'feature_request' => 'success',
                        'improvement' => 'warning',
                        'other' => 'info',
                    }),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'secondary',
                        'reviewed' => 'info',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'danger',
                    }),
                TextEntry::make('message')
                    ->label('Message')
                    ->columnSpanFull(),
                TextEntry::make('rating')
                    ->label('Rating')
                    ->state(fn ($record) => $record->rating ? '⭐ ' . $record->rating . '/5' : 'Not rated'),
                TextEntry::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y H:i:s'),
                TextEntry::make('respondedBy.name')
                    ->label('Responded By')
                    ->placeholder('Not yet responded'),
                TextEntry::make('responded_at')
                    ->label('Response Date')
                    ->dateTime('M d, Y H:i:s')
                    ->placeholder('N/A'),
                TextEntry::make('admin_response')
                    ->label('Admin Response')
                    ->columnSpanFull()
                    ->placeholder('No response yet'),
            ]);
    }
}
