<?php

namespace App\Services\Imports;

use Illuminate\Http\UploadedFile;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SpreadsheetReader
{
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new RuntimeException('Unsupported file type. Please upload CSV or XLSX.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
        }
        fclose($handle);

        return $this->normalizeRows($rows);
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required to import XLSX files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open the XLSX file.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->firstWorksheetPath($zip);
            $xml = $zip->getFromName($sheetPath);
            if ($xml === false) {
                throw new RuntimeException('The XLSX worksheet could not be read.');
            }

            $dom = new DOMDocument();
            if (! @$dom->loadXML($xml)) {
                throw new RuntimeException('The XLSX worksheet is invalid.');
            }
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $rows = [];
            foreach ($xpath->query('//x:sheetData/x:row') as $rowNode) {
                $cells = [];
                foreach ($xpath->query('./x:c', $rowNode) as $cellNode) {
                    $reference = $cellNode->attributes?->getNamedItem('r')?->nodeValue ?? 'A1';
                    $column = $this->columnIndex($reference);
                    $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue ?? '';
                    $value = '';

                    if ($type === 'inlineStr') {
                        foreach ($xpath->query('.//x:t', $cellNode) as $textNode) {
                            $value .= $textNode->textContent;
                        }
                    } else {
                        $valueNode = $xpath->query('./x:v', $cellNode)->item(0);
                        if ($valueNode) {
                            $raw = $valueNode->textContent;
                            $value = $type === 's' ? ($sharedStrings[(int) $raw] ?? '') : $raw;
                        }
                    }

                    $cells[$column] = trim((string) $value);
                }

                if ($cells) {
                    $max = max(array_keys($cells));
                    $normalized = [];
                    for ($i = 0; $i <= $max; $i++) {
                        $normalized[] = $cells[$i] ?? '';
                    }
                    $rows[] = $normalized;
                }
            }

            return $this->normalizeRows($rows);
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $dom = new DOMDocument();
        if (! @$dom->loadXML($xml)) {
            return [];
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($xpath->query('//x:si') as $item) {
            $text = '';
            foreach ($xpath->query('.//x:t', $item) as $textNode) {
                $text .= $textNode->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        // Our import template stores the company data in sheet1. Most exported spreadsheets do too.
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                return $name;
            }
        }

        throw new RuntimeException('No worksheet was found in the XLSX file.');
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function normalizeRows(array $rows): array
    {
        $rows = array_values(array_filter($rows, function ($row) {
            foreach ($row as $value) {
                if (trim((string) $value) !== '') return true;
            }
            return false;
        }));

        if (count($rows) < 2) {
            throw new RuntimeException('The spreadsheet does not contain any data rows.');
        }

        $header = array_map(fn ($value) => $this->normalizeHeader((string) $value), $rows[0]);
        $result = [];

        foreach (array_slice($rows, 1) as $rowNumber => $row) {
            $item = ['row_number' => $rowNumber + 2];
            foreach ($header as $index => $name) {
                $key = str_replace(' ', '_', trim($name));
                if ($key !== '') $item[$key] = trim((string) ($row[$index] ?? ''));
            }
            $result[] = $item;
        }

        return $result;
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', trim($value));
        return strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $value));
    }

}
