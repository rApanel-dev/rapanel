<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Character;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CharacterController extends Controller
{
    private function getOwnedCharacter(int $charId): ?Character
    {
        $char = Character::where('char_id', $charId)->first();

        if (!$char) {
            return null;
        }

        $owns = GameAccount::where('account_id', $char->account_id)
            ->where('master_id', Auth::id())
            ->exists();

        return $owns ? $char : null;
    }

    public function resetPosition(Request $request, int $charId)
    {
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
            'metadata'   => [
                'char_id'   => $charId,
                'char_name' => $char->name,
                'reset_to'  => "{$map} ({$x},{$y})",
            ],
        ]);

        return back()->with('success', __('Position reset successfully.'));
    }

    public function resetLook(Request $request, int $charId)
    {
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
            'metadata'   => [
                'char_id'   => $charId,
                'char_name' => $char->name,
            ],
        ]);

        return back()->with('success', __('Look reset successfully.'));
    }
}
