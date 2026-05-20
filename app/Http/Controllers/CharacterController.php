<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Character;
use App\Models\CharacterPreference;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CharacterController extends Controller
{
    private function getOwnedCharacter(int $charId): ?Character
    {
        $char = Character::where('char_id', $charId)->first();

        if (!$char) {
            return null;
        }

        if (Auth::user()->role === 'Admin') {
            return $char;
        }

        $owns = GameAccount::where('account_id', $char->account_id)
            ->where('master_id', Auth::id())
            ->exists();

        return $owns ? $char : null;
    }

    public function resetPosition(Request $request, int $charId)
    {
        $isAdmin = Auth::user()->role === 'Admin';

        $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $char = $this->getOwnedCharacter($charId);

        if (!$char) {
            return back()->withErrors(['error' => __('Character not found or unauthorized.')]);
        }

        if ($char->online > 0) {
            return back()->withErrors(['error' => __('Cannot reset position while the character is online.')]);
        }

        $map = config('services.ra.reset_map', 'prontera');
        $x   = (int) config('services.ra.reset_x', 150);
        $y   = (int) config('services.ra.reset_y', 180);

        $char->update([
            'last_map' => $map, 'last_x' => $x, 'last_y' => $y,
            'save_map' => $map, 'save_x' => $x, 'save_y' => $y,
        ]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'CHARACTER',
            'action'     => 'character_reset_position',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => array_filter([
                'account_id'     => $char->account_id,
                'char_id'        => $charId,
                'char_name'      => $char->name,
                'reset_to'       => "{$map} ({$x},{$y})",
                'admin_override' => $isAdmin ?: null,
                'admin_name'     => $isAdmin ? Auth::user()->name : null,
            ]),
        ]);

        return back()->with('success', __('Position reset successfully.'));
    }

    public function resetLook(Request $request, int $charId)
    {
        $isAdmin = Auth::user()->role === 'Admin';

        $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $char = $this->getOwnedCharacter($charId);

        if (!$char) {
            return back()->withErrors(['error' => __('Character not found or unauthorized.')]);
        }

        if ($char->online > 0) {
            return back()->withErrors(['error' => __('Cannot reset look while the character is online.')]);
        }

        $char->update([
            'hair'          => 1,
            'hair_color'    => 0,
            'clothes_color' => 0,
            'body'          => 0,
        ]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'CHARACTER',
            'action'     => 'character_reset_look',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => array_filter([
                'account_id'     => $char->account_id,
                'char_id'        => $charId,
                'char_name'      => $char->name,
                'admin_override' => $isAdmin ?: null,
                'admin_name'     => $isAdmin ? Auth::user()->name : null,
            ]),
        ]);

        return back()->with('success', __('Look reset successfully.'));
    }

    public function changeSlot(Request $request, int $charId)
    {
        $isAdmin = Auth::user()->role === 'Admin';

        $request->validate([
            'password' => ['required', 'string'],
            'slot'     => ['required', 'integer', 'min:0'],
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $char = $this->getOwnedCharacter($charId);

        if (!$char) {
            return back()->withErrors(['error' => __('Character not found or unauthorized.')]);
        }

        if ($char->online > 0) {
            return back()->withErrors(['error' => __('Cannot change slot while the character is online.')]);
        }

        $maxSlots   = (int) GameAccount::where('account_id', $char->account_id)->value('character_slots');
        $targetSlot = (int) $request->slot;

        if ($targetSlot < 0 || $targetSlot >= $maxSlots) {
            return back()->withErrors(['error' => __('Invalid slot selected.')]);
        }

        if ($targetSlot === (int) $char->char_num) {
            return back()->with('success', __('Slot changed successfully.'));
        }

        $targetChar = DB::connection('mysql_main')
            ->table('char')
            ->where('account_id', $char->account_id)
            ->where('char_num', $targetSlot)
            ->first();

        if ($targetChar && $targetChar->online > 0) {
            return back()->withErrors(['error' => __('The target slot is occupied by a character that is online.')]);
        }

        DB::connection('mysql_main')->transaction(function () use ($char, $targetChar, $targetSlot) {
            if ($targetChar) {
                DB::connection('mysql_main')->statement(
                    "UPDATE `char` SET char_num = CASE
                        WHEN char_id = ? THEN ?
                        WHEN char_id = ? THEN ?
                    END WHERE char_id IN (?, ?)",
                    [$char->char_id, $targetSlot, $targetChar->char_id, $char->char_num, $char->char_id, $targetChar->char_id]
                );
            } else {
                DB::connection('mysql_main')->table('char')
                    ->where('char_id', $char->char_id)
                    ->update(['char_num' => $targetSlot]);
            }
        });

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'CHARACTER',
            'action'     => 'character_slot_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => array_filter([
                'account_id'     => $char->account_id,
                'char_id'        => $charId,
                'char_name'      => $char->name,
                'from_slot'      => $char->char_num,
                'to_slot'        => $targetSlot,
                'swapped_with'   => $targetChar?->name,
                'admin_override' => $isAdmin ?: null,
                'admin_name'     => $isAdmin ? Auth::user()->name : null,
            ]),
        ]);

        return back()->with('success', __('Slot changed successfully.'));
    }

    public function updatePreferences(Request $request, int $charId)
    {
        $isAdmin = Auth::user()->role === 'Admin';

        $request->validate([
            'password'           => ['required', 'string'],
            'hide_from_rankings' => ['boolean'],
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $char = $this->getOwnedCharacter($charId);

        if (!$char) {
            return back()->withErrors(['error' => __('Character not found or unauthorized.')]);
        }

        CharacterPreference::updateOrCreate(
            ['char_id' => $charId],
            ['hide_from_rankings' => (bool) $request->boolean('hide_from_rankings')]
        );

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'CHARACTER',
            'action'     => 'character_preferences_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => array_filter([
                'account_id'         => $char->account_id,
                'char_id'            => $charId,
                'char_name'          => $char->name,
                'hide_from_rankings' => (bool) $request->boolean('hide_from_rankings'),
                'admin_override'     => $isAdmin ?: null,
                'admin_name'         => $isAdmin ? Auth::user()->name : null,
            ]),
        ]);

        return back()->with('success', __('Character preferences saved.'));
    }
}
