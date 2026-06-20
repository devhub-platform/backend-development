<?php

namespace App\Console\Commands;

use App\Imports\PostsImport;
use App\Models\User;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportPostsFromCsv extends Command
{
    protected $signature = 'posts:import-csv
                            {file : Path to the CSV file}
                            {--user-id= : User ID to assign imported posts to}
                            {--status=published : Default post status to use}';

    protected $description = 'Import posts from a CSV file into the posts table';

    public function handle(): int
    {
        $file = $this->resolvePath((string) $this->argument('file'));

        if (!is_file($file)) {
            $this->error("CSV file not found: {$file}");
            return self::FAILURE;
        }

        $userId = $this->resolveUserId();
        if (!$userId) {
            $this->error('No valid user found. Pass --user-id=ID or create at least one user first.');
            return self::FAILURE;
        }

        $status = (string) $this->option('status');
        $import = new PostsImport($userId, $status);

        try {
            Excel::import($import, $file);
        } catch (Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Import completed successfully.');
        $this->line('Inserted: ' . $import->inserted);
        $this->line('Updated: ' . $import->updated);
        $this->line('Tag assignments: ' . $import->tagAssignments);

        return self::SUCCESS;
    }

    private function resolveUserId(): ?int
    {
        $option = $this->option('user-id');

        if ($option !== null && $option !== '') {
            $userId = (int) $option;

            return User::whereKey($userId)->exists() ? $userId : null;
        }

        return User::query()->value('id');
    }

    private function resolvePath(string $path): string
    {
        $candidates = [
            $path,
            base_path($path),
            storage_path($path),
            storage_path('app/' . ltrim($path, '\\/')),
        ];

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false) {
                return $resolved;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }
}

