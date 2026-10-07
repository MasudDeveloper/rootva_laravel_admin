<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$proofsDir = public_path('uploads/proofs');
if (!is_dir($proofsDir)) {
    @mkdir($proofsDir, 0777, true);
}

$days = isset($argv[1]) ? intval($argv[1]) : 10;
$doDelete = in_array('--delete', $argv);

$cutoffTimestamp = time() - ($days * 86400);

echo "Cutoff Date: " . date('Y-m-d H:i:s', $cutoffTimestamp) . " ({$days} days ago)\n";
echo "Scanning directory: {$proofsDir} ...\n\n";

$files = scandir($proofsDir);
$totalFiles = 0;
$eligibleForDelete = 0;
$freedBytes = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
    
    $totalFiles++;
    $filePath = $proofsDir . '/' . $file;
    
    // Extract timestamp from filename prefix (e.g. 1790745509_filename.jpg)
    $fileTimestamp = null;
    if (preg_match('/^(\d{10})_/', $file, $m)) {
        $fileTimestamp = intval($m[1]);
    } else {
        // Fallback to file creation/modification time if filename has no timestamp prefix
        $fileTimestamp = filemtime($filePath);
    }
    
    if ($fileTimestamp < $cutoffTimestamp) {
        $eligibleForDelete++;
        $freedBytes += filesize($filePath);
        if ($doDelete) {
            @unlink($filePath);
        }
    }
}

$freedMB = round($freedBytes / (1024 * 1024), 2);

echo "Total Files Scanned: {$totalFiles}\n";
echo "Files older than {$days} days: {$eligibleForDelete}\n";
echo "Estimated Space to Free: {$freedMB} MB\n\n";

if ($doDelete) {
    echo "SUCCESS: Deleted {$eligibleForDelete} files older than {$days} days ({$freedMB} MB freed).\n";
} else {
    echo "DRY RUN MODE: No files were deleted.\n";
    echo "To actually delete these files, run:\n";
    echo "  php cleanup_proofs.php {$days} --delete\n";
}
