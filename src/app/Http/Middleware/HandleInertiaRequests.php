<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;
use Inertia\Support\Header;
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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Inertiaミドルウェアのロジックを実行
        Inertia::version(function () use ($request) {
            return $this->version($request);
        });

        Inertia::share($this->share($request));
        Inertia::setRootView($this->rootView($request));

        $response = $next($request);
        
        // Inertia\Responseの場合はHTTPレスポンスに変換（親クラスのhandleメソッドがheadersにアクセスする前に変換）
        if ($response instanceof \Inertia\Response) {
            $response = $response->toResponse($request);
        }

        // HTTPレスポンスの場合のみ、ヘッダーを設定
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->headers->set('Vary', Header::INERTIA);
        }

        if (! $request->header(Header::INERTIA)) {
            return $response;
        }

        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            if ($request->method() === 'GET' && $request->header(Header::VERSION, '') !== Inertia::getVersion()) {
                $response = parent::onVersionChange($request, $response);
            }

            if ($response->isOk() && empty($response->getContent())) {
                $response = parent::onEmptyResponse($request, $response);
            }

            if ($response->getStatusCode() === 302 && in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
                $response->setStatusCode(303);
            }
        }

        return $response;
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
            if (tenant()) {
                $tenant = DB::table('tenants')
                    ->select('id', 'business_name')
                    ->where('id', tenant('id'))
                    ->first();
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合は、エラーをログに記録して続行
            \Illuminate\Support\Facades\Log::warning('HandleInertiaRequests: Failed to query tenant', [
                'error' => $e->getMessage(),
            ]);
        }

        $currentAdminId = null;

        try {
            if ($request->user()) {
                $tenantId = $request->user()->tenant_id;
                $admin = \App\Models\User::role('admin')
                    ->where('tenant_id', $tenantId)
                    ->first();
                $currentAdminId = $admin ? $admin->id : null;
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合は、エラーをログに記録して続行
            \Illuminate\Support\Facades\Log::warning('HandleInertiaRequests: Failed to query admin', [
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
            \Illuminate\Support\Facades\Log::warning('HandleInertiaRequests: Session not available', [
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
