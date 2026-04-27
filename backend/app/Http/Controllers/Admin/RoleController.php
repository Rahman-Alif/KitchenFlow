<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleController extends Controller
{
    /**
     * Get all available roles
     * Demonstrates the Roles table in action
     */
    public function index(): JsonResource
    {
        $roles = Role::all();

        return JsonResource::make([
            'success' => true,
            'data' => $roles,
            'message' => 'All roles retrieved successfully',
        ]);
    }

    /**
     * Get users with their assigned roles
     * Demonstrates the FK relationship between users and roles tables
     */
    public function usersWithRoles(Request $request): JsonResource
    {
        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->with('roleModel')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_enum' => $user->role, // backward compat: existing enum field
                    'role' => $user->roleModel ? [
                        'id' => $user->roleModel->id,
                        'name' => $user->roleModel->name,
                        'description' => $user->roleModel->description,
                    ] : null,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                ];
            });

        return JsonResource::make([
            'success' => true,
            'data' => $users,
            'message' => 'Users with roles retrieved successfully - demonstrates FK relationship',
        ]);
    }

    /**
     * Get a specific role with all its users
     * Demonstrates role hasMany users relationship
     */
    public function show(Role $role, Request $request): JsonResource
    {
        $roleWithUsers = [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'users_count' => $role->users()->count(),
            'users' => $role->users()
                ->where('tenant_id', $request->user()->tenant_id)
                ->get(['id', 'name', 'email', 'is_active'])
                ->toArray(),
        ];

        return JsonResource::make([
            'success' => true,
            'data' => $roleWithUsers,
            'message' => 'Role with users retrieved successfully',
        ]);
    }
}
