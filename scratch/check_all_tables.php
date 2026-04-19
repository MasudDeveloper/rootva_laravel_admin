<?php
// Scratch script to check tables
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = [
    'sign_up', 'banners', 'social_links', 'app_updates', 'sim_offers', 'products', 
    'courses', 'services', 'microjobs', 'money_requests', 'withdraw_requests', 
    'verification_requests', 'referral_commissions', 'leadership_rewards', 
    'spin_history', 'daily_winners', 'math_games', 'popups'
];

foreach ($tables as $table) {
    if (Illuminate\Support\Facades\Schema::hasTable($table)) {
        echo "Table: $table [EXISTS]\n";
        $columns = Illuminate\Support\Facades\Schema::getColumnListing($table);
        echo "Columns: " . implode(', ', $columns) . "\n\n";
    } else {
        echo "Table: $table [MISSING]\n";
    }
}
