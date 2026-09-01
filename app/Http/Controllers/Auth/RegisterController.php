<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    private const ADMIN_ROLE_DESCRIPTION = 'Administrator master data, transaksi operasional, dan pelaporan tambak.';

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $adminRole = Role::query()->firstOrCreate(
                ['name' => 'Admin'],
                ['description' => self::ADMIN_ROLE_DESCRIPTION],
            );

            return User::query()->create([
                'role_id' => $adminRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'ACTIVE',
            ]);
        });

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
