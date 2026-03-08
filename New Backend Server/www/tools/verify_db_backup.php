<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(App\Http\Controllers\AdminSettingsController::class);
$response = $controller->downloadDatabaseBackup();

echo ($response->getStatusCode() ?? 200) . PHP_EOL;
echo ($response->headers->get('content-type') ?? '') . PHP_EOL;
echo ($response->headers->get('content-disposition') ?? '') . PHP_EOL;

