<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cards = [
    [
        'full_name' => 'Test User',
        'id_number' => '0001',
        'info' => 'Info',
        'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode(
            SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(140)->margin(1)->generate('test')
        ),
    ],
];

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('admin.users.qr-pdf', ['cards' => $cards]);
$pdf->save(storage_path('app/tmp_test_qr.pdf'));

echo "ok\n";

