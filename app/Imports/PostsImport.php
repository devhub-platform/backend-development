<?php

namespace App\Imports;

use App\Models\Post;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class PostsImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    public int $inserted = 0;
    public int $updated = 0;
    public int $tagAssignments = 0;
    /** @var array<int, array<string, mixed>> */
    public array $failures = [];

    public function __construct(
        private readonly int $userId,
        private readonly string $defaultStatus = 'published'
    ) {
    }

    public function model(array $row)
    {
        $id = $this->integerValue($row['article_id'] ?? null);
        $uuid = $this->stringValue($row['uuid'] ?? null) ?: (string) Str::uuid();
        $title = $this->stringValue($row['title'] ?? null) ?: 'Untitled post';
        $content = $this->stringValue($row['content'] ?? null) ?: '';
        $slug = Str::slug($title . '-' . Str::substr($uuid, 0, 8));

        $payload = [
            'id' => $id,
            'user_id' => $this->userId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $this->defaultStatus,
            'uuid' => $uuid,
            'read_time' => $this->estimateReadTime($content),
            'image_url' => null,
        ];

        $post = Post::withoutEvents(function () use ($uuid, $id, $payload) {
            $query = Post::query();

            if ($id !== null) {
                $query->whereKey($id);
            } else {
                $query->where('uuid', $uuid);
            }

            $existing = $query->first();

            if ($existing) {
                $existing->fill($payload);
                $existing->save();

                $this->updated++;

                return $existing;
            }

            $post = new Post($payload);
            $post->save();

            $this->inserted++;

            return $post;
        });

        return $post;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'article_id' => ['nullable', 'integer'],
            'uuid' => ['nullable', 'string'],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function onError(Throwable $e): void
    {
        report($e);
    }

    /**
     * @param  Failure  ...$failures
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }


    private function estimateReadTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));

        return max(1, (int) ceil($words / 200));
    }

    private function stringValue(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerValue(mixed $value): ?int
    {
        $value = $this->stringValue($value);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
