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
            fn (string $text) => '• ' . $text,
            $items,
        ));
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    public static function normalize(mixed $input): array
    {
        if (is_string($input)) {
            return self::parseMultiline($input);
        }

        if (! is_array($input)) {
            return [];
        }

        $out = [];
        foreach ($input as $item) {
            if (is_string($item) && str_contains($item, "\n")) {
                foreach (self::parseMultiline($item) as $line) {
                    $out[] = $line;
                }
            } else {
                $text = trim((string) $item);
                if ($text !== '') {
                    $text = self::cleanItem($text);
                    if ($text !== '') {
                        $out[] = $text;
                    }
                }
            }
        }

        return array_values($out);
    }

    /**
     * Parse multiline string into clean array of lines
     *
     * @return list<string>
     */
    public static function parseMultiline(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $clean = self::cleanItem($line);
            if ($clean !== '') {
                $items[] = $clean;
            }
        }

        return array_values($items);
    }

    /**
     * Strip leading numbers, bullets, dots, dashes
     */
    public static function cleanItem(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Remove leading 1-, 1., 1), •, -, *, >
        $cleaned = preg_replace('/^(\d+[\-\.\)]\s*|[•\-\*\>]\s*)+/u', '', $text) ?? $text;

        return trim($cleaned);
    }

    /**
     * @return list<string>
     */
    public static function parseLegacy(?string $text): array
    {
        return self::parseMultiline($text);
    }
}

