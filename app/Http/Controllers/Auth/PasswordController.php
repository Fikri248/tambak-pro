<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(UpdatePasswordRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        DB::transaction(function () use ($currentSessionId, $request, $user): void {
            $user->forceFill([
                'password' => Hash::make($request->string('password')->toString()),
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table((string) config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->where('id', '!=', $currentSessionId)
                    ->delete();
            }
        });

        $request->session()->regenerate();

        $message = 'Password berhasil diubah.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('account.password.edit')
            ->with('success', $message);
    }
}
