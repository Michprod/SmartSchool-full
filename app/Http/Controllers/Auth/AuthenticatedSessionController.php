<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\FrontendUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Redirige vers le SPA (plus de vue login côté API).
     */
    public function create(): RedirectResponse
    {
        return FrontendUrl::redirect('login');
    }

    /**
     * Authentification — toujours JSON (API-only, consommée par le SPA).
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Déconnexion — toujours JSON.
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
