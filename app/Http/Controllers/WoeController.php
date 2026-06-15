<?php

namespace App\Http\Controllers;

use App\Models\WoeSchedule;
use Inertia\Inertia;
use Inertia\Response;

class WoeController extends Controller
{
    public function index(): Response
    {
        $schedules = WoeSchedule::where('enabled', true)
            ->orderBy('start_day')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'label'      => $s->label,
                'type'       => $s->type,
                'type_label' => $s->getTypeLabel(),
                'start_day'  => $s->start_day,
                'start_time' => substr($s->start_time, 0, 5),
                'end_day'    => $s->end_day,
                'end_time'   => substr($s->end_time, 0, 5),
                'is_active'  => $s->isActive(),
                'next_ts'    => $s->isActive() ? null : $s->nextStart()->timestamp,
            ])
            ->toArray();

        return Inertia::render('Info/Woe/Index', [
            'schedules' => $schedules,
        ]);
    }
}
