<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WoeSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WoeScheduleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Woe/Index', [
            'schedules' => WoeSchedule::orderBy('start_day')->orderBy('start_time')->get()
                ->map(fn ($s) => array_merge($s->toArray(), [
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time'   => substr($s->end_time, 0, 5),
                    'image_url'  => $s->image ? asset('storage/' . $s->image) : null,
                ])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'      => 'nullable|string|max:100',
            'type'       => 'required|integer|in:1,2,3',
            'start_day'  => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_day'    => 'required|integer|between:0,6',
            'end_time'   => 'required|date_format:H:i',
            'enabled'    => 'boolean',
            'image'      => 'nullable|file|mimes:jpeg,png,gif,webp|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('woe', 'public');
        } else {
            unset($data['image']);
        }

        WoeSchedule::create($data);
        Cache::forget('ra_woe_status');

        return back()->with('success', __('WOE schedule created.'));
    }

    public function update(Request $request, WoeSchedule $woe): RedirectResponse
    {
        $data = $request->validate([
            'label'        => 'nullable|string|max:100',
            'type'         => 'required|integer|in:1,2,3',
            'start_day'    => 'required|integer|between:0,6',
            'start_time'   => 'required|date_format:H:i',
            'end_day'      => 'required|integer|between:0,6',
            'end_time'     => 'required|date_format:H:i',
            'enabled'      => 'boolean',
            'image'        => 'nullable|file|mimes:jpeg,png,gif,webp|max:10240',
            'remove_image' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($woe->image) {
                Storage::disk('public')->delete($woe->image);
            }
            $data['image'] = $request->file('image')->store('woe', 'public');
        } elseif (!empty($data['remove_image'])) {
            if ($woe->image) {
                Storage::disk('public')->delete($woe->image);
            }
            $data['image'] = null;
        } else {
            unset($data['image']);
        }

        unset($data['remove_image']);
        $woe->update($data);
        Cache::forget('ra_woe_status');

        return back()->with('success', __('WOE schedule updated.'));
    }

    public function destroy(WoeSchedule $woe): RedirectResponse
    {
        if ($woe->image) {
            Storage::disk('public')->delete($woe->image);
        }
        $woe->delete();
        Cache::forget('ra_woe_status');

        return back()->with('success', __('WOE schedule deleted.'));
    }

    public function toggleEnabled(WoeSchedule $woe): RedirectResponse
    {
        $woe->update(['enabled' => !$woe->enabled]);
        Cache::forget('ra_woe_status');

        return back()->with('success', $woe->enabled
            ? __('WOE schedule enabled.')
            : __('WOE schedule disabled.')
        );
    }
}
