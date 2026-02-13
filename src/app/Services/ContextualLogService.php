<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * コンテキストに応じて自動的にログチャンネルを選択するサービス
 * 
 * 実行コンテキスト（Web/CLI、ドメイン、ルート、ユーザーロール等）に基づいて
 * 適切なログチャンネルを自動選択し、日付ローテーションされたログファイルに出力します。
 */
class ContextualLogService
{
    /**
     * 利用可能なログチャンネルのリスト
     * config/logging.php の contextual_channels（LOG_CHANNELS 環境変数）でカンマ区切り指定可能
     * 例: "web,guest,admin,console"。指定がない場合は全チャンネルを使用。
     */
    protected array $availableChannels;

    /**
     * デフォルトチャンネル（チャンネルが1つだけの場合や判定できない場合）
     */
    protected string $defaultChannel;

    public function __construct()
    {
        // config 経由で利用可能なチャンネルを取得（config:cache 環境でも正しく動作）
        $channels = config('logging.contextual_channels', 'web,guest,admin,console');
        $this->availableChannels = array_map('trim', explode(',', $channels));
        
        // デフォルトチャンネル（最初のチャンネル、または'web'）
        $this->defaultChannel = $this->availableChannels[0] ?? 'web';
    }

    /**
     * 現在の実行コンテキストから適切なログチャンネルを決定
     */
    protected function determineChannel(): string
    {
        // CLI環境の場合（artisanコマンド、キューワーカー等）
        if (app()->runningInConsole()) {
            return $this->getChannelIfAvailable('console', $this->defaultChannel);
        }

        // Web環境の場合
        try {
            $request = request();
            
            if (!$request) {
                return $this->defaultChannel;
            }

            // ゲストドメインかどうかを判定
            $host = $request->getHost();
            $guestDomain = config('guest.domains.' . config('app.env'), null);
            
            if ($guestDomain && $host === $guestDomain) {
                return $this->getChannelIfAvailable('guest', $this->defaultChannel);
            }

            // 管理者ルートかどうかを判定
            $route = Route::current();
            if ($route) {
                $routeName = $route->getName();
                $routePath = $route->uri();
                
                // 管理者関連のルートかチェック
                if ($this->isAdminRoute($routeName, $routePath)) {
                    return $this->getChannelIfAvailable('admin', $this->defaultChannel);
                }
            }

            // 認証済みユーザーが管理者ロールを持っているかチェック
            if (Auth::check()) {
                $user = Auth::user();
                if ($user instanceof User && $user->hasRole('admin')) {
                    // 管理者ロールを持つが、管理者専用ルートでない場合は'web'チャンネル
                    // （管理者も一般機能を使う場合があるため）
                    return $this->getChannelIfAvailable('web', $this->defaultChannel);
                }
            }

            // デフォルトは一般ユーザー（web）
            return $this->getChannelIfAvailable('web', $this->defaultChannel);
            
        } catch (\Exception $e) {
            // エラーが発生した場合はデフォルトチャンネルを使用
            return $this->defaultChannel;
        }
    }

    /**
     * 管理者ルートかどうかを判定
     * ルート名が null の場合でも、パスが admin/ で始まれば管理者ルートとして扱う
     */
    protected function isAdminRoute(?string $routeName, string $routePath): bool
    {
        // ルート名で判定（null の場合はスキップ）
        if ($routeName !== null && str_starts_with($routeName, 'admin.')) {
            return true;
        }

        // パスで判定（名前のないルートも admin/ で始まれば管理者ルート）
        if (str_starts_with($routePath, 'admin/')) {
            return true;
        }

        return false;
    }

    /**
     * チャンネルが利用可能な場合にそのチャンネルを返し、そうでない場合はフォールバックを返す
     */
    protected function getChannelIfAvailable(string $channel, string $fallback): string
    {
        // チャンネルが1つだけの場合は常にそのチャンネルを使用
        if (count($this->availableChannels) === 1) {
            return $this->availableChannels[0];
        }

        // チャンネルが利用可能な場合
        if (in_array($channel, $this->availableChannels, true)) {
            return $channel;
        }

        // フォールバックチャンネルが利用可能かチェック
        if (in_array($fallback, $this->availableChannels, true)) {
            return $fallback;
        }

        // どちらも利用できない場合は最初のチャンネル
        return $this->availableChannels[0] ?? 'web';
    }

    /**
     * ログを記録
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $channel = $this->determineChannel();
        Log::channel($channel)->{$level}($message, $context);
    }

    /**
     * 動的メソッド呼び出し（Log::info(), Log::error() 等に対応）
     */
    public function __call(string $method, array $arguments): void
    {
        // サポートされているログレベル
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        
        if (in_array($method, $levels, true)) {
            $message = $arguments[0] ?? '';
            $context = $arguments[1] ?? [];
            $this->log($method, $message, $context);
        } else {
            throw new \BadMethodCallException("Method [{$method}] does not exist on " . static::class);
        }
    }
}
