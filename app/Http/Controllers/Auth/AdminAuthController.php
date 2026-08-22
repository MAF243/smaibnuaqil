<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\V3Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string','max:50'],
            'password' => ['required','string','min:4'],
        ]);

        $admin = Admin::where('username', $data['username'])->first();
        if (!$admin) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->withInput();
        }

        $stored = (string) $admin->password;
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
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_username', $admin->username);
        $request->session()->put('admin_role', $admin->role ?: 'superadmin');

        $account = V3Sync::ensureLegacyAdminAccount($admin);
        if ($account) {
            $request->session()->put('admin_account_id', $account->id);
            $roleCode = $account->roles()->pluck('roles.code')->first();
            if ($roleCode) {
                $request->session()->put('admin_role', $roleCode);
            }
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id','admin_username','admin_role','admin_account_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login.form');
    }
}
