<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LegacyUser;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:50'],
            'password' => ['required','string','min:4'],
        ]);

        $user = LegacyUser::where('username', $data['username'])->first();
        if (!$user) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->withInput();
        }

        $stored = (string) $user->password;
        $ok = false;

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            $ok = Hash::check($data['password'], $stored);
        } else {
            $ok = md5($data['password']) === $stored;
        }

        if (!$ok) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put('user_id', $user->user_id);
        $request->session()->put('username', $user->username);

        $account = V3Sync::ensureLegacyUserAccount($user);
        if ($account) {
            $request->session()->put('account_id', $account->id);
        }

        return redirect()->route('student.dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:50', Rule::unique('users','username')],
            'password' => ['required','string','min:6','confirmed'],
        ]);

        $user = LegacyUser::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        $request->session()->regenerate();
        $request->session()->put('user_id', $user->user_id);
        $request->session()->put('username', $user->username);

        $account = V3Sync::ensureLegacyUserAccount($user);
        if ($account) {
            $request->session()->put('account_id', $account->id);
        }

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['user_id','username','account_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }
}
