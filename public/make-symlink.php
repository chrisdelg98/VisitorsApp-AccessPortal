<?php
// ONE-TIME USE — delete this file immediately after running it.
if (php_sapi_name() === 'cli') exit('Use via browser only.' . PHP_EOL);

// Read API_PRIVATE_STORAGE_PATH from .env (no Laravel bootstrap)
$envPath  = dirname(__DIR__) . '/.env';
$apiRoot  = null;

foreach (file($envPath) as $line) {
    if (str_starts_with(trim($line), 'API_PRIVATE_STORAGE_PATH=')) {
        $apiRoot = trim(explode('=', $line, 2)[1]);
        break;
    }
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;line-height:1.8">';
echo '<strong>== Visitors Symlink Setup ==</strong>' . PHP_EOL . PHP_EOL;

if (!$apiRoot) {
    echo 'ERROR: API_PRIVATE_STORAGE_PATH not set in .env' . PHP_EOL;
    echo '</pre>'; exit;
}

// Source: the API's visitors folder  (what the symlink will POINT TO)
$source = rtrim($apiRoot, '/');
if (!str_ends_with($source, '/visitors')) {
    $source .= '/visitors';
}

// Target: the symlink location inside portal's public/storage
// public/storage is itself a symlink to storage/app/public, so this creates
// storage/app/public/visitors -> API visitors directory
$target = dirname(__DIR__) . '/storage/app/public/visitors';

echo 'Source (API visitors dir): ' . $source . PHP_EOL;
echo 'Target (portal symlink):   ' . $target . PHP_EOL . PHP_EOL;

// Check source exists
if (!file_exists($source)) {
    echo 'ERROR: Source path does not exist: ' . $source . PHP_EOL;
    echo 'Fix API_PRIVATE_STORAGE_PATH in .env first, then re-run.' . PHP_EOL;
    echo '</pre>'; exit;
}

// Check if target already exists
if (is_link($target)) {
    $existing = readlink($target);
    if ($existing === $source) {
        echo 'OK Symlink already exists and points to the correct location.' . PHP_EOL;
    } else {
        echo 'WARNING: Symlink exists but points to: ' . $existing . PHP_EOL;
        echo 'Removing old symlink...' . PHP_EOL;
        unlink($target);
        if (symlink($source, $target)) {
            echo 'OK Symlink updated to: ' . $source . PHP_EOL;
        } else {
            echo 'ERROR: Could not create symlink. Check permissions.' . PHP_EOL;
        }
    }
} elseif (file_exists($target)) {
    echo 'ERROR: Target path already exists as a real directory: ' . $target . PHP_EOL;
    echo 'Rename or remove it manually, then re-run.' . PHP_EOL;
} else {
    if (symlink($source, $target)) {
        echo 'OK Symlink created successfully!' . PHP_EOL;
        echo PHP_EOL . 'Images are now accessible at:' . PHP_EOL;
        echo '  https://accessportal.efltrackingsystem.com/storage/visitors/{uuid}/{file}.jpg' . PHP_EOL;
    } else {
        echo 'ERROR: symlink() failed.' . PHP_EOL;
        echo 'Your host may not allow symlinks across directories.' . PHP_EOL;
        echo PHP_EOL . 'Alternative — run this via SSH:' . PHP_EOL;
        echo '  ln -s ' . $source . ' ' . $target . PHP_EOL;
    }
}

echo PHP_EOL . '<strong>Done. DELETE this file immediately!</strong>';
echo '</pre>';
