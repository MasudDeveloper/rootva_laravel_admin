<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::connection()->getSchemaBuilder()->getTableListing();
foreach ($tables as $table) {
    if (preg_match('/sim|shop|course|offer|package|product|order/i', $table)) {
        echo $table . "\n";
    }
}
