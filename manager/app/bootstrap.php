<?php
declare(strict_types=1);

require_once __DIR__ . '/domain.php';

set_exception_handler(function (Throwable $error): void {
    error_log('VELORA manager: ' . $error->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Setup failed. Check the config, database and PHP error log.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VELORA — setup required</title><body><h1>VELORA manager is unavailable</h1><p>If this is a new installation, follow manager/README.md to configure MySQL, import the schema, and create the first admin.</p><p>If it worked before, contact the site administrator. Connection details are not displayed here.</p></body></html>';
});

function config(): array
{
    static $config;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config/local.php';
        if (!is_file($path)) throw new RuntimeException('Missing config/local.php');
        $config = require $path;
        if (!is_array($config)) throw new RuntimeException('Invalid configuration');
    }
    return $config;
}

function db(): PDO
{
    static $pdo;
    if ($pdo === null) {
        $c = config();
        $pdo = new PDO($c['dsn'], $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=UTF-8');
    header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('velora_manager');
    session_set_cookie_params([
        'httponly' => true, 'secure' => (bool) (config()['secure_cookies'] ?? false),
        'samesite' => 'Lax', 'path' => '/',
    ]);
    session_start();
    if (isset($_SESSION['admin_id']) && time() - (int) ($_SESSION['last_active'] ?? 0) > 1800) {
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        session_regenerate_id(true);
    }
    if (isset($_SESSION['admin_id'])) $_SESSION['last_active'] = time();
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    if (!hash_equals(csrf_token(), field($_POST, 'csrf'))) {
        http_response_code(403);
        exit('Form expired or invalid. Go back, refresh the page, and try again.');
    }
}

function require_admin(): void
{
    if (!isset($_SESSION['admin_id'])) redirect('login.php');
    $q = db()->prepare('SELECT id FROM admins WHERE id = ?');
    $q->execute([$_SESSION['admin_id']]);
    if (!$q->fetch()) {
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        redirect('login.php');
    }
}

function flash(string $message): void { $_SESSION['flash'] = $message; }

function rate_limit(string $scope, int $limit, int $seconds): bool
{
    // Hash the client IP; never trust a client-supplied forwarded IP header.
    $bucket = hash('sha256', $scope . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
    $now = time();
    $q = db()->prepare('INSERT INTO request_limits (bucket, attempts, window_start) VALUES (?, 1, ?)
        ON DUPLICATE KEY UPDATE attempts = IF(window_start <= ?, 1, attempts + 1),
        window_start = IF(window_start <= ?, ?, window_start)');
    $q->execute([$bucket, $now, $now - $seconds, $now - $seconds, $now]);
    $q = db()->prepare('SELECT attempts FROM request_limits WHERE bucket = ?');
    $q->execute([$bucket]);
    $allowed = (int) $q->fetchColumn() <= $limit;
    db()->prepare('DELETE FROM request_limits WHERE window_start < ?')->execute([$now - 86400]);
    return $allowed;
}

function find_car(int $id, bool $publishedOnly = false): array|false
{
    $q = db()->prepare('SELECT * FROM cars WHERE id = ?' . ($publishedOnly ? ' AND is_published = 1' : ''));
    $q->execute([$id]);
    return $q->fetch();
}

function page_header(string $title, bool $admin = false): void
{
    $notice = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#090b0e"><title><?= e($title) ?> — VELORA</title><link rel="stylesheet" href="assets/app.css"></head>
<body><a class="skip" href="#main">Skip to content</a>
<header class="topbar"><a class="brand" href="<?= $admin ? 'admin.php' : 'index.php' ?>">VELORA<span>.</span><small><?= $admin ? 'SHOWROOM MANAGER' : 'THE DIGITAL COLLECTION' ?></small></a>
<nav aria-label="Main navigation">
<?php if ($admin): ?><a href="admin.php">Overview</a><a href="admin.php?view=cars">Cars</a><a href="admin.php?view=enquiries">Enquiries</a><a href="index.php">View showroom ↗</a><form method="post" action="logout.php"><?= csrf_input() ?><button class="link-button">Sign out</button></form>
<?php else: ?><a href="index.php">Collection</a><a href="https://ahsanawan-dev.github.io/velora-car-showroom/">Original concept ↗</a><a href="login.php">Admin login</a><?php endif; ?>
</nav></header><main id="main" class="container">
<?php if ($notice): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif;
}

function page_footer(): void
{
    ?></main><footer class="footer"><span>VELORA / Independent portfolio concept</span><span>Built by Muhammad Ahsan Awan · PHP + MySQL</span><p>Demonstration vehicles only. No real bookings or sales. Third-party photographs; image permissions are not included.</p></footer></body></html><?php
}

function show_errors(array $errors): void
{
    if (!$errors) return;
    echo '<div class="errors" role="alert"><strong>Please check the form</strong><ul>';
    foreach ($errors as $error) echo '<li>' . e($error) . '</li>';
    echo '</ul></div>';
}
