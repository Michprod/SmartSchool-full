<?php

$base = 'http://127.0.0.1:8000';
$origin = 'http://127.0.0.1:5173';
$cookieFile = sys_get_temp_dir() . '/smartschool_login_cookies.txt';
@unlink($cookieFile);

function request(string $method, string $url, array $opts = []): array
{
    global $cookieFile, $origin;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => array_merge([
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'Origin: ' . $origin,
            'Referer: ' . $origin . '/login',
        ], $opts['headers'] ?? []),
        CURLOPT_POSTFIELDS => $opts['body'] ?? null,
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'raw' => $raw];
}

$csrf = request('GET', $base . '/sanctum/csrf-cookie');
preg_match('/XSRF-TOKEN=([^;]+)/', $csrf['raw'], $m);
$xsrf = isset($m[1]) ? urldecode($m[1]) : null;

$login = request('POST', $base . '/login', [
    'headers' => $xsrf ? ['X-XSRF-TOKEN: ' . $xsrf] : [],
    'body' => json_encode([
        'email' => 'admin@smartschool.cd',
        'password' => 'password',
    ]),
]);

$user = request('GET', $base . '/api/user', [
    'headers' => $xsrf ? ['X-XSRF-TOKEN: ' . $xsrf] : [],
]);

echo "csrf_status={$csrf['status']}\n";
echo 'xsrf_token=' . ($xsrf ? 'OK' : 'MISSING') . "\n";
echo "login_status={$login['status']}\n";
echo "user_status={$user['status']}\n";

if ($login['status'] !== 204 && $login['status'] !== 200) {
    $body = substr($login['raw'], strpos($login['raw'], "\r\n\r\n") + 4);
    echo "login_body={$body}\n";
}
