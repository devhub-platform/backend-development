<?php

namespace App\Services\Chat;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIO;
use Illuminate\Support\Facades\Log;
use Exception;

class IngestionService
{
    public function extractText($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        try {
            if ($ext === 'pdf') {
                $parser = new PdfParser();
                return $parser->parseFile($file->getPathname())->getText();
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $phpWord = WordIO::load($file->getPathname());
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $el) {
                        if (method_exists($el, 'getText')) {
                            $text .= $el->getText() . "\n";
                        }
                    }
                }
                return $text;
            } elseif ($ext === 'txt') {
                return file_get_contents($file->getPathname());
            }
            return '';
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return '';
        }
    }
}
