<?php
// ONE-TIME USE — delete this file after checking.
if (php_sapi_name() === 'cli') exit('Use via browser only.' . PHP_EOL);

// Read API_PRIVATE_STORAGE_PATH from .env manually (no Laravel bootstrap needed)
$envPath  = dirname(__DIR__) . '/.env';
$rootPath = null;

if (file_exists($envPath)) {
    foreach (file($envPath) as $line) {
        if (str_starts_with(trim($line), 'API_PRIVATE_STORAGE_PATH=')) {
            $rootPath = trim(explode('=', $line, 2)[1]);
            break;
        }
    }
}

function ok(string $msg): void  { echo '  <span style="color:#16a34a">&#10003; ' . htmlspecialchars($msg) . '</span>' . PHP_EOL; }
function err(string $msg): void { echo '  <span style="color:#dc2626">&#10007; ' . htmlspecialchars($msg) . '</span>' . PHP_EOL; }
function info(string $msg): void{ echo '  <span style="color:#2563eb">&#9432; '  . htmlspecialchars($msg) . '</span>' . PHP_EOL; }

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;font-size:13px;padding:20px;line-height:1.8">';
echo '<strong>== API Images Disk Checker ==</strong>' . PHP_EOL . PHP_EOL;

// 1. Show configured path
echo '<strong>1. API_PRIVATE_STORAGE_PATH in .env</strong>' . PHP_EOL;
if ($rootPath) {
    info('Value: ' . $rootPath);
} else {
    err('Variable not found in .env');
    echo '</pre>'; exit;
}

// 2. Check path exists
echo PHP_EOL . '<strong>2. Path exists on server</strong>' . PHP_EOL;
if (!file_exists($rootPath)) {
    err('Path does not exist: ' . $rootPath);
    info('Hint: SSH and run: ls ' . dirname($rootPath));
    echo '</pre>'; exit;
}
ok('Path exists');

// 3. Check readable
echo PHP_EOL . '<strong>3. Path is readable by web server</strong>' . PHP_EOL;
if (!is_readable($rootPath)) {
    err('Not readable — check permissions');
    info('Run: chmod -R 755 ' . $rootPath);
    echo '</pre>'; exit;
}
ok('Readable');

// 4. List first 5 subdirectories (visitor UUIDs)
echo PHP_EOL . '<strong>4. Subdirectory structure (first 5)</strong>' . PHP_EOL;
$dirs = array_slice(glob($rootPath . '/*', GLOB_ONLYDIR) ?: [], 0, 5);
if (empty($dirs)) {
    err('No subdirectories found — path may be wrong');
} else {
    foreach ($dirs as $dir) {
        ok(basename($dir) . '/');
    }
}

// 5. Peek inside first directory — show files
echo PHP_EOL . '<strong>5. Files inside first directory</strong>' . PHP_EOL;
if (!empty($dirs)) {
    $files = glob($dirs[0] . '/*') ?: [];
    if (empty($files)) {
        err('No files found inside ' . basename($dirs[0]));
    } else {
        foreach ($files as $file) {
            $relative = ltrim(str_replace($rootPath, '', $file), '/\\');
            ok($relative . '  (' . number_format(filesize($file)) . ' bytes)');
        }
        echo PHP_EOL;
        info('Sample file_path value to expect in DB: ' . ltrim(str_replace($rootPath, '', $files[0]), '/\\'));
    }
}

// 6. Summary
echo PHP_EOL . '<strong>6. Summary</strong>' . PHP_EOL;
ok('Disk root is reachable. Set API_PRIVATE_STORAGE_PATH to: ' . $rootPath);
info('The file_path stored in visit_images should be relative to this root.');
info('Example: if DB has "visitors/abc-123/photo.jpg", root must be the parent of "visitors/"');

echo PHP_EOL . '<strong>Done. DELETE this file immediately!</strong>';
echo '</pre>';
