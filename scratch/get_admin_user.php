<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::first();
if ($user) {
    echo "Email: " . $user->email . "\n";
} else {
    echo "No user found\n";
}
