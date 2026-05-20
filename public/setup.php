<?php
/**
 * EFL Access Portal — Setup & Health Check
 * Upload this file, open it in the browser ONCE, then DELETE it.
 */

$base  = dirname(__DIR__);
$isWin = DIRECTORY_SEPARATOR === '\\';

// Allow manual OS override via ?os=win or ?os=linux
$osOverride = $_GET['os'] ?? null;
if ($osOverride === 'win')   $isWin = true;
if ($osOverride === 'linux') $isWin = false;

// ── Helpers ──────────────────────────────────────────────────────────────────
function check(string $label, bool $pass, string $detail = '', array $stepsWin = [], array $stepsLinux = []): array {
    return compact('label', 'pass', 'detail', 'stepsWin', 'stepsLinux');
}

function readEnv(string $base): array {
    $env  = [];
    $path = $base . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($path)) return $env;
    foreach (file($path) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\"'");
        }
    }
    return $env;
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 1 — PHP & Extensions
// ════════════════════════════════════════════════════════════════════════════
$section1 = [];
$phpMin   = '8.2.0';
$phpVer   = PHP_VERSION;

$section1[] = check(
    "PHP version ($phpVer)",
    version_compare($phpVer, $phpMin, '>='),
    "Required minimum: $phpMin",
    [
        'Open IIS Manager',
        'Select the server node in the left panel',
        'Double-click "Handler Mappings"',
        'Find the PHP CGI handler → check the executable path (e.g. C:\\php\\8.4\\php-cgi.exe)',
        'If using PHP Manager for IIS: click "Change PHP Version" and select 8.2+',
        'Open Command Prompt as Administrator and run: iisreset',
    ],
    [
        'Check available versions: ls /usr/local/bin/php*',
        'In cPanel → go to "MultiPHP Manager" → set PHP 8.2+ for this domain',
        'Via SSH: sudo update-alternatives --config php',
        'Restart: sudo systemctl restart apache2   (or nginx)',
    ]
);

