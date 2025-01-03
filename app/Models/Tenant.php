<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
{
    use HasDomains;

    protected $fillable = ['business_name', 'tenant_domain_id'];

    // 'data' カラムを JSON としてキャスト
    protected $casts = [
        'data' => 'json',
    ];

    /**
     * Get the domain associated with the tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function domain()
    {
        return $this->hasOne(Domain::class);
    }

    /**
     * テナントに関連付けられたユーザー
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
