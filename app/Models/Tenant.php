<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends Model implements TenantContract
{
    use HasFactory, HasDomains, TenantRun, HasDatabase;

    protected $fillable = [
        'name',
        'domain',
        'database',
    ];

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getKey();
    }

    public function getTenantIdentifier()
    {
        return $this->getKey();
    }

    public function getTenantKeyType(): string
    {
        return 'int';
    }

    public function getInternal(string $key, $default = null)
    {
        return $this->getAttribute($key) ?? $default;
    }

    public function setInternal(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
