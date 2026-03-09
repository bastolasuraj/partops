<?php

declare(strict_types=1);

$inputPath = $argv[1] ?? (__DIR__ . '/MASTER Inventory.xlsx');
$outputPath = $argv[2] ?? (__DIR__ . '/master_inventory_normalized.csv');

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    fwrite(STDERR, "Missing PhpSpreadsheet autoload.php. Install phpoffice/phpspreadsheet with Composer.\n");
    exit(1);
}

require $autoloadPath;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!file_exists($inputPath)) {
    fwrite(STDERR, "Input file not found: {$inputPath}\n");
    exit(1);
}

$spreadsheet = IOFactory::load($inputPath);

$outputHeaders = [
    'supplier_part_number',
    'name',
    'supplier_name',
    'location_raw',
    'location_aisle',
    'location_shelf',
    'location_bay',
    'quantity',
    'unit_price',
    'source_sheet',
    'source_row',
];

$out = fopen($outputPath, 'w');
if ($out === false) {
    fwrite(STDERR, "Unable to write output file: {$outputPath}\n");
    exit(1);
}

fputcsv($out, $outputHeaders);

$headerMap = [
    'part number' => 'supplier_part_number',
    'description' => 'name',
    'manufacturer (if known)' => 'supplier_name',
    'location' => 'location_raw',
    'quantity' => 'quantity',
    'cost/unit' => 'unit_price',
    'cost per unit' => 'unit_price',
];

$stats = [
    'sheets' => 0,
    'rows' => 0,
    'written' => 0,
    'skipped' => 0,
];

foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
    $stats['sheets']++;

    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    if ($highestRow < 2 || $highestColumnIndex < 1) {
        continue;
    }

    $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
    $columnMap = [];

    foreach ($headerRow as $index => $header) {
        $key = normalizeHeader($header);
        if (isset($headerMap[$key])) {
            $columnMap[$index] = $headerMap[$key];
        }
    }

    if (empty($columnMap)) {
        continue;
    }

    for ($row = 2; $row <= $highestRow; $row++) {
        $stats['rows']++;

        $rowValues = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false)[0];
        $rowData = [];

        foreach ($columnMap as $index => $field) {
            $rowData[$field] = $rowValues[$index] ?? '';
        }

        if (isRowEmpty($rowData)) {
            $stats['skipped']++;
            continue;
        }

        $supplierPartNumber = normalizeString($rowData['supplier_part_number'] ?? '');
        $name = normalizeString($rowData['name'] ?? '');
        $supplierName = normalizeString($rowData['supplier_name'] ?? '');
        $locationRaw = normalizeString($rowData['location_raw'] ?? '');

        [$aisle, $shelf, $bay] = parseLocation($locationRaw);

        $quantity = parseQuantity($rowData['quantity'] ?? '');
        $unitPrice = parseUnitPrice($rowData['unit_price'] ?? '');

        $outputRow = [
            $supplierPartNumber,
            $name,
            $supplierName,
            $locationRaw,
            $aisle,
            $shelf,
            $bay,
            $quantity,
            $unitPrice,
            $sheet->getTitle(),
            $row,
        ];

        fputcsv($out, $outputRow);
        $stats['written']++;
    }
}

fclose($out);

echo "Sheets: {$stats['sheets']}\n";
echo "Rows read: {$stats['rows']}\n";
echo "Rows written: {$stats['written']}\n";
echo "Rows skipped: {$stats['skipped']}\n";
echo "Output: {$outputPath}\n";

function normalizeHeader($value): string
{
    $header = is_string($value) ? $value : (string)$value;
    $header = strtolower(trim($header));
    $header = preg_replace('/\s+/', ' ', $header);
    return $header;
}

function normalizeString($value): string
{
    if ($value === null) {
        return '';
    }

    $text = is_string($value) ? $value : (string)$value;
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function isRowEmpty(array $rowData): bool
{
    foreach ($rowData as $value) {
        if (trim((string)$value) !== '') {
            return false;
        }
    }

    return true;
}

function parseQuantity($value): int
{
    $num = normalizeNumber($value);
    return (int)round($num);
}

function parseUnitPrice($value): string
{
    $num = normalizeNumber($value);
    return number_format($num, 2, '.', '');
}

function normalizeNumber($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    if (is_numeric($value)) {
        return (float)$value;
    }

    $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
    if ($clean === '' || $clean === '-' || $clean === '.') {
        return 0.0;
    }

    return (float)$clean;
}

function parseLocation(string $raw): array
{
    $clean = trim(preg_replace('/\s+/', ' ', $raw));
    if ($clean === '') {
        return ['', '', ''];
    }

    $prefix = '';
    $working = $clean;

    if (preg_match('/^shelf\s*([A-Za-z0-9]+)\b/i', $clean, $matches)) {
        $prefix = 'Shelf ' . $matches[1];
        $working = trim(substr($clean, strlen($matches[0])));
        $working = ltrim($working, '- ');
    }

    $parts = preg_split('/[.\/-]+/', $working);
    $parts = array_values(array_filter(array_map('trim', $parts), 'strlen'));

    if ($prefix !== '') {
        if (count($parts) < 2) {
            $tokens = preg_split('/\s+/', $working);
            $tokens = array_values(array_filter(array_map('trim', $tokens), 'strlen'));
            if (count($tokens) >= 2) {
                $parts = $tokens;
            }
        }

        return [$prefix, $parts[0] ?? '', $parts[1] ?? ''];
    }

    if (count($parts) >= 3) {
        return [$parts[0], $parts[1], $parts[2]];
    }

    if (count($parts) === 2) {
        return [$parts[0], $parts[1], ''];
    }

    $tokens = preg_split('/\s+/', $working);
    $tokens = array_values(array_filter(array_map('trim', $tokens), 'strlen'));
    if (count($tokens) >= 3) {
        return [$tokens[0], $tokens[1], $tokens[2]];
    }

    return [$clean, '', ''];
}
