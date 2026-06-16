<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GuildController extends Controller
{
    public function emblem(int $guildId)
    {
        // emblem_id increments every time the guild changes its emblem —
        // using it in the cache key means stale entries are auto-abandoned,
        // no manual invalidation needed (same strategy FluxCP uses with filenames).
        $emblemId = DB::connection('mysql_main')
            ->table('guild')
            ->where('guild_id', $guildId)
            ->value('emblem_id') ?? 0;

        $b64 = Cache::remember("guild_emblem_b64_{$guildId}_{$emblemId}", 3600, function () use ($guildId) {
            // Renewal uses the rAthena web service (guild_emblems, GIF/PNG).
            // Pre-renewal servers don't run the web service; emblems live in
            // guild.emblem_data as hex-encoded zlib-compressed BMP.
            $isRenewal = config('services.ra.game_mode') === 'renewal';

            $png = $isRenewal
                ? ($this->emblemFromWebDb($guildId)  ?? $this->emblemFromCharDb($guildId))
                : ($this->emblemFromCharDb($guildId) ?? $this->emblemFromWebDb($guildId));

            return $png ? base64_encode($png) : null;
        });

        if ($b64 === null) {
            abort(404);
        }

        return response(base64_decode($b64), 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    // ── Sources ───────────────────────────────────────────────────────────────

    private function emblemFromWebDb(int $guildId): ?string
    {
        try {
            $row = DB::connection('mysql_web')
                ->table('guild_emblems')
                ->select('file_data')
                ->where('guild_id', $guildId)
                ->first();

            if (!$row || empty($row->file_data)) return null;

            $img = @imagecreatefromstring($row->file_data);
            if ($img === false) return null;

            return $this->toPng($img);
        } catch (\Throwable) {
            return null;
        }
    }

    private function emblemFromCharDb(int $guildId): ?string
    {
        try {
            $row = DB::connection('mysql_main')
                ->table('guild')
                ->select('emblem_data', 'emblem_len')
                ->where('guild_id', $guildId)
                ->first();

            if (!$row || !$row->emblem_len || empty($row->emblem_data)) return null;

            // emblem_data is hex-encoded zlib-compressed BMP
            $bmp = @gzuncompress(pack('H*', $row->emblem_data));
            if ($bmp === false) return null;

            $img = $this->parseBmp($bmp);
            if ($img === false) return null;

            return $this->toPng($img);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── BMP parser ────────────────────────────────────────────────────────────
    // Ported from FluxCP's imagecreatefrombmpstring() with operator-precedence
    // bug fixed (PHP: & binds looser than ==, so ($r & 0xf8 == 0xf8) is wrong).

    private function parseBmp(string $im): \GdImage|false
    {
        if (strlen($im) < 54) return false;

        $header = unpack('vtype/Vsize/v2reserved/Voffset', substr($im, 0, 14));
        $info   = unpack('Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant',
                         substr($im, 14, 40));

        if ($header['type'] !== 0x4D42) return false;

        $width        = (int) $info['width'];
        $height       = (int) $info['height'];
        $bits         = (int) $info['bits'];
        $offset       = (int) $header['offset'];
        $palette_size = $offset - 54;

        $imres = imagecreatetruecolor($width, $height);
        imagealphablending($imres, false);
        imagesavealpha($imres, true);

        // Build color palette from BMP header
        $pal = [];
        if ($palette_size > 0) {
            $palette = substr($im, 54, $palette_size);
            $j = 0;
            $n = 0;
            while ($j < $palette_size) {
                $b = ord($palette[$j++]);
                $g = ord($palette[$j++]);
                $r = ord($palette[$j++]);
                $a = ord($palette[$j++]); // 4th byte in BGRX palette entry

                // rAthena uses #ff00ff (magenta) as chroma-key transparency.
                // Palette entry alpha > 127 OR fuzzy-magenta → fully transparent.
                if ($a > 127 || (($r & 0xf8) === 0xf8 && $g === 0 && ($b & 0xf8) === 0xf8)) {
                    $a = 127; // GD: 127 = fully transparent
                } else {
                    $a = 0;   // GD:   0 = fully opaque
                }
                $pal[$n++] = imagecolorallocatealpha($imres, $r, $g, $b, $a);
            }
        }

        $scan_line_size  = ((($bits * $width) + 7) >> 3);
        $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;
        $stride          = $scan_line_size + $scan_line_align;

        for ($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
            $scan_line = substr($im, $offset + $stride * $l, $scan_line_size);

            if ($bits === 24) {
                $j = 0;
                $n = 0;
                while ($j < $scan_line_size) {
                    $b = ord($scan_line[$j++]);
                    $g = ord($scan_line[$j++]);
                    $r = ord($scan_line[$j++]);
                    $a = (($r & 0xf8) === 0xf8 && $g === 0 && ($b & 0xf8) === 0xf8) ? 127 : 0;
                    imagesetpixel($imres, $n++, $i, imagecolorallocatealpha($imres, $r, $g, $b, $a));
                }
            } elseif ($bits === 8) {
                for ($j = 0; $j < $scan_line_size; $j++) {
                    imagesetpixel($imres, $j, $i, $pal[ord($scan_line[$j])]);
                }
            } elseif ($bits === 4) {
                $j = 0;
                $n = 0;
                while ($j < $scan_line_size) {
                    $byte = ord($scan_line[$j++]);
                    imagesetpixel($imres, $n++, $i, $pal[$byte >> 4]);
                    imagesetpixel($imres, $n++, $i, $pal[$byte & 0x0F]);
                }
            }
        }

        return $imres;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function toPng(\GdImage $img): string
    {
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return $png;
    }
}
