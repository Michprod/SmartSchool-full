<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'department',
        'permissions',
        'last_login',
        'birth_date',
        'address',
        'city',
        'province',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // If user has role-based permissions from the role
        $role = Role::where('slug', $this->role)->first();
        if ($role && !empty($role->permissions)) {
            $rolePermissions = $role->permissions;
            
            // Check for wildcard permission (admin)
            if (in_array('*', $rolePermissions)) {
                return true;
            }
            
            // Check exact permission match
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
            
            // Check wildcard resource permission (e.g., 'students:*' matches 'students:read')
            $resource = explode(':', $permission)[0];
            if (in_array($resource . ':*', $rolePermissions)) {
                return true;
            }
        }
        
        // Check user-specific permissions (if any)
        if (!empty($this->permissions)) {
            if (in_array('*', $this->permissions)) {
                return true;
            }
            if (in_array($permission, $this->permissions)) {
                return true;
            }
            $resource = explode(':', $permission)[0];
            if (in_array($resource . ':*', $this->permissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the role model for this user
     */
    public function getRole()
    {
        return Role::where('slug', $this->role)->first();
    }

    /**
     * Get all permissions for this user (from role + user-specific)
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        // Get permissions from role
        $role = $this->getRole();
        if ($role && !empty($role->permissions)) {
            $permissions = array_merge($permissions, $role->permissions);
        }
        
        // Merge with user-specific permissions
        if (!empty($this->permissions)) {
            $permissions = array_merge($permissions, $this->permissions);
        }
        
        return array_unique($permissions);
    }
}
