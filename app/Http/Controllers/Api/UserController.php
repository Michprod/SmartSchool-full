<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $users = $query->select([
            'id', 'first_name', 'last_name', 'email', 'role', 'department',
            'permissions', 'phone', 'avatar', 'is_active', 'last_login', 'created_at'
        ])->get();
        
        // Add role information to each user
        $users->transform(function ($user) {
            $role = \App\Models\Role::where('slug', $user->role)->first();
            $user->role_info = $role;
            $user->all_permissions = $user->getAllPermissions();
            return $user;
        });
        
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|exists:roles,slug',
            'department' => 'nullable|string|max:255',
            'permissions'=> 'nullable|array',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
        ]);
        
        if (!empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $data = substr($validated['avatar'], strpos($validated['avatar'], ',') + 1);
            $data = base64_decode($data);
            $imageName = 'avatar_' . time() . '_' . uniqid() . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/' . $imageName, $data);
            $validated['avatar'] = 'avatars/' . $imageName;
        }
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        
        // Load role info
        $user->role_info = Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        
        return response()->json($user->makeHidden(['password', 'remember_token']), 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $user->role_info = \App\Models\Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $id,
            'role'       => 'sometimes|exists:roles,slug',
            'department' => 'nullable|string|max:255',
            'permissions'=> 'nullable|array',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
            'is_active'  => 'sometimes|boolean',
            'password'   => 'nullable|string|min:8',
            'birth_date' => 'nullable|date',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'province'   => 'nullable|string|max:255',
            'bio'        => 'nullable|string',
        ]);
        
        if (!empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $data = substr($validated['avatar'], strpos($validated['avatar'], ',') + 1);
            $data = base64_decode($data);
            $imageName = 'avatar_' . time() . '_' . uniqid() . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/' . $imageName, $data);
            $validated['avatar'] = 'avatars/' . $imageName;
        }
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        
        $user->update($validated);
        
        // Load updated role info
        $user->role_info = \App\Models\Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        
        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        $authenticatedUser = Auth::user();
        if ($authenticatedUser && $user->id === $authenticatedUser->id) {
            return response()->json([
                'message' => 'Cannot delete your own account.',
            ], 422);
        }
        
        $user->delete();
        return response()->json(null, 204);
    }
}
