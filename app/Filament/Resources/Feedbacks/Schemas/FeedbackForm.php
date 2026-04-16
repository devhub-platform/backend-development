<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'bug' => 'Bug Report',
                        'feature_request' => 'Feature Request',
                        'improvement' => 'Improvement',
                        'other' => 'Other',
                    ])
                    ->default('other')
                    ->required(),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Select::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '1 - Poor',
                        2 => '2 - Fair',
                        3 => '3 - Good',
                        4 => '4 - Very Good',
                        5 => '5 - Excellent',
                    ])
                    ->nullable(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'reviewed' => 'Reviewed',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->default('new')
                    ->required(),
                Textarea::make('admin_response')
                    ->label('Admin Response')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }
}
