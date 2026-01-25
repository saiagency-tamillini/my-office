<?php

if (! function_exists('hasRole')) {
    /**
     * Usage:
     * hasRole('admin')
     * hasRole('admin', 'super_admin')
     * hasRole(['admin', 'super_admin'])
     */
    function hasRole(...$roles): bool
    {
        if (!auth()->check()) return false;

        $roleName = auth()->user()->role?->name;

        if (!$roleName) return false;

        if (count($roles) === 1 && is_array($roles[0])) {
            $roles = $roles[0];
        }

        return in_array($roleName, $roles, true);
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin(): ?string
    {
        $role_name = auth()->check() ? auth()->user()->role?->name : null;
        return $role_name === 'super_admin';
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): ?string
    {
        $role_name = auth()->check() ? auth()->user()->role?->name : null;
        return $role_name === 'admin' || $role_name === 'super_admin';
    }
}
