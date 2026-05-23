<?php

namespace App\Filament\Widgets;

use App\Mail\AdminUserMessageMail;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Mail;
use App\Services\HackClubCdnService;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Http as HttpClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmailWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.send-email-widget';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
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
                Select::make('user_id')
                    ->label('Recipient user')
                    ->required()
                    ->searchable()
                    ->placeholder('Search by name or email')
                    ->columnSpan(1)
                    ->getSearchResultsUsing(function (string $search): array {
                        return User::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn(User $user): array => [
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
                    ->placeholder('Write a clear subject line')
                    ->columnSpan(1)
                    ->maxLength(150),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->placeholder('Write your message to the selected user')
                    ->rows(8)
                    ->columnSpanFull()
                    ->maxLength(5000),
                FileUpload::make('attachment')
                    ->label('Attachment')
                    ->disk('local')
                    ->directory('email_attachments')
                    ->maxSize(10240) // 10 MB
                    ->columnSpanFull()
                    ->helperText('Optional: attach a single file (max 10 MB).'),
            ])
            ->columns(2)
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
            $mailable = new AdminUserMessageMail(
                user: $recipient,
                mailSubject: $state['subject'],
                mailBody: $state['message'],
                senderName: auth()->user()?->name,
            );

            if (!empty($state['attachment'])) {
                $paths = is_array($state['attachment']) ? $state['attachment'] : [$state['attachment']];

                $hackClub = app(HackClubCdnService::class);

                foreach ($paths as $path) {
                    $full = storage_path('app/' . ltrim($path, '\/'));
                    if (!is_file($full)) {
                        continue;
                    }

                    $originalName = basename($full);

                    // Build UploadedFile for HackClub upload
                    $uploaded = new HttpUploadedFile($full, $originalName, null, null, true);

                    try {
                        $url = $hackClub->uploadFileUrl($uploaded);

                        $resp = HttpClient::get($url);

                        if ($resp->successful()) {
                            $mime = $resp->header('Content-Type', 'application/octet-stream');
                            $mailable->attachData($resp->body(), $originalName, ['mime' => $mime]);
                        } else {
                            Log::warning('Failed to fetch uploaded file from Hack Club CDN', ['url' => $url, 'status' => $resp->status()]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Hack Club CDN upload/attach failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            Mail::to($recipient->email)->send($mailable);

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
