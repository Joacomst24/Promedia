<?php

declare(strict_types=1);

const DATA_DIR = __DIR__ . '/../data';

function ensureDataFilesExist(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }

    $files = [
        'students.json' => [],
        'subjects.json' => [],
        'grades.json' => [],
    ];

    foreach ($files as $name => $defaultContent) {
        $path = DATA_DIR . '/' . $name;
        if (!file_exists($path)) {
            file_put_contents($path, json_encode($defaultContent, JSON_PRETTY_PRINT));
        }
    }
}

function readJsonFile(string $filename): array
{
    ensureDataFilesExist();

    $path = DATA_DIR . '/' . $filename;
    $content = file_get_contents($path);

    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);

    return is_array($decoded) ? $decoded : [];
}

function writeJsonFile(string $filename, array $data): void
{
    ensureDataFilesExist();

    $path = DATA_DIR . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function nextId(array $items): int
{
    if (empty($items)) {
        return 1;
    }

    $ids = array_map(static fn(array $item): int => (int)($item['id'] ?? 0), $items);

    return max($ids) + 1;
}
