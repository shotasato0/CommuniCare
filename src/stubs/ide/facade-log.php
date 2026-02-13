<?php

/**
 * IDE stub: Illuminate\Support\Facades\Log
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Support\Facades\Log
 */
namespace Illuminate\Support\Facades;

abstract class Log
{
    public static function warning(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
    public static function info(string $message, array $context = []): void {}
    public static function debug(string $message, array $context = []): void {}
    public static function critical(string $message, array $context = []): void {}
}
