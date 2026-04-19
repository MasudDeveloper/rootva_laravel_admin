<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = collect(Illuminate\Support\Facades\Route::getRoutes())->filter(function ($route) {
    return str_starts_with($route->uri(), 'api/');
})->map(function ($route) {
    $action = $route->getActionName();
    $action = str_replace(['App\Http\Controllers\Api\\', 'App\Http\Controllers\Admin\\', 'App\Http\Controllers\\'], '', $action);
    $methodName = str_contains($action, '@') ? explode('@', $action)[1] : $action;
    return [
        'uri' => $route->uri(),
        'action' => $action,
        'method_name' => $methodName,
        'is_legacy' => str_ends_with($route->uri(), '.php'),
    ];
});

foreach ($routes->groupBy('method_name') as $methodName => $group) {
    echo "Method: $methodName | Count: " . $group->count() . "\n";
    foreach ($group as $r) {
        echo "  - URI: " . $r['uri'] . " | Legacy: " . ($r['is_legacy'] ? 'Y' : 'N') . "\n";
    }
}
