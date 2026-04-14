<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reporter_id')
                    ->relationship('reporter', 'name')
                    ->required(),
                Select::make('reported_user_id')
                    ->relationship('reportedUser', 'name')
                    ->required(),
                Select::make('reported_post_id')
                    ->relationship('reportedPost', 'title'),
                TextInput::make('type')
                    ->required()
                    ->default('user'),
                Textarea::make('message')
                    ->columnSpanFull(),
                TextInput::make('reason'),
                Toggle::make('report')
                    ->required(),
            ]);
    }
}
