<?php

namespace Tests\Support;

class UserStub
{
    public int $id;
    public int $tenant_id;
    private bool $isAdmin;

    public function __construct(int $id, int $tenantId, bool $isAdmin = false)
    {
        $this->id = $id;
        $this->tenant_id = $tenantId;
        $this->isAdmin = $isAdmin;
    }

    public function hasRole(string $role): bool
    {
        return $role === 'admin' && $this->isAdmin;
    }
}

