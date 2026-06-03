<?php

namespace App\Support;

class InspectionTextLists
{
    /**
     * @return list<string>
     */
    public static function fromArea(?array $json, ?string $legacy): array
    {
        if (is_array($json) && $json !== []) {
            return self::normalize($json);
        }

        return self::parseLegacy($legacy);
    }

    /**
     * @param  list<string>  $items
     */
    public static function formatForStorage(array $items): string
    {
        $items = self::normalize($items);

        if ($items === []) {
            return '';
        }

        return implode("\n", array_map(
            fn (int $i, string $text) => ($i + 1) . '- ' . $text,
            array_keys($items),
            $items,
        ));
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    public static function normalize(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $out = [];
        foreach ($input as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return array_values($out);
    }

    /**
     * @return list<string>
     */
    public static function parseLegacy(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return self::normalize($decoded);
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^\d+[\-\.\)]\s*/u', '', $line) ?? $line;
            $items[] = trim($line);
        }

        return self::normalize($items);
    }
}
