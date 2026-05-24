<?php

namespace App\Services;

class MapSpawnParser
{
    /**
     * Parse map_cache.dat and return all map dimensions.
     *
     * rAthena/eAthena map_cache binary layout:
     *   Bytes 0–3 : uint32  file_size
     *   Bytes 4–5 : uint16  map_count
     *   Bytes 6–7 : 2 bytes padding (struct alignment in C)  ← data starts at offset 8
     *   Per map   : char[12] name  +  int16 xs  +  int16 ys  +  uint32 len  +  <len> bytes zlib cells
     *
     * The 2-byte padding after map_count is the root cause of off-by-two bugs when
     * starting at offset 6 instead of 8. Confirmed against the reference FluxCP implementation.
     *
     * Returns ['map_name' => ['width' => int, 'height' => int], ...]
     */
    public function parseMapCache(string $filePath): array
    {
        $data = file_get_contents($filePath);
        if ($data === false || strlen($data) < 8) return [];

        // map_count lives at bytes 4–5 (uint16); bytes 6–7 are padding → data starts at 8
        $expected = (int) unpack('x4/vmap_count', substr($data, 0, 6))['map_count'];

        // Try nameLen 12 first (standard eAthena/rAthena), then 16 for newer builds.
        // Return on exact match; otherwise return the candidate with the most maps.
        $best = [];
        foreach ([12, 16, 20, 24] as $nameLen) {
            $result = $this->doParseMapCache($data, $expected, $nameLen);
            if (count($result) === $expected) return $result;
            if (count($result) > count($best)) $best = $result;
        }

        return $best;
    }

    private function doParseMapCache(string $data, int $expected, int $nameLen): array
    {
        $offset   = 8; // skip 4-byte file_size + 2-byte map_count + 2-byte padding
        $totalLen = strlen($data);
        $maps     = [];

        for ($i = 0; $i < $expected; $i++) {
            $headerSize = $nameLen + 2 + 2 + 4; // name + xs + ys + dataLen
            if ($offset + $headerSize > $totalLen) break;

            $info    = unpack("a{$nameLen}name/vxs/vys/Vlen", substr($data, $offset, $headerSize));
            $offset += $headerSize;

            $mapName = rtrim($info['name'], "\0");
            $mapName = preg_replace('/\.gat$/i', '', $mapName);
            // Strip any non-ASCII or non-printable bytes left after null terminator
            $mapName = preg_replace('/[^a-zA-Z0-9_@]/', '', $mapName);

            // Sanity-check dimensions and compressed-data length.
            // If any field is out of range the nameLen is wrong — abort this attempt.
            if ($info['xs'] < 1 || $info['xs'] > 1023) break;
            if ($info['ys'] < 1 || $info['ys'] > 1023) break;
            if ($info['len'] > 600_000)                 break; // no map cell data exceeds ~600 KB compressed

            if ($mapName !== '') {
                $maps[$mapName] = [
                    'width'  => (int) $info['xs'],
                    'height' => (int) $info['ys'],
                ];
            }

            $offset += (int) $info['len'];
            if ($offset > $totalLen) break;
        }

        return $maps;
    }

    /**
     * Parse rAthena spawn TXT files (npc/ directory files).
     *
     * Line format:
     *   mapname,x,y,rx,ry<TAB>monster<TAB>MobName<TAB>MobID,Amount,Delay1,Delay2[,Event]
     *
     * Lines starting with // are comments.
     * boss_monster is treated the same as monster.
     *
     * Returns array of spawn-entry arrays (not yet including timestamps).
     */
    /**
     * Parse a single spawn line into its 4 logical fields:
     * [location, type, mob_name, spawn_params]
     *
     * Handles both standard tab-separated and space-padded rAthena formats.
     * The spawn_params field always starts with a digit (mob_id).
     * Returns null if the line cannot be recognized.
     */
    private function splitSpawnLine(string $line): ?array
    {
        // Standard rAthena format: fields separated by one or more TABs.
        $fields = preg_split('/\t+/', $line, 4);
        if (count($fields) === 4) {
            $type = strtolower(trim($fields[1]));
            if (in_array($type, ['monster', 'boss_monster'])) {
                return [$fields[0], $type, $fields[2], trim($fields[3])];
            }
        }

        // Fallback: any whitespace as separator, spawn params identified by
        // leading digit.  "Mob Name" with spaces is preserved because the
        // spawn_params field must start with a digit (mob_id).
        if (preg_match(
            '/^(\S+)\s+(monster|boss_monster)\s+(.+?)\s+(\d[\d,]*)$/i',
            $line,
            $m
        )) {
            return [$m[1], strtolower($m[2]), $m[3], $m[4]];
        }

        return null;
    }

    /**
     * Like parseSpawnFiles but also returns the first 20 skipped non-comment lines
     * so the caller can surface them as debug info.
     * Returns ['spawns' => [...], 'skipped' => [...]]
     */
    public function parseSpawnFilesDebug(array $filePaths): array
    {
        $skipped = [];
        $spawns  = $this->parseSpawnFilesInner($filePaths, $skipped);
        return ['spawns' => $spawns, 'skipped' => array_slice($skipped, 0, 20)];
    }

    public function parseSpawnFiles(array $filePaths): array
    {
        return $this->parseSpawnFilesInner($filePaths);
    }

    private function parseSpawnFilesInner(array $filePaths, array &$skipped = []): array
    {
        $spawns = [];

        foreach ($filePaths as $filePath) {
            $content = file_get_contents($filePath);
            if ($content === false) continue;

            foreach (explode("\n", $content) as $rawLine) {
                $line = trim($rawLine);
                if ($line === '' || str_starts_with($line, '//')) continue;

                // Strip inline comments
                $line = preg_replace('/\s*\/\/.*$/', '', $line);
                $line = trim($line);
                if ($line === '') continue;

                $fields = $this->splitSpawnLine($line);
                if ($fields === null) {
                    $skipped[] = $line;
                    continue;
                }

                [$location, , $mobName, $spawnParamStr] = $fields;

                $locationParts = explode(',', $location);
                $mapName = trim($locationParts[0] ?? '');
                if ($mapName === '') continue;

                $x  = (int) ($locationParts[1] ?? 0);
                $y  = (int) ($locationParts[2] ?? 0);
                $rx = (int) ($locationParts[3] ?? 0);
                $ry = (int) ($locationParts[4] ?? 0);

                $spawnParts = explode(',', $spawnParamStr);
                $mobId  = (int) ($spawnParts[0] ?? 0);
                $amount = (int) ($spawnParts[1] ?? 1);
                $delay1 = (int) ($spawnParts[2] ?? 0);
                $delay2 = (int) ($spawnParts[3] ?? 0);

                if ($mobId <= 0) continue;

                $spawns[] = [
                    'map_name' => $mapName,
                    'mob_id'   => $mobId,
                    'mob_name' => mb_substr(trim($mobName), 0, 64),
                    'x'        => max(0, $x),
                    'y'        => max(0, $y),
                    'rx'       => max(0, $rx),
                    'ry'       => max(0, $ry),
                    'amount'   => max(1, min(255, $amount)),
                    'delay1'   => max(0, $delay1),
                    'delay2'   => max(0, $delay2),
                ];
            }
        }

        return $spawns;
    }
}
