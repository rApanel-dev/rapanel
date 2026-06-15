<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WoeSchedule extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'woe_schedules';

    protected $fillable = ['label', 'type', 'start_day', 'start_time', 'end_day', 'end_time', 'enabled'];

    protected $casts = [
        'type'      => 'integer',
        'start_day' => 'integer',
        'end_day'   => 'integer',
        'enabled'   => 'boolean',
    ];

    public const TYPE_LABELS = [
        1 => 'WOE 1 (FE)',
        2 => 'WOE 2 (SE)',
        3 => 'WOE TE',
    ];

    public const DAY_NAMES = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday',  3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'WOE 1 (FE)';
    }

    /**
     * Returns true if this schedule is currently active, based on server time.
     */
    public function isActive(): bool
    {
        $now = Carbon::now();

        $startDay  = self::DAY_NAMES[$this->start_day];
        $endDay    = self::DAY_NAMES[$this->end_day];
        $startTime = Carbon::parse("last {$startDay} {$this->start_time}");
        $endTime   = Carbon::parse("last {$endDay} {$this->end_time}");

        // Adjust to nearest window within the same week
        if ($startTime->gt($endTime)) {
            $endTime->addWeek();
        }
        if ($now->gt($endTime)) {
            $startTime->addWeek();
            $endTime->addWeek();
        }

        return $now->between($startTime, $endTime);
    }

    /**
     * Returns the next start datetime (Carbon) for this schedule.
     */
    public function nextStart(): Carbon
    {
        $now      = Carbon::now();
        $startDay = self::DAY_NAMES[$this->start_day];
        $next     = Carbon::parse("last {$startDay} {$this->start_time}");

        if ($next->lte($now)) {
            $next->addWeek();
        }

        return $next;
    }

    /**
     * Build the global WOE status from all enabled schedules.
     * Returns: ['active' => bool, 'active_types' => int[], 'next' => [...]]
     */
    public static function buildStatus(): array
    {
        $schedules = self::where('enabled', true)->orderBy('start_day')->orderBy('start_time')->get();

        $activeTypes = [];
        $nextInfo    = null;
        $nextDiff    = PHP_INT_MAX;

        foreach ($schedules as $s) {
            if ($s->isActive()) {
                $activeTypes[] = $s->type;
            } else {
                $diff = $s->nextStart()->diffInSeconds(Carbon::now());
                if ($diff < $nextDiff) {
                    $nextDiff = $diff;
                    $nextInfo = [
                        'label'      => $s->label ?: $s->getTypeLabel(),
                        'type'       => $s->type,
                        'day'        => self::DAY_NAMES[$s->start_day],
                        'start_time' => substr($s->start_time, 0, 5),
                        'end_time'   => substr($s->end_time, 0, 5),
                        'timestamp'  => $s->nextStart()->timestamp,
                    ];
                }
            }
        }

        return [
            'active'       => count($activeTypes) > 0,
            'active_types' => array_unique($activeTypes),
            'next'         => $nextInfo,
            'total'        => $schedules->count(),
        ];
    }
}
