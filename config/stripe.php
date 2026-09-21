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

$stripeConfig = [
    'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
    'currency' => 'usd',
];

$localConfigPath = __DIR__ . '/stripe.local.php';
if (file_exists($localConfigPath)) {
    $stripeConfig = array_merge($stripeConfig, require $localConfigPath);
}

return $stripeConfig;
