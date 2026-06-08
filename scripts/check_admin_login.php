<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$email = 'admin@smartschool.cd';
$user = User::where('email', $email)->first();

if (! $user) {
    echo "NO_USER\n";
    exit(1);
}

echo "user_id={$user->id} active=".($user->is_active ? '1' : '0')." role={$user->role}\n";
echo 'hash_check='.(Hash::check('password', $user->password) ? 'OK' : 'FAIL')."\n";
echo 'auth_attempt='.(Auth::attempt(['email' => $email, 'password' => 'password']) ? 'OK' : 'FAIL')."\n";
