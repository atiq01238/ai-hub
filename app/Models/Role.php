<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'color'];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Quick lookup: "Can this role View AI Tools?" -> true/false
    public function can(string $module, string $action): bool
    {
        return $this->permissions
            ->where('module', $module)
            ->where('action', $action)
            ->first()?->allowed ?? false;
    }
}
