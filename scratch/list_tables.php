<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
foreach ($tables as $table) {
    foreach ($table as $key => $val) {
        echo $val . "\n";
    }
}
