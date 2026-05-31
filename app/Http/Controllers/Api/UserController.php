<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Personnel;
use App\Models\RdcCity;
use App\Models\RdcProvince;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /** Roles creatable directly via Users module (no personnel record). */
    private const ACCOUNT_ONLY_ROLES = ['admin', 'parent', 'inventory_manager'];

    /** Fields a user may update on their own profile without users:write. */
    private const SELF_SERVICE_FIELDS = [
        'first_name', 'last_name', 'email', 'phone', 'avatar',
        'birth_date', 'address', 'city', 'province',
        'province_id', 'city_id', 'commune_id', 'quartier', 'bio',
    ];

    /** Fields restricted to users:write when editing another user. */
    private const ADMIN_ONLY_FIELDS = [
        'role', 'permissions', 'is_active',
    ];

    public function index(Request $request)
    {
        if (! $request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        $query = User::query()->with('personnel');

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->select([
            'id', 'first_name', 'last_name', 'email', 'role',
            'permissions', 'phone', 'avatar', 'is_active', 'last_login', 'created_at',
        ])->get();

        $users->transform(function ($user) {
            $role = Role::where('slug', $user->role)->first();
            $user->role_info = $role;
            $user->all_permissions = $user->getAllPermissions();
            $user->personnel_id = $user->personnel?->id;
            $user->personnel_summary = $user->personnel ? [
                'id' => $user->personnel->id,
                'staff_number' => $user->personnel->staff_number,
                'staff_type' => $user->personnel->staff_type,
                'full_name' => trim("{$user->personnel->first_name} {$user->personnel->last_name}"),
            ] : null;

            return $user;
        });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasPermission('users:write')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|exists:roles,slug',
            'permissions'=> 'nullable|array',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
            'is_active'  => 'sometimes|boolean',
        ]);

        if (! in_array($validated['role'], self::ACCOUNT_ONLY_ROLES, true)) {
            return response()->json([
                'message' => 'Pour créer un enseignant ou staff, utilisez le module Personnel.',
            ], 422);
        }

        if (! empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $validated['avatar'] = $this->storeAvatar($validated['avatar']);
        }
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->role_info = Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();

        return response()->json($user->makeHidden(['password', 'remember_token']), 201);
    }

    public function show(Request $request, string $id)
    {
        $user = User::with('personnel')->findOrFail($id);
        if ($request->user()->id !== $user->id && ! $request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        $user->role_info = Role::where('slug', $user->role)->first();
        $user->all_permissions = $user->getAllPermissions();
        $user->personnel_id = $user->personnel?->id;

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

        if (isset($validated['role']) && ! in_array($validated['role'], self::ACCOUNT_ONLY_ROLES, true) && ! $user->personnel) {
            return response()->json([
                'message' => 'Assignez un rôle staff via le module Personnel.',
            ], 422);
        }

        if (! empty($validated['avatar']) && preg_match('/^data:image\/(\w+);base64,/', $validated['avatar'])) {
            $validated['avatar'] = $this->storeAvatar($validated['avatar']);
        }

        $validated = $this->syncGeoLabels($validated);
        $user->update($validated);

        return new UserResource($user->fresh()->load('personnel'));
    }

    public function destroy(Request $request, string $id)
    {
        if (! $request->user()->hasPermission('users:write')) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }
        $user = User::findOrFail($id);

        if (Auth::user() && $user->id === Auth::user()->id) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }

        if ($user->personnel) {
            return response()->json([
                'message' => 'Ce compte est lié à une fiche personnel. Désactivez via le module Personnel.',
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
