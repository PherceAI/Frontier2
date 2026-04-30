<?php

namespace App\Domain\Restaurant\Actions;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class ImportKitchenDailyStockFromDocx
{
    private const CATEGORIES = ['CARNES', 'DESAYUNOS', 'OTROS'];

    public function __construct(private readonly UpsertKitchenDailyStockItem $upsert) {}

    /**
     * @return array{items: int, categories: int}
     */
    public function handle(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("DOCX file not found: {$path}");
        }

        $documentXml = $this->documentXml($path);
        $rows = $this->rows($documentXml);
        $currentCategory = null;
        $categories = [];
        $items = 0;
        $importedAt = now();

        foreach ($rows as $row) {
            $firstCell = $this->clean($row[0] ?? '');

            if (count($row) === 1 && in_array($firstCell, self::CATEGORIES, true)) {
                $currentCategory = $firstCell;
                $categories[$firstCell] = true;

                continue;
            }

            if (! $currentCategory || count($row) < 6 || $firstCell === 'PRODUCTO') {
                continue;
            }

            $targetStock = $this->clean($row[1] ?? '');
            $unit = $this->clean($row[3] ?? '');

            if ($firstCell === '' || $targetStock === '' || $unit === '' || ! is_numeric(str_replace(',', '.', $targetStock))) {
                continue;
            }

            $this->upsert->handle([
                'category' => $currentCategory,
                'product_name' => $firstCell,
                'target_stock' => $targetStock,
                'unit' => $unit,
                'unit_detail' => $row[4] ?? null,
                'is_active' => true,
            ], $importedAt);

            $items++;
        }

        return ['items' => $items, 'categories' => count($categories)];
    }

    private function documentXml(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException("Unable to open DOCX file: {$path}");
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($documentXml)) {
            throw new RuntimeException('DOCX file does not contain word/document.xml.');
        }

        return $documentXml;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function rows(string $documentXml): array
    {
        $document = new DOMDocument();
        $document->loadXML($documentXml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $table = $xpath->query('//w:tbl')->item(0);

        if (! $table instanceof DOMElement) {
            return [];
        }

        $rows = [];

        foreach ($xpath->query('.//w:tr', $table) as $tableRow) {
            if (! $tableRow instanceof DOMElement) {
                continue;
            }

            $cells = [];

            foreach ($xpath->query('./w:tc', $tableRow) as $cell) {
                if (! $cell instanceof DOMElement) {
                    continue;
                }

                $texts = [];

                foreach ($xpath->query('.//w:t', $cell) as $textNode) {
                    $texts[] = $textNode->textContent;
                }

                $cells[] = $this->clean(implode('', $texts));
            }

            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private function clean(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) $value));
    }
}
