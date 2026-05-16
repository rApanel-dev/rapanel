<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GuildController extends Controller
{
    public function emblem(int $guildId)
    {
        // Store as base64 in cache — the ra_cache.value column is utf8 and
        // rejects raw binary (PNG bytes), so we encode/decode around it.
        $b64 = Cache::remember("guild_emblem_b64_{$guildId}", 300, function () use ($guildId) {
            $row = DB::connection('mysql_main')
                ->table('guild')
                ->select('emblem_data')
                ->where('guild_id', $guildId)
                ->first();

            if (!$row || empty($row->emblem_data)) {
                return null;
            }

            // emblem_data is stored as a hex string ("7801...") — convert to binary bytes
            $binary = pack('H*', $row->emblem_data);

            // binary is zlib-compressed (header 0x78xx) — decompress to get raw BMP
            $bmp = @gzuncompress($binary);
            if ($bmp === false) {
                return null;
            }

            // GD 2.x reads BMP natively; convert to PNG for browser compatibility
            $img = @imagecreatefromstring($bmp);
            if ($img === false) {
                $img = $this->parseBmp($bmp);
            }
            if ($img === false) {
                return null;
            }

            // rAthena uses #ff00ff as the chroma-key transparency color — replace with alpha
            $img = $this->applyMagentaTransparency($img);

            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            return base64_encode($png);
        });

        if ($b64 === null) {
            abort(404);
        }

        return response(base64_decode($b64), 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Converts every #ff00ff pixel to fully transparent.
     * rAthena guild emblems use magenta as their chroma-key transparency color.
     */
    private function applyMagentaTransparency(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        // Work on a truecolor canvas so we can store alpha values
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        // Fill with full transparency before copying
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        // Copy pixels, replacing magenta with transparency
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $color = imagecolorat($src, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8)  & 0xFF;
                $b =  $color        & 0xFF;

                if ($r === 255 && $g === 0 && $b === 255) {
                    imagesetpixel($dst, $x, $y, $transparent);
                } else {
                    $alpha = ($color >> 24) & 0x7F;
                    imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, $r, $g, $b, $alpha));
                }
            }
        }

        imagedestroy($src);
        return $dst;
    }

    /**
     * Manual BMP parser for 8-bit palette and 24-bit BMPs.
     * Used as fallback when GD's imagecreatefromstring() can't handle the file.
     */
    private function parseBmp(string $data)
    {
        if (strlen($data) < 54 || substr($data, 0, 2) !== 'BM') {
            return false;
        }

        $fh = unpack('vtype/Vsize/v2reserved/Voffset', substr($data, 0, 14));
        $ih = unpack('VdibSize/lwidth/lheight/vplanes/vbits/Vcompress/VimgSize/lxppm/lyppm/VclrUsed/VclrImp',
                     substr($data, 14, 40));

        $width   = $ih['width'];
        $height  = $ih['height'];
        $topDown = $height < 0;
        $height  = abs($height);
        $bits    = $ih['vbits'];
        $offset  = $fh['offset'];

        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            return false;
        }

        if ($bits === 8) {
            $palette = [];
            for ($i = 0; $i < 256; $i++) {
                $c = unpack('Cb/Cg/Cr', substr($data, 54 + $i * 4, 3));
                $palette[$i] = imagecolorallocate($img, $c['r'], $c['g'], $c['b']);
            }
            $stride = (int)(ceil($width / 4) * 4);
            for ($y = 0; $y < $height; $y++) {
                $srcRow  = $topDown ? $y : ($height - 1 - $y);
                $rowData = substr($data, $offset + $srcRow * $stride, $stride);
                for ($x = 0; $x < $width; $x++) {
                    imagesetpixel($img, $x, $y, $palette[ord($rowData[$x])]);
                }
            }
        } elseif ($bits === 24) {
            $stride = (int)(ceil($width * 3 / 4) * 4);
            for ($y = 0; $y < $height; $y++) {
                $srcRow  = $topDown ? $y : ($height - 1 - $y);
                $rowData = substr($data, $offset + $srcRow * $stride, $stride);
                for ($x = 0; $x < $width; $x++) {
                    $col = imagecolorallocate($img,
                        ord($rowData[$x * 3 + 2]),
                        ord($rowData[$x * 3 + 1]),
                        ord($rowData[$x * 3])
                    );
                    imagesetpixel($img, $x, $y, $col);
                }
            }
        } else {
            imagedestroy($img);
            return false;
        }

        return $img;
    }
}
