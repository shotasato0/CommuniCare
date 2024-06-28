<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase as TenantWithDatabaseContract;

class Tenant extends BaseTenant implements TenantWithDatabaseContract
{
    use HasDatabase, HasDomains;

    protected $fillable = ['name'];

    // 'data' カラムを JSON としてキャスト
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the database name for this tenant.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return 'tenant_' . $this->id;
    }

    /**
     * Get the database user for this tenant.
     *
     * @return string
     */
    public function getDatabaseUser()
    {
        return 'tenant_user_' . $this->id;
    }

    /**
     * Get the database password for this tenant.
     *
     * @return string
     */
    public function getDatabasePassword()
    {
        return 'tenant_password_' . $this->id;
    }
}
