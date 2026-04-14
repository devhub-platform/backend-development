<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('username'),
                TextEntry::make('role'),
                TextEntry::make('avatar_url')
                    ->placeholder('-'),
                ImageEntry::make('cover_image')
                    ->placeholder('-'),
                TextEntry::make('cv_url')
                    ->placeholder('-'),
                TextEntry::make('bio')
                    ->placeholder('-'),
                TextEntry::make('work_at')
                    ->placeholder('-'),
                TextEntry::make('education')
                    ->placeholder('-'),
                TextEntry::make('currently_learning')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('website_url')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('onesignal_player_id')
                    ->placeholder('-'),
                TextEntry::make('alt_email')
                    ->placeholder('-'),
                TextEntry::make('alt_email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('alt_email_otp')
                    ->placeholder('-'),
                TextEntry::make('alt_email_otp_expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('otp')
                    ->placeholder('-'),
                TextEntry::make('otp_expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('provider_id')
                    ->placeholder('-'),
                TextEntry::make('github_username')
                    ->placeholder('-'),
                TextEntry::make('orcid_username')
                    ->placeholder('-'),
                TextEntry::make('pronouns')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('linkedin_username')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (User $record): bool => $record->trashed()),
                TextEntry::make('two_factor_expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('last_seen_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
