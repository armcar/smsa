<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TablePdfExport
{
    private static function fixText(mixed $value): string
    {
        $text = (string) ($value ?? '');

        if ($text === '') {
            return '';
        }

        // Fix common mojibake patterns seen in PT text (e.g. "AntÃ³nio" -> "António").
        return str_replace(
            [
                'Ã€', 'Ã', 'Ã‚', 'Ãƒ', 'Ã„', 'Ã…', 'Ã‡', 'Ãˆ', 'Ã‰', 'ÃŠ', 'Ã‹',
                'ÃŒ', 'Ã', 'ÃŽ', 'Ã', 'Ã‘', 'Ã’', 'Ã“', 'Ã”', 'Ã•', 'Ã–', 'Ã™',
                'Ãš', 'Ã›', 'Ãœ', 'Ã ', 'Ã¡', 'Ã¢', 'Ã£', 'Ã¤', 'Ã¥', 'Ã§', 'Ã¨',
                'Ã©', 'Ãª', 'Ã«', 'Ã¬', 'Ã­', 'Ã®', 'Ã¯', 'Ã±', 'Ã²', 'Ã³', 'Ã´',
                'Ãµ', 'Ã¶', 'Ã¹', 'Ãº', 'Ã»', 'Ã¼', 'â‚¬', 'â€“', 'â€”', 'Âº', 'Âª', 'Â',
            ],
            [
                'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë',
                'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù',
                'Ú', 'Û', 'Ü', 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è',
                'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô',
                'õ', 'ö', 'ù', 'ú', 'û', 'ü', '€', '–', '—', 'º', 'ª', '',
            ],
            $text,
        );
    }

    public static function download(
        string $filename,
        string $title,
        array $columns,
        array $rows,
    ): StreamedResponse {
        $normalizedColumns = array_map(fn ($column) => self::fixText($column), $columns);
        $normalizedRows = array_map(
            fn (array $row): array => array_map(fn ($value) => self::fixText($value), $row),
            $rows,
        );

        $pdf = Pdf::loadView('pdf.table_export', [
            'title' => self::fixText($title),
            'columns' => $normalizedColumns,
            'rows' => $normalizedRows,
            'generatedAt' => now()->format('d-m-Y H:i'),
            'orgName' => self::fixText(config('smsa.organization.name')),
            'orgNif' => self::fixText(config('smsa.organization.nif')),
            'orgAddressLine' => self::fixText(config('smsa.organization.address_line')),
            'orgPostalCity' => self::fixText(config('smsa.organization.postal_city')),
            'orgEmail' => self::fixText(config('smsa.organization.email')),
        ]);

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
