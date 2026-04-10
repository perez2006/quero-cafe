<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function isValidDate(string $date): bool
{
    return is_valid_date($date);
}

function normalizeName(string $name): string
{
    return normalize_name($name);
}

function gramsFromRecord(array $record, array $quantityOptions): int
{
    $quantity = (string) ($record['quantidade'] ?? '');
    if (isset($quantityOptions[$quantity])) {
        return (int) $quantityOptions[$quantity];
    }
    if (preg_match('/^(\d+)g$/i', $quantity, $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

function formatWeight(int $grams): string
{
    return format_weight($grams);
}
