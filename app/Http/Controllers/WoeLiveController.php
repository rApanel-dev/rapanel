<?php

namespace App\Http\Controllers;

use App\Models\WoeSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WoeLiveController extends Controller
{
    // Fuente: /var/www/fluxcp/config/castlenames.php (nombres) +
    //         /var/www/rathena/db/castle_db.yml (mapa → ciudad)
    private const CASTLE_NAMES = [
        // WOE 1 FE — Al De Baran (aldeg_cas01-05)
        0  => ['name' => 'Neuschwanstein',    'map' => 'Al De Baran'],
        1  => ['name' => 'Hohenschwangau',    'map' => 'Al De Baran'],
        2  => ['name' => 'Nuenberg',          'map' => 'Al De Baran'],
        3  => ['name' => 'Wuerzburg',         'map' => 'Al De Baran'],
        4  => ['name' => 'Rothenburg',        'map' => 'Al De Baran'],
        // WOE 1 FE — Geffen (gefg_cas01-05)
        5  => ['name' => 'Repherion',         'map' => 'Geffen'],
        6  => ['name' => 'Eeyolbriggar',      'map' => 'Geffen'],
        7  => ['name' => 'Yesnelph',          'map' => 'Geffen'],
        8  => ['name' => 'Bergel',            'map' => 'Geffen'],
        9  => ['name' => 'Mersetzdeitz',      'map' => 'Geffen'],
        // WOE 1 FE — Payon (payg_cas01-05)
        10 => ['name' => 'Bright Arbor',      'map' => 'Payon'],
        11 => ['name' => 'Scarlet Palace',    'map' => 'Payon'],
        12 => ['name' => 'Holy Shadow',       'map' => 'Payon'],
        13 => ['name' => 'Sacred Altar',      'map' => 'Payon'],
        14 => ['name' => 'Bamboo Grove Hill', 'map' => 'Payon'],
        // WOE 1 FE — Prontera (prtg_cas01-05)
        15 => ['name' => 'Kriemhild',         'map' => 'Prontera'],
        16 => ['name' => 'Swanhild',          'map' => 'Prontera'],
        17 => ['name' => 'Fadhgridh',         'map' => 'Prontera'],
        18 => ['name' => 'Skoegul',           'map' => 'Prontera'],
        19 => ['name' => 'Gondul',            'map' => 'Prontera'],
        // WOE 1 FE — Novice / Niflheim (nguild_alde/gef/pay/prt)
        20 => ['name' => 'Novice Aldebaran',  'map' => 'Niflheim'],
        21 => ['name' => 'Novice Geffen',     'map' => 'Niflheim'],
        22 => ['name' => 'Novice Payon',      'map' => 'Niflheim'],
        23 => ['name' => 'Novice Prontera',   'map' => 'Niflheim'],
        // WOE 2 SE — Juno / Schwartzwald (schg_cas01-05)
        24 => ['name' => 'Himinn',            'map' => 'Juno'],
        25 => ['name' => 'Andlangr',          'map' => 'Juno'],
        26 => ['name' => 'Viblainn',          'map' => 'Juno'],
        27 => ['name' => 'Hljod',             'map' => 'Juno'],
        28 => ['name' => 'Skidbladnir',       'map' => 'Juno'],
        // WOE 2 SE — Arunafeltz (arug_cas01-05)
        29 => ['name' => 'Mardol',            'map' => 'Arunafeltz'],
        30 => ['name' => 'Cyr',               'map' => 'Arunafeltz'],
        31 => ['name' => 'Horn',              'map' => 'Arunafeltz'],
        32 => ['name' => 'Gefn',              'map' => 'Arunafeltz'],
        33 => ['name' => 'Bandis',            'map' => 'Arunafeltz'],
        // WOE TE — Al De Baran (te_aldecas1-5)
        34 => ['name' => 'Leilah',            'map' => 'Al De Baran'],
        35 => ['name' => 'Pavianne',          'map' => 'Al De Baran'],
        36 => ['name' => 'Jasmine',           'map' => 'Al De Baran'],
        37 => ['name' => 'Roxie',             'map' => 'Al De Baran'],
        38 => ['name' => 'Curly Sue',         'map' => 'Al De Baran'],
        // WOE TE — Prontera (te_prtcas01-05)
        39 => ['name' => 'Gaebolg',           'map' => 'Prontera'],
        40 => ['name' => 'Richard',           'map' => 'Prontera'],
        41 => ['name' => 'Wigner',            'map' => 'Prontera'],
        42 => ['name' => 'Heine',             'map' => 'Prontera'],
        43 => ['name' => 'Nerious',           'map' => 'Prontera'],
    ];

    public function events(): JsonResponse
    {
        // Cache the DB fetch + diff for 20 s to prevent hammering under concurrent polls
        $result = Cache::remember('woe_live_events_payload', 20, function () {
            $current  = $this->fetchCastleState();
            $previous = Cache::get('woe_castle_snapshot', []);
            $events   = Cache::get('woe_castle_events', []);

            if (!empty($previous)) {
                foreach ($current as $id => $data) {
                    $prevId = $previous[$id]['guild_id'] ?? 0;
                    if ($prevId !== $data['guild_id'] && $data['guild_id'] > 0) {
                        $events[] = [
                            'castle_id'   => $id,
                            'castle_name' => $data['castle_name'],
                            'castle_map'  => $data['castle_map'],
                            'guild_id'    => $data['guild_id'],
                            'guild_name'  => $data['guild_name'],
                            'from_guild'  => $prevId > 0 ? ($previous[$id]['guild_name'] ?? null) : null,
                            'ts'          => now()->toISOString(),
                        ];
                    }
                }
                // Keep last 20 events; persist for 4 h (covers a full WOE session)
                $events = array_values(array_slice($events, -20));
                Cache::put('woe_castle_events', $events, 3600 * 4);
            }

            // Save current snapshot (TTL 1 h, renewed each poll cycle)
            Cache::put('woe_castle_snapshot', $current, 3600);

            return [
                'castles' => array_values($current),
                'events'  => $events,
            ];
        });

        // active/current se recalculan en cada respuesta (misma caché 60s que HandleInertiaRequests)
        $woeStatus = Cache::remember('ra_woe_status', 60, fn () => WoeSchedule::buildStatus());
        $result['active']  = $woeStatus['active'] ?? false;
        $result['current'] = $woeStatus['current'] ?? [];

        return response()->json($result);
    }

    private function fetchCastleState(): array
    {
        try {
            $rows = DB::connection('mysql_main')
                ->table('guild_castle')
                ->leftJoin('guild', function ($join) {
                    $join->on('guild_castle.guild_id', '=', 'guild.guild_id')
                         ->where('guild_castle.guild_id', '>', 0);
                })
                ->select('guild_castle.castle_id', 'guild_castle.guild_id', 'guild.name as guild_name', 'guild.emblem_id')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $state = [];
        foreach ($rows as $row) {
            $info = self::CASTLE_NAMES[$row->castle_id] ?? null;
            $state[$row->castle_id] = [
                'castle_id'   => $row->castle_id,
                'castle_name' => $info ? $info['name'] : "Castle #{$row->castle_id}",
                'castle_map'  => $info['map'] ?? null,
                'guild_id'    => (int) $row->guild_id,
                'guild_name'  => $row->guild_name ?? null,
                'emblem_id'   => (int) ($row->emblem_id ?? 0),
            ];
        }
        return $state;
    }
}
