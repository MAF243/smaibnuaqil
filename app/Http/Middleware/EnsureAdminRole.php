<?php

namespace App\Http\Middleware;

use App\Support\V3Sync;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $current = (string) $request->session()->get('admin_role', 'panitia');

        $accountId = (int) $request->session()->get('admin_account_id', 0);
        if ($accountId) {
            $account = V3Sync::accountForLegacyAdminId((int) $request->session()->get('admin_id', 0));
            if ($account && $account->relationLoaded('roles') === false) {
                $account->load('roles');
            }
            if ($account && $account->roles->isNotEmpty()) {
                $current = (string) ($account->roles->pluck('code')->first() ?: $current);
                $request->session()->put('admin_role', $current);
            }
        }

        if ($roles && !in_array($current, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