$exts = ['pdo','pdo_mysql','mbstring','tokenizer','xml','ctype','json','bcmath','openssl','intl','fileinfo','curl'];
foreach ($exts as $ext) {
    $ok = extension_loaded($ext);
    $section1[] = check(
        "ext-$ext",
        $ok,
        $ok ? 'Loaded' : 'Missing',
        $ok ? [] : [
            "Open C:\\php\\8.x\\php.ini in a text editor (run Notepad as Administrator)",
            "Search for the line: ;extension=$ext",
            "Remove the semicolon → it should read: extension=$ext",
            "Save the file",
            "Open Command Prompt as Administrator → run: iisreset",
            "Reload this page to verify",
        ],
        $ok ? [] : [
            "Via SSH: sudo apt install php$(php -r 'echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;')-$ext",
            "Or in cPanel → 'Select PHP Version' → 'PHP Extensions' → enable $ext",
            "Restart PHP-FPM: sudo systemctl restart php8.4-fpm",
            "Reload this page to verify",
        ]
    );
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 2 — .env File
// ════════════════════════════════════════════════════════════════════════════
$section2  = [];
$envPath   = $base . DIRECTORY_SEPARATOR . '.env';
$envExists = file_exists($envPath);

$section2[] = check(
    '.env file exists',
    $envExists,
    $envExists ? $envPath : 'Not found',
    $envExists ? [] : [
        'Open File Explorer and navigate to the project root',
        'Copy ".env.example" and rename the copy to ".env"',
        'Open ".env" in Notepad and fill in the DB credentials and APP_URL',
    ],
    $envExists ? [] : [
        'Via SSH in the project root: cp .env.example .env',
        'Edit with nano: nano .env',
        'Fill in DB credentials, APP_URL, and run php artisan key:generate',
    ]
);

$env = readEnv($base);
if ($envExists) {
    $appKey = $env['APP_KEY'] ?? '';
    $keyOk  = str_starts_with($appKey, 'base64:') && strlen($appKey) > 10;
    $section2[] = check(
        'APP_KEY is set',
        $keyOk,
        $keyOk ? 'OK' : 'Empty or invalid',
        $keyOk ? [] : [
            'Open Command Prompt or PowerShell in the project root',
            'Run: php artisan key:generate',
            'The key will be written to .env automatically',
            'If no terminal access: generate locally and paste the base64:... value into .env on the server',
        ],
        $keyOk ? [] : [
            'Via SSH in the project root: php artisan key:generate',
            'The command writes the key to .env automatically',
        ]
    );

    $appUrl = $env['APP_URL'] ?? '';
    $section2[] = check(
        'APP_URL is set',
        !empty($appUrl),
        $appUrl ?: 'Empty',
        empty($appUrl) ? ['Open .env → set APP_URL=https://accessportal.efltrackingsystem.com'] : [],
        empty($appUrl) ? ['Open .env → set APP_URL=https://accessportal.efltrackingsystem.com'] : []
    );

    $appDebug = $env['APP_DEBUG'] ?? 'true';
    $debugOff = strtolower($appDebug) !== 'true';
    $section2[] = check(
        'APP_DEBUG=false (production)',
        $debugOff,
        "APP_DEBUG=$appDebug",
        $debugOff ? [] : [
            'Open .env → change APP_DEBUG=true to APP_DEBUG=false',
            'Run: php artisan optimize:clear && php artisan optimize',
            'WARNING: leaving debug=true exposes server paths and credentials in error pages',
        ],
        $debugOff ? [] : [
            'Edit .env → change APP_DEBUG=true to APP_DEBUG=false',
            'Run: php artisan optimize:clear && php artisan optimize',
        ]
    );

    $session = $env['SESSION_DRIVER'] ?? '';
    $section2[] = check(
        'SESSION_DRIVER=file',
        $session === 'file',
        $session ?: 'Not set',
        $session === 'file' ? [] : ['Open .env → set SESSION_DRIVER=file'],
        $session === 'file' ? [] : ['Open .env → set SESSION_DRIVER=file']
    );

    $cache = $env['CACHE_STORE'] ?? '';
    $section2[] = check(
        'CACHE_STORE=file',
        $cache === 'file',
        $cache ?: 'Not set',
        $cache === 'file' ? [] : ['Open .env → set CACHE_STORE=file'],
        $cache === 'file' ? [] : ['Open .env → set CACHE_STORE=file']
    );
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 3 — Database
// ════════════════════════════════════════════════════════════════════════════
$section3 = [];

if ($envExists && isset($env['DB_HOST'], $env['DB_DATABASE'], $env['DB_USERNAME'])) {
    $host   = $env['DB_HOST']     ?? '127.0.0.1';
    $port   = $env['DB_PORT']     ?? '3306';
    $dbName = $env['DB_DATABASE'] ?? '';
    $user   = $env['DB_USERNAME'] ?? '';
    $pass   = $env['DB_PASSWORD'] ?? '';

    $dbStepsWin = [
        "Open .env and verify: DB_HOST=$host  DB_PORT=$port  DB_DATABASE=$dbName  DB_USERNAME=$user",
        "Make sure MySQL is running: open Services (services.msc) and check MySQL service",
        "If MySQL is remote, verify the server allows connections from this host",
        "Test in HeidiSQL or MySQL Workbench using the same credentials",
    ];
    $dbStepsLinux = [
        "Open .env and verify: DB_HOST=$host  DB_PORT=$port  DB_DATABASE=$dbName  DB_USERNAME=$user",
        "Test via SSH: mysql -h $host -P $port -u $user -p $dbName",
        "In cPanel: go to 'MySQL Databases' → confirm the user is added to the database",
        "Check MySQL is running: sudo systemctl status mysql",
    ];

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4",
            $user, $pass, [PDO::ATTR_TIMEOUT => 5]
        );
        $section3[] = check("MySQL connection ($host:$port / $dbName)", true, 'Connected successfully');

        $tables = ['users','countries','stations','visitors','visits','visit_images','station_device_logs'];
        foreach ($tables as $t) {
            $ok = (bool) $pdo->query("SHOW TABLES LIKE '$t'")->fetchColumn();
            $fix = $ok ? [] : [
                "Table '$t' should exist from the API project migrations",
                "Verify the API project is using the same database: $dbName",
                "Check API migration history: SELECT * FROM migrations ORDER BY id;",
            ];
            $section3[] = check("Table `$t`", $ok, $ok ? 'Exists' : 'Not found', $fix, $fix);
        }

        $cols     = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['role','country_id','is_active'] as $col) {
            $ok  = in_array($col, $cols);
            $fix = $ok ? [] : [
                "Column '$col' is added by the API migration",
                "From the API project run: php artisan migrate",
                "Check pending migrations: php artisan migrate:status",
            ];
            $section3[] = check("Column users.$col", $ok, $ok ? 'Exists' : 'Missing', $fix, $fix);
        }

        $roleRow  = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
        $roleType = $roleRow['Type'] ?? '';
        $hasEnums = str_contains($roleType, 'country_manager') && str_contains($roleType, 'viewer');
        $fix = $hasEnums ? [] : [
            "The API migration 'extend_role_enum_on_users_table' has not been run",
            "From the API project directory: php artisan migrate",
            "Verify: SHOW COLUMNS FROM users LIKE 'role';",
        ];
        $section3[] = check("role ENUM has 'country_manager' & 'viewer'", $hasEnums, $roleType, $fix, $fix);

        $hasSA = (bool) $pdo->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND is_active=1")->fetchColumn();
        $section3[] = check(
            'Active super_admin user exists',
            $hasSA,
            $hasSA ? 'Found' : 'Not found — cannot log in',
            $hasSA ? [] : [
                'Open phpMyAdmin → select database: ' . $dbName,
                'Go to the SQL tab',
                'Paste and run the contents of: database/sql/create_super_admin.sql',
                'Default credentials: admin@efltrackingsystem.com — change password after first login',
            ],
            $hasSA ? [] : [
                'Via SSH: mysql -u ' . $user . ' -p ' . $dbName . ' < database/sql/create_super_admin.sql',
                'Or open phpMyAdmin → select database → SQL tab → paste create_super_admin.sql',
                'Default credentials: admin@efltrackingsystem.com — change password after first login',
            ]
        );
    } catch (PDOException $e) {
        $section3[] = check("MySQL connection ($host:$port / $dbName)", false, $e->getMessage(), $dbStepsWin, $dbStepsLinux);
    }
} else {
    $fix = ['Complete the DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD values in .env'];
    $section3[] = check('DB configuration in .env', false, 'Missing DB_* variables', $fix, $fix);
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 4 — File System & Permissions
// ════════════════════════════════════════════════════════════════════════════
$section4 = [];
$dirs = [
    'storage/framework/sessions',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/logs',
    'storage/app/public',
    'bootstrap/cache',
];

foreach ($dirs as $rel) {
    $path    = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $existed = is_dir($path);
    $created = !$existed && mkdir($path, 0755, true);
    $writable = is_writable($path);

    if (!$existed && $created)       $detail = 'Created now — verify write permission';
    elseif (!$existed && !$created)  $detail = 'Does not exist and could not be created';
    elseif ($writable)               $detail = 'Exists and writable';
    else                             $detail = 'Exists but NOT writable';

    $stepsWin = $writable ? [] : [
        'Open IIS Manager',
        'Left panel → Application Pools → find the pool for this site',
        'Right-click the site → Manage Website → Advanced Settings → note "Application Pool"',
        'Note the pool Identity (e.g. ApplicationPoolIdentity or IIS AppPool\\YourPool)',
        'Open File Explorer → navigate to: ' . $path,
        'Right-click → Properties → Security tab → Edit → Add',
        'Enter the pool identity in the text box (e.g. IIS AppPool\\YourPool)',
        'Check "Allow: Modify" and "Allow: Write" → click OK → Apply',
        'Repeat for every directory marked NOT writable',
        'Reload this page to confirm',
    ];
    $stepsLinux = $writable ? [] : [
        'Via SSH in the project root, run:',
        '  chmod -R 775 storage bootstrap/cache',
        '  chown -R www-data:www-data storage bootstrap/cache',
        '(Replace www-data with your web server user if different)',
        'Find your web server user: ps aux | grep -E "apache|nginx|php-fpm" | head -1',
        'Reload this page to confirm',
    ];

    $section4[] = check($rel, $writable, $detail, $stepsWin, $stepsLinux);
}

// Storage symlink
$linkPath   = $base . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage';
$linkExists = is_link($linkPath) || is_dir($linkPath);
$section4[] = check(
    'public/storage symlink',
    $linkExists,
    $linkExists ? 'Exists' : 'Missing',
    $linkExists ? [] : [
        'Open Command Prompt or PowerShell as Administrator',
        'Navigate to the project root: cd C:\\path\\to\\VisitorsApp-AccessPortal',
        'Run: php artisan storage:link',
        'If that fails, create manually: mklink /D public\\storage storage\\app\\public',
        'Reload this page to verify',
    ],
    $linkExists ? [] : [
        'Via SSH in the project root: php artisan storage:link',
        'Or manually: ln -s ../storage/app/public public/storage',
        'Reload this page to verify',
    ]
);

// Optimized cache
$cacheFile = $base . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';
$cacheOk   = file_exists($cacheFile);
$fixCache  = [
    'In the project root terminal, run: php artisan optimize',
    'IMPORTANT: always run this ON the server — cache from your dev machine has wrong file paths',
    'If you get errors: first run php artisan optimize:clear  then php artisan optimize',
];
$section4[] = check(
    'Optimized cache exists',
    $cacheOk,
    $cacheOk ? 'Generated' : 'Missing — run php artisan optimize on the server',
    $fixCache, $fixCache
);

// ════════════════════════════════════════════════════════════════════════════
// SECTION 5 — Web Server
// ════════════════════════════════════════════════════════════════════════════
$section5 = [];
$section5[] = check('Detected OS', true, $isWin ? 'Windows / IIS' : 'Linux');

$docRoot        = $_SERVER['DOCUMENT_ROOT'] ?? '';
$docRootNorm    = rtrim(str_replace('\\', '/', $docRoot), '/');
$pointsToPublic = str_ends_with($docRootNorm, '/public');
$section5[] = check(
    'Document root → /public',
    $pointsToPublic,
    $docRoot ?: 'Not detected',
    $pointsToPublic ? [] : [
        'Open IIS Manager',
        'Left panel → Sites → select your site (e.g. accessportal)',
        'Right panel → click "Basic Settings..."',
        'Set Physical path to: C:\\path\\to\\VisitorsApp-AccessPortal\\public',
        '  (must end in \\public — not the project root)',
        'Click OK → reload this page',
    ],
    $pointsToPublic ? [] : [
        'In cPanel → Domains → find the domain → click "Document Root"',
        'Set to: /home/user/project/public   (must end in /public)',
        'Or edit Apache VirtualHost: DocumentRoot /home/user/project/public',
        'Restart Apache: sudo systemctl restart apache2',
    ]
);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || ($_SERVER['SERVER_PORT'] ?? 80) == 443
         || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$section5[] = check(
    'HTTPS active',
    $isHttps,
    $isHttps ? 'Yes' : 'No (HTTP only)',
    $isHttps ? [] : [
        'Open IIS Manager → Sites → select your site',
        'Right panel → click "Bindings..."',
        'Click Add → Type: https → Port: 443 → select your SSL certificate',
        'Click OK',
        'If you don\'t have a certificate: use IIS Crypto or Let\'s Encrypt (win-acme.com)',
    ],
    $isHttps ? [] : [
        'In cPanel → "SSL/TLS" → "Install and Manage SSL for your site"',
        'Or use Let\'s Encrypt: cPanel → "Let\'s Encrypt SSL" → Issue Certificate',
        'After installing, access via https:// and reload this page',
    ]
);

$hasWebConfig = file_exists($base . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'web.config');
$section5[] = check(
    'public/web.config present (IIS rewrite)',
    $hasWebConfig,
    $hasWebConfig ? 'Found' : 'Missing',
    $hasWebConfig ? [] : [
        'The file public/web.config is required for URL rewriting on IIS',
        'It should have been uploaded with the project — check the public/ folder',
        'Also verify the "URL Rewrite" IIS module is installed:',
        '  IIS Manager → server node → look for "URL Rewrite" icon',
        '  If missing: download from https://www.iis.net/downloads/microsoft/url-rewrite',
        '  Install it and restart IIS: iisreset',
    ],
    []
);

$hasHtaccess = file_exists($base . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . '.htaccess');
$section5[] = check(
    'public/.htaccess present (Apache rewrite)',
    $hasHtaccess,
    $hasHtaccess ? 'Found' : 'Missing',
    [],
    $hasHtaccess ? [] : [
        '.htaccess starts with a dot so it may have been excluded during upload (hidden file)',
        'In cPanel File Manager: click "Settings" → enable "Show Hidden Files"',
        'Upload .htaccess from your local public/ folder',
        'Also check mod_rewrite is enabled:',
        '  sudo a2enmod rewrite && sudo systemctl restart apache2',
    ]
);

// ════════════════════════════════════════════════════════════════════════════
// Build all sections
// ════════════════════════════════════════════════════════════════════════════
$allSections = [
    '1 &mdash; PHP &amp; Extensions' => $section1,
    '2 &mdash; .env File'            => $section2,
    '3 &mdash; Database'             => $section3,
    '4 &mdash; File System'          => $section4,
    '5 &mdash; Web Server'           => $section5,
];

$totalFail = array_sum(array_map(
    fn($s) => count(array_filter($s, fn($c) => !$c['pass'])),
    $allSections
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EFL Portal — Setup Check</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:2rem;line-height:1.5}
h1{color:#f97316;font-size:1.5rem;margin-bottom:.2rem}
.meta{color:#64748b;font-size:.82rem;margin-bottom:1.5rem}

/* OS Selector */
.os-bar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.75rem;
        background:#1e293b;padding:.6rem 1rem;border-radius:.5rem;flex-wrap:wrap}
.os-bar span{color:#94a3b8;font-size:.85rem;font-weight:600}
.os-btn{padding:.35rem .9rem;border-radius:.375rem;border:1px solid #334155;
        background:transparent;color:#94a3b8;font-size:.82rem;cursor:pointer;
        text-decoration:none;transition:all .15s}
.os-btn:hover{border-color:#f97316;color:#f97316}
.os-btn.active{background:#f97316;border-color:#f97316;color:#fff;font-weight:700}
.os-detected{font-size:.75rem;color:#64748b;margin-left:auto}

/* Summary */
.summary{display:flex;gap:.75rem;margin-bottom:1.75rem;flex-wrap:wrap;align-items:center}
.badge{padding:.4rem 1.1rem;border-radius:9999px;font-weight:700;font-size:.92rem}
.badge-ok{background:#14532d;color:#4ade80}
.badge-err{background:#7f1d1d;color:#fca5a5}

/* Next steps */
.next{background:#0c2340;border:1px solid #1e40af;border-radius:.5rem;
      padding:1rem 1.25rem;margin-bottom:2rem;color:#bfdbfe;font-size:.88rem}
.next h3{color:#60a5fa;margin-bottom:.5rem;font-size:.9rem}
.next ol{padding-left:1.25rem}
.next li{margin-bottom:.3rem}

/* Sections */
section{margin-bottom:2rem}
section h2{font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;
           letter-spacing:.08em;border-bottom:1px solid #1e293b;
           padding-bottom:.35rem;margin-bottom:.75rem}
table{width:100%;border-collapse:collapse;font-size:.855rem}
td,th{padding:.5rem .7rem;text-align:left;border-bottom:1px solid #1e293b;vertical-align:top}
th{color:#475569;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em}
.ok{color:#4ade80}
.err{color:#f87171}
.label{color:#cbd5e1}

/* Fix steps */
.fix-block{margin-top:.5rem;border-radius:0 .3rem .3rem 0}
.fix-block.win  {border-left:3px solid #3b82f6;background:#0d1b2e;padding:.6rem .85rem}
.fix-block.linux{border-left:3px solid #22c55e;background:#0d2010;padding:.6rem .85rem}
.fix-block p{font-size:.77rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem}
.fix-block.win   p{color:#60a5fa}
.fix-block.linux p{color:#4ade80}
.fix-block ol{font-size:.8rem;padding-left:1.2rem;color:#cbd5e1}
.fix-block li{margin-bottom:.2rem;line-height:1.45}

/* Hide/show logic */
.steps-win,.steps-linux{display:none}
body.show-win   .steps-win  {display:block}
body.show-linux .steps-linux{display:block}
body.show-both  .steps-win,
body.show-both  .steps-linux{display:block}

code{background:#1e293b;padding:.1rem .35rem;border-radius:.25rem;
     font-family:'Consolas',monospace;font-size:.82em;color:#f9a8d4}

.warn{background:#431407;border:1px solid #c2410c;border-radius:.5rem;
      padding:1rem 1.25rem;margin-top:2rem;color:#fed7aa;font-size:.86rem}
.warn strong{color:#fb923c}
</style>
</head>
<body class="show-<?= $isWin ? 'win' : 'linux' ?>">

<h1>&#9881; EFL Access Portal &mdash; Setup Health Check</h1>
<p class="meta">
    <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?> &nbsp;&middot;&nbsp;
    PHP <?= PHP_VERSION ?> &nbsp;&middot;&nbsp;
    <?= date('Y-m-d H:i:s') ?>
</p>

<!-- ── OS Selector ── -->
<div class="os-bar">
    <span>Show fix instructions for:</span>
    <button class="os-btn<?= $isWin ? ' active' : '' ?>" onclick="setOs('win')">&#129695; Windows / IIS</button>
    <button class="os-btn<?= !$isWin ? ' active' : '' ?>" onclick="setOs('linux')">&#127822; Linux / cPanel</button>
    <button class="os-btn" onclick="setOs('both')">&#128203; Both</button>
    <span class="os-detected">Auto-detected: <?= $isWin ? 'Windows' : 'Linux' ?></span>
</div>

<!-- ── Summary ── -->
<div class="summary">
<?php if ($totalFail === 0): ?>
    <span class="badge badge-ok">&#10003; All checks passed &mdash; ready to use</span>
<?php else: ?>
    <span class="badge badge-err">&#10007; <?= $totalFail ?> check<?= $totalFail > 1 ? 's' : '' ?> failed &mdash; see fix instructions below</span>
<?php endif; ?>
</div>

<?php if ($totalFail === 0): ?>
<div class="next">
    <h3>&#127881; Everything is OK! Next steps:</h3>
    <ol>
        <li>Run the super admin SQL if not done yet: open phpMyAdmin &rarr; select <code><?= htmlspecialchars($env['DB_DATABASE'] ?? 'servercpanel_privateapi') ?></code> &rarr; SQL tab &rarr; paste <code>database/sql/create_super_admin.sql</code></li>
        <li>Open the portal login: <a href="/login" style="color:#93c5fd"><?= ($isHttps?'https':'http').'://'.htmlspecialchars($_SERVER['HTTP_HOST']??'') ?>/login</a></li>
        <li>Log in with <code>admin@efltrackingsystem.com</code> and immediately change the password under <strong>Sistema &rarr; Usuarios</strong></li>
        <li><strong>Delete this file from the server:</strong> <code>public/setup.php</code></li>
    </ol>
</div>
<?php endif; ?>

<!-- ── Sections ── -->
<?php foreach ($allSections as $title => $checks): ?>
<section>
    <h2><?= $title ?></h2>
    <table>
        <thead>
            <tr>
                <th style="width:32%">Check</th>
                <th style="width:9%">Status</th>
                <th>Detail &amp; Fix Steps</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($checks as $c): ?>
            <tr>
                <td class="label"><?= htmlspecialchars($c['label']) ?></td>
                <td class="<?= $c['pass'] ? 'ok' : 'err' ?>"><?= $c['pass'] ? '&#10003; OK' : '&#10007; FAIL' ?></td>
                <td>
                    <span class="<?= $c['pass'] ? 'ok' : 'err' ?>"><?= htmlspecialchars($c['detail']) ?></span>

                    <?php if (!$c['pass'] && !empty($c['stepsWin'])): ?>
                    <div class="steps-win fix-block win">
                        <p>&#129695; Windows / IIS &mdash; How to fix:</p>
                        <ol><?php foreach ($c['stepsWin'] as $s): ?><li><?= htmlspecialchars($s) ?></li><?php endforeach; ?></ol>
                    </div>
                    <?php endif; ?>

                    <?php if (!$c['pass'] && !empty($c['stepsLinux'])): ?>
                    <div class="steps-linux fix-block linux">
                        <p>&#127822; Linux / cPanel &mdash; How to fix:</p>
                        <ol><?php foreach ($c['stepsLinux'] as $s): ?><li><?= htmlspecialchars($s) ?></li><?php endforeach; ?></ol>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endforeach; ?>

<div class="warn">
    <strong>&#9888; SECURITY WARNING:</strong> This file exposes server details.
    <strong>Delete it immediately after use.</strong><br><br>
    Linux: <code>rm public/setup.php</code> &nbsp;&nbsp;
    Windows: <code>del public\setup.php</code> &nbsp;&nbsp;
    cPanel File Manager: navigate to <code>public/</code> &rarr; delete <code>setup.php</code>
</div>

<script>
function setOs(os) {
    document.body.className = 'show-' + os;
    document.querySelectorAll('.os-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
</script>
</body>
</html>
