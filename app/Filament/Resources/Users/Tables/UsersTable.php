<?php

namespace App\Filament\Resources\Users\Tables;

use App\Mail\AdminUserMessageMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
                    ->description(fn($record): ?string => $record->username),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'moderator' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
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
                Action::make('sendEmail')
                    ->label('Send Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn(User $record): bool => filled($record->email))
                    ->modalHeading(fn(User $record): string => "Send email to {$record->name}")
                    ->modalDescription(fn(User $record): string => "This will send an email to {$record->email}.")
                    ->form([
                        TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->maxLength(150),
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(8)
                            ->maxLength(5000),
                    ])
                    ->action(function (User $record, array $data): void {
                        try {
                            Mail::to($record->email)->send(new AdminUserMessageMail(
                                user: $record,
                                mailSubject: $data['subject'],
                                message: $data['message'],
                                senderName: auth()->user()?->name,
                            ));

                            Notification::make()
                                ->title('Email sent successfully.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Failed to send email.')
                                ->body('Please check your mail configuration and try again.')
                                ->danger()
                                ->send();
                        }
                    }),
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
