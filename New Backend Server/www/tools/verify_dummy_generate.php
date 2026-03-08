<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(App\Http\Controllers\AdminSettingsController::class);
$ref = new ReflectionClass($controller);
$m = $ref->getMethod('generateDummyUsers');
$m->setAccessible(true);
$created = $m->invoke($controller, 3);

echo "created={$created}\n";

