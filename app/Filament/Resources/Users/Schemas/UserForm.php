<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'moderator' => 'Moderator',
                        'user' => 'User',
                    ])
                    ->required()
                    ->default('user'),
                TextInput::make('avatar_url')
                    ->url(),
                FileUpload::make('cover_image')
                    ->image(),
                TextInput::make('cv_url')
                    ->url(),
                TextInput::make('bio'),
                TextInput::make('work_at'),
                TextInput::make('education'),
                Textarea::make('currently_learning')
                    ->columnSpanFull(),
                TextInput::make('location'),
                TextInput::make('website_url')
                    ->url(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('onesignal_player_id'),
                TextInput::make('alt_email')
                    ->email(),
                DateTimePicker::make('alt_email_verified_at'),
                TextInput::make('alt_email_otp')
                    ->email(),
                DateTimePicker::make('alt_email_otp_expires_at'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                TextInput::make('skills'),
                TextInput::make('provider_id'),
                TextInput::make('github_username'),
                TextInput::make('orcid_username'),
                Select::make('pronouns')
                    ->options(['she/her' => 'She/her', 'he/him' => 'He/him', 'prefer not to say' => 'Prefer not to say']),
                TextInput::make('linkedin_username'),
                DateTimePicker::make('two_factor_expires_at'),
                TextInput::make('notification_preferences'),
                Select::make('status')
                    ->options([
                        'online' => 'Online',
                        'away' => 'Away',
                        'offline' => 'Offline',
                    ])
                    ->required()
                    ->default('offline'),
                DateTimePicker::make('last_seen_at'),
            ]);
    }
}
