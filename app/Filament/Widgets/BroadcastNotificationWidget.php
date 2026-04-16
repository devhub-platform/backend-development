<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Throwable;

class BroadcastNotificationWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.broadcast-notification-widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('notification_type')
                    ->label('Notification Type')
                    ->required()
                    ->options([
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'danger' => 'Danger',
                        'info' => 'Info',
                    ])
                    ->default('info')
                    ->columnSpan(1),

                TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->placeholder('Enter notification title')
                    ->columnSpan(1)
                    ->maxLength(100),

                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->placeholder('Write your broadcast message')
                    ->rows(6)
                    ->columnSpanFull()
                    ->maxLength(1000),

                Select::make('recipients')
                    ->label('Send To')
                    ->required()
                    ->options([
                        'all' => 'All Users',
                        'admins' => 'Admins Only',
                        'moderators' => 'Moderators Only',
                        'users' => 'Regular Users Only',
                    ])
                    ->default('all')
                    ->columnSpan(1),

                TextInput::make('action_url')
                    ->label('Action URL (Optional)')
                    ->placeholder('e.g., /dashboard')
                    ->url()
                    ->columnSpan(1),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();

        try {
            // Get users based on recipient type
            $users = match($state['recipients']) {
                'admins' => User::where('role', 'admin')->get(),
                'moderators' => User::where('role', 'moderator')->get(),
                'users' => User::where('role', 'user')->get(),
                'all' => User::all(),
                default => User::all(),
            };

            if ($users->isEmpty()) {
                Notification::make()
                    ->title('No recipients found.')
                    ->warning()
                    ->send();
                return;
            }

            $count = 0;

            // Send notification to each user
            foreach ($users as $user) {
                try {
                    Notification::make()
                        ->title($state['title'])
                        ->body($state['message'])
                        ->color($state['notification_type'])
                        ->when(!empty($state['action_url']), fn($notification) => $notification->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View')
                                ->url($state['action_url'], shouldOpenInNewTab: true),
                        ]))
                        ->sendToDatabase($user);

                    $count++;
                } catch (Throwable $e) {
                    report($e);
                }
            }

            Notification::make()
                ->title('Broadcast sent successfully!')
                ->body("Notification sent to {$count} user(s).")
                ->success()
                ->send();

            // Reset form
            $this->form->fill([
                'notification_type' => 'info',
                'title' => '',
                'message' => '',
                'recipients' => 'all',
                'action_url' => '',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Failed to send broadcast.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}

