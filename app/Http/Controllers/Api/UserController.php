<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\RdcCity;
use App\Models\RdcCommune;
use App\Models\RdcProvince;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /** Fields a user may update on their own profile without users:write. */
    private const SELF_SERVICE_FIELDS = [
        'first_name', 'last_name', 'email', 'phone', 'avatar',
        'birth_date', 'address', 'city', 'province',
        'province_id', 'city_id', 'commune_id', 'quartier', 'bio',
    ];

    /** Fields restricted to users:write when editing another user. */
    private const ADMIN_ONLY_FIELDS = [
        'role', 'department', 'has_professional_profile', 'workload_hours',
        'job_grade', 'job_title', 'permissions', 'is_active',
    ];

    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }
        $query = User::query();
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $users = $query->select([
            'id', 'first_name', 'last_name', 'email', 'role', 'department',
            'permissions', 'phone', 'avatar', 'is_active', 'last_login', 'created_at',
            'has_professional_profile', 'workload_hours', 'job_grade', 'job_title'
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
        if (!$request->user()->hasPermission('users:write')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|exists:roles,slug',
            'department' => 'nullable|string|max:255',
            'has_professional_profile' => 'sometimes|boolean',
            'workload_hours' => 'nullable|integer|min:0|max:120',
            'job_grade' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:100',
            'permissions'=> 'nullable|array',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
        ]);
        
        if (!empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $validated['avatar'] = $this->storeAvatar($validated['avatar']);
        }
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        
        // Load role info
        $user->role_info = Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        
        return response()->json($user->makeHidden(['password', 'remember_token']), 201);
    }

    public function show(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && !$request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }
        $user->role_info = \App\Models\Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $authUser = $request->user();
        $isSelf = $authUser->id === $user->id;
        $canAdminEdit = $authUser->hasPermission('users:write');

        if (! $isSelf && ! $canAdminEdit) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        $rules = [
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $id,
            'role'       => 'sometimes|exists:roles,slug',
            'department' => 'nullable|string|max:255',
            'has_professional_profile' => 'sometimes|boolean',
            'workload_hours' => 'nullable|integer|min:0|max:120',
            'job_grade' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:100',
            'permissions'=> 'nullable|array',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
            'is_active'  => 'sometimes|boolean',
            'birth_date' => 'nullable|date',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'province'   => 'nullable|string|max:255',
            'province_id' => 'nullable|exists:rdc_provinces,id',
            'city_id' => 'nullable|exists:rdc_cities,id',
            'commune_id' => 'nullable|exists:rdc_communes,id',
            'quartier' => 'nullable|string|max:255',
            'bio'        => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        if ($isSelf && ! $canAdminEdit) {
            foreach (self::ADMIN_ONLY_FIELDS as $field) {
                if (array_key_exists($field, $validated)) {
                    return response()->json(['message' => "Le champ {$field} ne peut pas être modifié par vous-même."], 403);
                }
            }
        }

        if (! empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $validated['avatar'] = $this->storeAvatar($validated['avatar']);
        }

        $validated = $this->syncGeoLabels($validated);

        $user->update($validated);

        return new UserResource($user->fresh());
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermission('users:write')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function syncGeoLabels(array $validated): array
    {
        if (! empty($validated['province_id'])) {
            $validated['province'] = RdcProvince::find($validated['province_id'])?->name ?? ($validated['province'] ?? null);
        }
        if (! empty($validated['city_id'])) {
            $validated['city'] = RdcCity::find($validated['city_id'])?->name ?? ($validated['city'] ?? null);
        }

        return $validated;
    }

    private function storeAvatar(string $base64): string
    {
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);
        $imageName = 'avatar_' . time() . '_' . uniqid() . '.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/' . $imageName, $data);

        return 'avatars/' . $imageName;
    }
}
