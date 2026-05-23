<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobDb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Yaml\Yaml;

class MobDbController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total'     => MobDb::count(),
            'mvps'      => MobDb::where('is_mvp', true)->count(),
            'boss'      => MobDb::where('is_mvp', false)->where('class', 'Boss')->count(),
            'normal'    => MobDb::where('is_mvp', false)->where('class', '!=', 'Boss')->count(),
            'last_sync' => MobDb::max('updated_at'),
        ];

        $raw          = config('services.ra.game_mode', 'renewal');
        $serverConfig = [
            'emulator'    => config('services.ra.emulator', 'rathena'),
            'server_mode' => str_starts_with($raw, 'pre') ? 'Pre-Renewal' : 'Renewal',
        ];

        return Inertia::render('Admin/MobDb/Index', compact('stats', 'serverConfig'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'yml_file' => 'required|file|max:51200',
        ]);

        set_time_limit(0);

        $content = file_get_contents($request->file('yml_file')->getRealPath());

        try {
            $parsed = Yaml::parse($content);
        } catch (\Exception $e) {
            return back()->withErrors(['yml_file' => 'YAML inválido: ' . $e->getMessage()]);
        }

        // mob_db.yml tiene estructura Header/Body — extraer Body si existe
        $mobs = isset($parsed['Body']) && is_array($parsed['Body'])
            ? $parsed['Body']
            : $parsed;

        if (!is_array($mobs)) {
            return back()->withErrors(['yml_file' => 'Formato de mob_db.yml inválido']);
        }

        $imported = 0;
        $updated  = 0;
        $failed   = 0;
        $now      = now()->toDateTimeString();

        foreach (array_chunk($mobs, 200) as $chunk) {
            $rows = [];

            foreach ($chunk as $mob) {
                if (empty($mob['Id'])) continue;

                $isMvp = isset($mob['Modes']['Mvp']) && $mob['Modes']['Mvp'] === true;

                $stats = array_filter([
                    'sp'                  => $mob['Sp']                 ?? 1,
                    'base_exp'            => $mob['BaseExp']            ?? 0,
                    'job_exp'             => $mob['JobExp']             ?? 0,
                    'attack'              => $mob['Attack']             ?? 0,
                    'attack2'             => $mob['Attack2']            ?? 0,
                    'defense'             => $mob['Defense']            ?? 0,
                    'magic_defense'       => $mob['MagicDefense']       ?? 0,
                    'resistance'          => $mob['Resistance']         ?? 0,
                    'magic_resistance'    => $mob['MagicResistance']    ?? 0,
                    'str'                 => $mob['Str']                ?? 1,
                    'agi'                 => $mob['Agi']                ?? 1,
                    'vit'                 => $mob['Vit']                ?? 1,
                    'int'                 => $mob['Int']                ?? 1,
                    'dex'                 => $mob['Dex']                ?? 1,
                    'luk'                 => $mob['Luk']                ?? 1,
                    'attack_range'        => $mob['AttackRange']        ?? 0,
                    'skill_range'         => $mob['SkillRange']         ?? 0,
                    'chase_range'         => $mob['ChaseRange']         ?? 0,
                    'element_level'       => $mob['ElementLevel']       ?? 1,
                    'walk_speed'          => $mob['WalkSpeed']          ?? 150,
                    'attack_delay'        => $mob['AttackDelay']        ?? 0,
                    'attack_motion'       => $mob['AttackMotion']       ?? 0,
                    'client_attack_motion'=> $mob['ClientAttackMotion'] ?? 0,
                    'damage_motion'       => $mob['DamageMotion']       ?? 0,
                    'damage_taken'        => $mob['DamageTaken']        ?? 100,
                    'ai'                  => $mob['Ai']                 ?? 6,
                ], fn($v) => $v !== null);

                $drops = array_map(fn($d) => array_filter([
                    'item'    => $d['Item'] ?? '',
                    'rate'    => $d['Rate'] ?? 0,
                    'nosteal' => !empty($d['StealProtected']) ? true : null,
                ], fn($v) => $v !== null), $mob['Drops'] ?? []);

                $mvpDrops = array_map(fn($d) => [
                    'item' => $d['Item'] ?? '',
                    'rate' => $d['Rate'] ?? 0,
                ], $mob['MvpDrops'] ?? []);

                $rows[] = [
                    'id'          => (int) $mob['Id'],
                    'aegis_name'  => (string) ($mob['AegisName']     ?? ''),
                    'name'        => (string) ($mob['Name']          ?? ''),
                    'level'       => (int)    ($mob['Level']         ?? 1),
                    'hp'          => (int)    ($mob['Hp']            ?? 1),
                    'base_exp'    => (int)    ($mob['BaseExp']       ?? 0),
                    'mvp_exp'     => (int)    ($mob['MvpExp']        ?? 0),
                    'is_mvp'      => $isMvp,
                    'element'     => (string) ($mob['Element']       ?? 'Neutral'),
                    'race'        => (string) ($mob['Race']          ?? 'Formless'),
                    'size'        => (string) ($mob['Size']          ?? 'Small'),
                    'class'       => (string) ($mob['Class']         ?? 'Normal'),
                    'stats'       => json_encode($stats),
                    'modes'       => json_encode($mob['Modes']       ?? []),
                    'race_groups' => json_encode($mob['RaceGroups']  ?? []),
                    'drops'       => json_encode($drops),
                    'mvp_drops'   => json_encode($mvpDrops),
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ];
            }

            if (empty($rows)) continue;

            try {
                $ids      = array_column($rows, 'id');
                $existing = MobDb::whereIn('id', $ids)->pluck('id')->flip()->all();

                MobDb::upsert($rows, ['id'], [
                    'aegis_name', 'name', 'level', 'hp', 'base_exp', 'mvp_exp',
                    'is_mvp', 'element', 'race', 'size', 'class',
                    'stats', 'modes', 'race_groups', 'drops', 'mvp_drops', 'updated_at',
                ]);

                foreach ($ids as $id) {
                    isset($existing[$id]) ? $updated++ : $imported++;
                }
            } catch (\Throwable) {
                $failed += count($rows);
            }
        }

        return back()->with([
            'success'  => __('Import complete'),
            'imported' => $imported,
            'updated'  => $updated,
            'failed'   => $failed,
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $count = MobDb::count();
        MobDb::truncate();
        return back()->with('success', __(':count mobs deleted.', ['count' => $count]));
    }
}
