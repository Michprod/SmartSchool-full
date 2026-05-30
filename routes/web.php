<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web — session Sanctum uniquement (login/logout/register)
|--------------------------------------------------------------------------
| Aucune page UI : le frontend vit dans smartschool-web (port 5173).
*/

Route::get('/', function () {
    return response()->json([
        'service' => 'SmartSchool API',
        'frontend' => config('app.frontend_url'),
        'health' => url('/up'),
    ]);
});

require __DIR__.'/auth.php';
