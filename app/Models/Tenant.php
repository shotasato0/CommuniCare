<?php

namespace App\Models;

use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * テナントモデル
 *
 * マルチテナンシーの基本となるモデルクラス
 */
class Tenant extends BaseTenant
{
    use HasDomains;

    /**
     * マスアサインメントを許可する属性
     */
    protected $guarded = [];

    /**
     * 属性のキャスト設定
     */
    protected $casts = [
        'data' => 'array'
    ];

    /**
     * テナントのカスタムカラムを定義
     *
     * @return array
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'business_name',
            'tenant_domain_id',
        ];
    }

    /**
     * モデルの初期起動時の処理
     */
    protected static function boot()
    {
        parent::boot();

        // 新規作成時にUUIDを自動生成
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
