<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = '99990001';

App\Models\User::where('id_number', $id)->delete();
App\Models\User::where('qr_code', $id)->delete();
App\Models\User::where('rfid_uid', $id)->delete();

$u = App\Models\User::create([
    'user_type' => 'student',
    'full_name' => 'Test Default',
    'id_number' => $id,
    'qr_code' => $id,
    'rfid_uid' => $id,
    'status' => 'active',
]);

echo "id_number={$u->id_number} qr_code={$u->qr_code} rfid_uid={$u->rfid_uid}\n";

$u->delete();

