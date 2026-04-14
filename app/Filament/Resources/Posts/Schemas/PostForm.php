<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Author')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('cover_image')
                    ->image(),
                TextInput::make('read_time')
                    ->numeric(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                FileUpload::make('image_url')
                    ->image(),
                TextInput::make('views')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_edit')
                    ->default(false),
            ]);
    }
}
