<?php

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = App\Models\User::count();
$first = App\Models\User::orderBy('id')->first();

echo "count={$count}\n";
if ($first) {
    echo "first_id={$first->id} id_number={$first->id_number} name={$first->full_name}\n";
} else {
    echo "first=null\n";
}

