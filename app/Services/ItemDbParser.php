<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ItemDbParser
{
    /**
     * Parse a YAML file content and return the Body array.
     * Throws \RuntimeException on parse failure.
     */
    public function parseYml(string $content): array
    {
        try {
            $data = Yaml::parse($content);
        } catch (ParseException $e) {
            throw new \RuntimeException('YAML parse error: ' . $e->getMessage());
        }

        if (! isset($data['Body']) || ! is_array($data['Body'])) {
            return [];
        }

        return $data['Body'];
    }

    /**
     * Parse itemInfo.lub (single-file format) → [id => ['display_name', 'description_html']]
     *
     * Expected structure:
     *   tbl = {
     *     [501] = {
     *       identifiedDisplayName    = "Red Potion",
     *       identifiedResourceName   = "빨간포션",
     *       identifiedDescriptionName = { "line1", "line2" },
     *       ...
     *     },
     *   }
     */
    public function parseLuaItemInfo(string $content): array
    {
        $map = [];

        // Match each top-level [ID] = { ... } entry (handles nested braces from descriptionName arrays)
        preg_match_all('/\[(\d+)\]\s*=\s*\{((?:[^{}]|\{[^{}]*\})*)\}/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $id    = (int) $m[1];
            $block = $m[2];
            $entry = [];

            if (preg_match('/(?<!un)identifiedDisplayName\s*=\s*"([^"]*)"/u', $block, $nm)) {
                $entry['display_name'] = $nm[1];
            }

            if (preg_match('/(?<!un)identifiedDescriptionName\s*=\s*\{([^}]*)\}/s', $block, $dm)) {
                preg_match_all('/"([^"]*)"/u', $dm[1], $lines);
                $filtered = array_filter($lines[1], fn($l) => trim($l) !== '');
                $text = implode("\n", $filtered);
                $html = $this->colorCodesToHtml($text);
                // Remove consecutive blank lines that survive color-code-only lines
                $html = preg_replace('/(<br \/>(\s*<br \/>)+)/', '<br />', $html);
                $entry['description_html'] = trim($html);
            }

            if (! empty($entry)) {
                $map[$id] = $entry;
            }
        }

        return $map;
    }

    /**
     * Convert rAthena ^RRGGBB color codes to HTML <span> tags.
     * ^000000 resets to default color.
     */
    public function colorCodesToHtml(string $text): string
    {
        // Split on color code markers, capturing each hex code
        $parts = preg_split('/\^([0-9A-Fa-f]{6})/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }

        $html     = '';
        $inColor  = false;

        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                // Regular text segment
                $html .= htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
            } else {
                // Color code (6 hex chars)
                $color = strtoupper($part);
                if ($inColor) {
                    $html .= '</span>';
                    $inColor = false;
                }
                if ($color !== '000000') {
                    $html   .= "<span style=\"color:#{$color}\">";
                    $inColor = true;
                }
            }
        }

        if ($inColor) {
            $html .= '</span>';
        }

        return nl2br($html);
    }

    /**
     * Map a single YAML item array to our DB row format.
     * Returns empty array if Id is missing.
     */
    public function mapItem(
        array $item,
        array $luaInfo = [],
    ): array {
        $id = (int) ($item['Id'] ?? 0);
        if (! $id) {
            return [];
        }

        $lua = $luaInfo[$id] ?? [];

        $mainKeys   = ['Id', 'AegisName', 'Name', 'Type', 'SubType', 'Slots'];
        $properties = [];

        foreach ($item as $key => $val) {
            if (! in_array($key, $mainKeys, true)) {
                $properties[$key] = $val;
            }
        }

        return [
            'item_id'          => $id,
            'aegis_name'       => $item['AegisName'] ?? '',
            'name'             => $item['Name'] ?? '',
            'display_name'     => $lua['display_name'] ?? ($item['Name'] ?? null),
            'type'             => $item['Type'] ?? 'Etc',
            'subtype'          => $item['SubType'] ?? null,
            'slots'            => (int) ($item['Slots'] ?? 0),
            'description_html' => $lua['description_html'] ?? null,
            'is_custom'        => false,
            'properties'       => $properties ?: null,
        ];
    }
}
