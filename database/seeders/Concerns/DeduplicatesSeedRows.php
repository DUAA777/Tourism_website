<?php

namespace Database\Seeders\Concerns;

trait DeduplicatesSeedRows
{
    protected function deduplicateSeedRows(array $rows, string $nameKey, string $locationKey, string $label): array
    {
        $uniqueRows = [];
        $seenKeys = [];
        $duplicateCount = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalizedRow = $this->normalizeSeedRow($row);
            $dedupeKey = $this->buildSeedRowKey($normalizedRow, $nameKey, $locationKey);

            if ($dedupeKey === '|') {
                $uniqueRows[] = $normalizedRow;
                continue;
            }

            if (!array_key_exists($dedupeKey, $seenKeys)) {
                $seenKeys[$dedupeKey] = count($uniqueRows);
                $uniqueRows[] = $normalizedRow;
                continue;
            }

            $duplicateCount++;
            $existingIndex = $seenKeys[$dedupeKey];
            $uniqueRows[$existingIndex] = $this->chooseRicherSeedRow($uniqueRows[$existingIndex], $normalizedRow);
        }

        if ($duplicateCount > 0) {
            $this->command?->warn("Removed {$duplicateCount} duplicate {$label} rows while seeding.");
        }

        return array_values($uniqueRows);
    }

    protected function normalizeSeedRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = preg_replace('/\s+/u', ' ', trim($value));
                continue;
            }

            if (is_array($value)) {
                $normalizedItems = [];
                $seenItems = [];

                foreach ($value as $item) {
                    if (!is_scalar($item)) {
                        continue;
                    }

                    $cleanItem = preg_replace('/\s+/u', ' ', trim((string) $item));
                    if ($cleanItem === '') {
                        continue;
                    }

                    $itemKey = $this->normalizeSeedKey($cleanItem);
                    if ($itemKey === '' || isset($seenItems[$itemKey])) {
                        continue;
                    }

                    $seenItems[$itemKey] = true;
                    $normalizedItems[] = $cleanItem;
                }

                $row[$key] = $normalizedItems;
            }
        }

        return $row;
    }

    protected function buildSeedRowKey(array $row, string $nameKey, string $locationKey): string
    {
        return $this->normalizeSeedKey($row[$nameKey] ?? '') . '|' . $this->normalizeSeedKey($row[$locationKey] ?? '');
    }

    protected function normalizeSeedKey(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $normalized = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = str_replace(['’', "'", '`'], '', $normalized);
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));

        if ($normalized === '') {
            return '';
        }

        return mb_strtolower($normalized, 'UTF-8');
    }

    protected function chooseRicherSeedRow(array $existingRow, array $candidateRow): array
    {
        return $this->calculateSeedRowScore($candidateRow) >= $this->calculateSeedRowScore($existingRow)
            ? $candidateRow
            : $existingRow;
    }

    protected function calculateSeedRowScore(array $row): int
    {
        $score = 0;

        foreach ($row as $value) {
            if (is_array($value)) {
                $score += count($value) * 3;
                continue;
            }

            if (is_numeric($value)) {
                $score += 2;
                continue;
            }

            if (is_string($value)) {
                $cleanValue = trim($value);

                if ($cleanValue === '') {
                    continue;
                }

                $score += 1 + min(5, intdiv(strlen($cleanValue), 50));
                continue;
            }

            if (!empty($value)) {
                $score += 1;
            }
        }

        return $score;
    }
}
