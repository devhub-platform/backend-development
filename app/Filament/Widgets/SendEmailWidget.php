<?php

namespace App\Filament\Widgets;

use App\Mail\AdminUserMessageMail;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.send-email-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Recipient user')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return User::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => "{$user->name} ({$user->email})",
                            ])
                            ->all();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        $user = User::query()->find($value);

                        if (! $user) {
                            return null;
                        }

                        return "{$user->name} ({$user->email})";
                    }),
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
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();
        $recipient = User::query()->find($state['user_id'] ?? null);

        if (! $recipient || ! filled($recipient->email)) {
            Notification::make()
                ->title('Recipient email not found.')
                ->danger()
                ->send();

            return;
        }

        try {
            Mail::to($recipient->email)->send(new AdminUserMessageMail(
                user: $recipient,
                mailSubject: $state['subject'],
                message: $state['message'],
                senderName: auth()->user()?->name,
            ));

            Notification::make()
                ->title('Email sent successfully.')
                ->success()
                ->send();

            $this->form->fill([
                'user_id' => $recipient->id,
                'subject' => '',
                'message' => '',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Failed to send email.')
                ->body('Please check your mail configuration and try again.')
                ->danger()
                ->send();
        }
    }
}
