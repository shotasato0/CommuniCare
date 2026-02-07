<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\DB;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = null;
        if (tenant()) {
            $tenant = DB::table('tenants')
                ->select('id', 'business_name')
                ->where('id', tenant('id'))
                ->first();
        }

        $currentAdminId = null;

    if ($request->user()) {
        $tenantId = $request->user()->tenant_id;
        $admin = \App\Models\User::role('admin')
            ->where('tenant_id', $tenantId)
            ->first();
        $currentAdminId = $admin ? $admin->id : null;
    }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => $request->user() ? $request->user()->hasRole('admin') : false,
            ],
            'currentAdminId' => $currentAdminId,
            'tenant' => $tenant,
            'isGuest' => $request->user() && $request->user()->guest_session_id ? true : false,
            'guestSessionId' => $request->user() && $request->user()->guest_session_id ? $request->user()->guest_session_id : null,
            'flash' => [
                'message' => $request->session()->get('message'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
            ],
        ];
    }
}
