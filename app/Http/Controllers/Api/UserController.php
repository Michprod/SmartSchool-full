<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        return response()->json($query->select(['id','first_name','last_name','email','role','phone','avatar','is_active','created_at'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin,teacher,parent,student',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
        ]);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user->makeHidden(['password', 'remember_token']), 201);
    }

    public function show(string $id)
    {
        return response()->json(User::findOrFail($id)->makeHidden(['password', 'remember_token']));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'first_name' => 'string|max:255',
            'last_name'  => 'string|max:255',
            'email'      => 'email|unique:users,email,' . $id,
            'role'       => 'in:admin,teacher,parent,student',
            'phone'      => 'nullable|string|max:50',
            'avatar'     => 'nullable|string',
            'is_active'  => 'boolean',
            'password'   => 'nullable|string|min:8',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        $user->update($validated);
        return response()->json($user->makeHidden(['password', 'remember_token']));
    }

    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
