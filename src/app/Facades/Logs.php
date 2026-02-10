<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * コンテキスト対応ログファサード
 * 
 * 使用例:
 * Logs::info('メッセージ', ['context' => 'data']);
 * Logs::error('エラーが発生しました', ['exception' => $e]);
 */
class Logs extends Facade
{
    /**
     * ファサードのアクセサ名を取得
     */
    protected static function getFacadeAccessor(): string
    {
        return 'contextual.log';
    }
}
