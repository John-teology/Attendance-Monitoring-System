<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::query()->orderBy('id')->first();
if (!$u) {
    echo "no users\n";
    exit(0);
}

$controller = app(App\Http\Controllers\Api\AttendanceController::class);

$reqRfid = Illuminate\Http\Request::create('/api/verify', 'POST', ['code' => $u->rfid_uid, 'entry_type' => 'rfid']);
$resRfid = $controller->verify($reqRfid)->getData(true);

$reqQr = Illuminate\Http\Request::create('/api/verify', 'POST', ['code' => $u->id_number, 'entry_type' => 'qr']);
$resQr = $controller->verify($reqQr)->getData(true);

echo "rfid_success=" . ($resRfid['success'] ? '1' : '0') . "\n";
echo "qr_success=" . ($resQr['success'] ? '1' : '0') . "\n";

