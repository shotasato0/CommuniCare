<?php

namespace App\Http\Middleware;

require_once __DIR__ . '/../../../stubs/ide/tenant.php';

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Inertia\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Handle an incoming request.
     * Wraps $next so that Inertia\Response is converted to HTTP response before parent runs.
     * This keeps the recommended pattern: only override share() (and optionally version()/rootView).
     */
    public function handle($request, \Closure $next)
    {
        return parent::handle($request, function ($request) use ($next) {
            $response = $next($request);
            if ($response instanceof Response) {
                return $response->toResponse($request);
            }
            return $response;
        });
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = null;
        try {
            if (\tenant()) {
                $tenant = DB::table('tenants')
                    ->select('id', 'business_name')
                    ->where('id', \tenant('id'))
                    ->first();
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合は、エラーをログに記録して続行
            Log::warning('HandleInertiaRequests: Failed to query tenant', [
                'error' => $e->getMessage(),
            ]);
        }

        $currentAdminId = null;

        try {
            $user = $request->user();
            if ($user) {
                $tenantId = $user->tenant_id;
                /** @var User|null $admin */
                $admin = User::admins()
                    ->where('tenant_id', $tenantId)
                    ->first();
                $currentAdminId = $admin ? $admin->id : null;
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合は、エラーをログに記録して続行
            Log::warning('HandleInertiaRequests: Failed to query admin', [
                'error' => $e->getMessage(),
            ]);
        }

        // セッションが利用可能かどうかを確認
        $flash = [
            'message' => null,
            'success' => null,
            'error' => null,
            'info' => null,
        ];
        
        try {
            if ($request->hasSession()) {
                $flash = [
                    'message' => $request->session()->get('message'),
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                    'info' => $request->session()->get('info'),
                ];
            }
        } catch (\Exception $e) {
            // セッションが利用できない場合は、デフォルト値を使用
            Log::warning('HandleInertiaRequests: Session not available', [
                'error' => $e->getMessage(),
            ]);
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
            'flash' => $flash,
        ];
    }
}
