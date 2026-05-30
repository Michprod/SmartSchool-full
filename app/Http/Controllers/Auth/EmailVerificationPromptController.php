<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\FrontendUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? FrontendUrl::redirect('dashboard')
            : FrontendUrl::redirect('verify-email');
    }
}
