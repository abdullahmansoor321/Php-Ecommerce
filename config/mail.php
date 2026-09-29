<?php

$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $envLine) {
        $envLine = trim($envLine);
        if ($envLine === '' || str_starts_with($envLine, '#') || !str_contains($envLine, '=')) {
            continue;
        }

        [$envName, $envValue] = explode('=', $envLine, 2);
        $envName = trim($envName);
        $envValue = trim($envValue);
        $envValue = trim($envValue, "\\\"'");
        if ($envName !== '' && getenv($envName) === false) {
            putenv($envName . '=' . $envValue);
        }
    }
}

$mailConfig = [
    'host'         => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'         => (int)(getenv('MAIL_PORT') ?: 587),
    'username'     => getenv('MAIL_USERNAME') ?: '',
    'password'     => getenv('MAIL_PASSWORD') ?: '',
    'encryption'   => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: '',
    'from_name'    => getenv('MAIL_FROM_NAME') ?: 'Molla',
];

$localConfigPath = __DIR__ . '/mail.local.php';
if (file_exists($localConfigPath)) {
    $mailConfig = array_merge($mailConfig, require $localConfigPath);
}

return $mailConfig;