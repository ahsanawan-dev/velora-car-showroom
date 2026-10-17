<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
if (isset($_SESSION['admin_id'])) redirect('admin.php');
$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = field($_POST, 'username');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    if (!rate_limit('login', 5, 900)) {
        http_response_code(429);
        $errors[] = 'Too many login attempts. Try again in 15 minutes.';
    } else {
        $q = db()->prepare('SELECT * FROM admins WHERE username = ?');
        $q->execute([substr($username, 0, 80)]);
        $admin = $q->fetch();
        // Always run a password comparison, including unknown usernames.
        $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $hash);
        if ($admin && $valid && strlen($username) <= 80 && strlen($password) <= 72) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['last_active'] = time();
            unset($_SESSION['csrf']);
            redirect('admin.php');
        }
        $errors[] = 'Username or password is incorrect.';
    }
}
page_header('Admin login');
?>
<section class="auth-panel panel"><p class="eyebrow">PRIVATE WORKSPACE</p><h1>Welcome back.</h1><p class="muted">Manage the collection and follow up on enquiries.</p>
<?php show_errors($errors); ?>
<form method="post" class="form-stack"><?= csrf_input() ?>
<label>Username<input name="username" value="<?= e($username) ?>" maxlength="80" autocomplete="username" required></label>
<label>Password<input name="password" type="password" maxlength="72" autocomplete="current-password" required></label>
<button class="button">Sign in →</button></form><p class="help">First installation? Create your admin from the command line using the setup guide. There is no default password.</p></section>
<?php page_footer(); ?>
