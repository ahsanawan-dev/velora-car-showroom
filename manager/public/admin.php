<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$view = field($_GET, 'view') ?: 'overview';
$allowed = ['overview', 'cars', 'edit', 'delete', 'enquiries', 'delete-enquiry'];
if (!in_array($view, $allowed, true)) { http_response_code(404); exit('Page not found.'); }
$id = (int) (filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0);
$errors = [];
$car = ['name' => '', 'category' => 'hybrid', 'model_year' => date('Y'), 'price_usd' => '', 'description' => '', 'image_key' => 'revuelto', 'is_published' => 0];
if (in_array($view, ['edit', 'delete'], true) && $id) {
    $car = find_car($id);
    if (!$car) { http_response_code(404); exit('Car not found.'); }
}
if ($view === 'delete' && !$id) { http_response_code(404); exit('Car not found.'); }
if ($view === 'delete-enquiry') {
    $q = db()->prepare('SELECT * FROM enquiries WHERE id = ?');
    $q->execute([$id]);
    $deleteEnquiry = $q->fetch();
    if (!$deleteEnquiry) { http_response_code(404); exit('Enquiry not found.'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = field($_POST, 'action');
    if ($action === 'save_car' && $view === 'edit') {
        [$car, $errors] = validate_car($_POST);
        if (!$errors) {
            $values = array_values($car);
            if ($id) {
                $values[] = $id;
                $q = db()->prepare('UPDATE cars SET name=?, category=?, model_year=?, price_usd=?, description=?, image_key=?, is_published=? WHERE id=?');
            } else {
                $q = db()->prepare('INSERT INTO cars (name, category, model_year, price_usd, description, image_key, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)');
            }
            $q->execute($values);
            flash($id ? 'Car updated.' : 'Car added.');
            redirect('admin.php?view=cars');
        }
    } elseif ($action === 'delete_car' && $view === 'delete' && $id && field($_POST, 'confirm') === 'yes') {
        db()->prepare('DELETE FROM cars WHERE id = ?')->execute([$id]);
        flash('Car deleted. Existing enquiries keep the vehicle name.');
        redirect('admin.php?view=cars');
    } elseif ($action === 'set_status' && $view === 'enquiries') {
        $enquiryId = (int) (filter_var($_POST['enquiry_id'] ?? null, FILTER_VALIDATE_INT) ?: 0);
        $status = field($_POST, 'status');
        if (!$enquiryId || !isset(ENQUIRY_STATUSES[$status])) $errors[] = 'Choose a valid enquiry and status.';
        else {
            $q = db()->prepare('SELECT id FROM enquiries WHERE id = ?');
            $q->execute([$enquiryId]);
            if (!$q->fetch()) { http_response_code(404); exit('Enquiry not found.'); }
            db()->prepare('UPDATE enquiries SET status = ? WHERE id = ?')->execute([$status, $enquiryId]);
            flash('Enquiry status updated.');
            redirect('admin.php?view=enquiries');
        }
    } elseif ($action === 'delete_enquiry' && $view === 'delete-enquiry' && $id && field($_POST, 'confirm') === 'yes') {
        $q = db()->prepare('DELETE FROM enquiries WHERE id = ?');
        $q->execute([$id]);
        flash('Enquiry deleted.');
        redirect('admin.php?view=enquiries');
    } else {
        http_response_code(400);
        $errors[] = 'Invalid action. Nothing was changed.';
    }
    if ($errors && http_response_code() === 200) http_response_code(422);
}

$titles = ['overview' => 'Overview', 'cars' => 'Manage cars', 'edit' => $id ? 'Edit car' : 'Add car', 'delete' => 'Delete car', 'enquiries' => 'Enquiries', 'delete-enquiry' => 'Delete enquiry'];
page_header($titles[$view], true);
?>
<section class="section-top intro compact"><div><p class="eyebrow">YOUR SHOWROOM / <?= e($_SESSION['admin_username']) ?></p><h1><?= e($titles[$view]) ?><span>.</span></h1></div><?php if (in_array($view, ['overview', 'cars'], true)): ?><a class="button" href="admin.php?view=edit">+ Add a car</a><?php endif; ?></section>
<?php show_errors($errors); ?>
<?php if ($view === 'overview'):
    $total = (int) db()->query('SELECT COUNT(*) FROM cars')->fetchColumn();
    $published = (int) db()->query('SELECT COUNT(*) FROM cars WHERE is_published = 1')->fetchColumn();
    $new = (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
    $recent = db()->query('SELECT * FROM enquiries ORDER BY id DESC LIMIT 5')->fetchAll();
?>
<div class="stats"><a class="panel stat" href="admin.php?view=cars"><span>Total cars</span><strong><?= $total ?></strong><small>Drafts and published entries</small></a><a class="panel stat" href="index.php"><span>Published</span><strong><?= $published ?></strong><small>Visible in the public collection</small></a><a class="panel stat" href="admin.php?view=enquiries"><span>New enquiries</span><strong><?= $new ?></strong><small>Ready for a follow-up</small></a></div>
<div class="section-top"><h2>Recent enquiries</h2><a class="text-link" href="admin.php?view=enquiries">View inbox ↗</a></div>
<?php if (!$recent): ?><div class="empty"><h3>Your inbox starts here.</h3><p>Publish a car, open the showroom, and send an enquiry using fictional test details.</p><a class="button secondary" href="admin.php?view=cars">Review draft cars</a></div><?php else: ?><div class="panel recent-list"><?php foreach ($recent as $item): ?><article><div><strong><?= e($item['car_name']) ?></strong><p class="muted"><?= e($item['name']) ?> · <?= e($item['created_at']) ?> UTC</p></div><span class="badge"><?= e(ENQUIRY_STATUSES[$item['status']] ?? $item['status']) ?></span></article><?php endforeach; ?></div><?php endif; ?>
<p class="help">First release: PHP sessions, MySQL storage, prepared queries, CSRF-protected forms, and server-side validation. Demo data is not real showroom inventory.</p>
<?php elseif ($view === 'cars'):
    $page = max(1, (int) (filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1));
    $total = (int) db()->query('SELECT COUNT(*) FROM cars')->fetchColumn();
    $pages = max(1, (int) ceil($total / 20));
    $page = min($page, $pages);
    $cars = db()->query('SELECT * FROM cars ORDER BY id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20))->fetchAll();
?>
<p class="muted">Review each vehicle, then publish it to make it visible in the database-backed showroom.</p>
<div class="car-grid admin-grid"><?php foreach ($cars as $item): ?><article class="car-card"><img src="<?= e(car_image($item)) ?>" alt="<?= e($item['name']) ?> — project photograph" width="800" height="500" loading="lazy"><div class="car-copy"><div class="section-top"><p class="eyebrow"><?= e(CATEGORIES[$item['category']] ?? $item['category']) ?></p><span class="badge <?= $item['is_published'] ? 'published' : '' ?>"><?= $item['is_published'] ? 'Published' : 'Draft' ?></span></div><h2><?= e($item['name']) ?></h2><p class="muted"><?= e($item['model_year']) ?> · <?= e(price_label($item['price_usd'])) ?></p><div class="actions"><a class="button secondary" href="admin.php?view=edit&id=<?= e($item['id']) ?>">Edit car</a><a class="text-link danger-text" href="admin.php?view=delete&id=<?= e($item['id']) ?>">Delete</a></div></div></article><?php endforeach; ?></div>
<?php if (!$cars): ?><div class="empty"><h2>No cars yet.</h2><a class="button" href="admin.php?view=edit">Add your first car</a></div><?php endif; ?>
<nav class="pagination" aria-label="Car pages"><?php if ($page > 1): ?><a href="?view=cars&page=<?= $page - 1 ?>">← Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if ($page < $pages): ?><a href="?view=cars&page=<?= $page + 1 ?>">Next →</a><?php endif; ?></nav>
<?php elseif ($view === 'edit'): ?>
<div class="editor-grid"><form method="post" class="panel form-stack"><?= csrf_input() ?><input type="hidden" name="action" value="save_car">
<label>Car name<input name="name" value="<?= e($car['name']) ?>" maxlength="120" required></label>
<div class="field-row"><label>Category<select name="category"><?php foreach (CATEGORIES as $key => $label): ?><option value="<?= e($key) ?>" <?= $car['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Model year<input type="number" name="model_year" min="1950" max="<?= (int) date('Y') + 1 ?>" value="<?= e($car['model_year']) ?>" required></label></div>
<label>Display price (USD)<input type="number" name="price_usd" min="0" max="9999999999.99" step="0.01" value="<?= e($car['price_usd']) ?>"><small>Leave blank for “Price on enquiry”. Demonstration prices only.</small></label>
<label>Description<textarea name="description" rows="6" minlength="10" maxlength="3000" required><?= e($car['description']) ?></textarea></label>
<label>Project photograph<select name="image_key"><?php foreach (CAR_IMAGES as $key => $image): ?><option value="<?= e($key) ?>" <?= $car['image_key'] === $key ? 'selected' : '' ?>><?= e($image[0]) ?></option><?php endforeach; ?></select><small>Uses the five existing project photos. Select a photo that matches your entry. No upload is enabled.</small></label>
<label class="check"><input type="checkbox" name="is_published" value="1" <?= $car['is_published'] ? 'checked' : '' ?>> Publish in the database-backed showroom</label>
<div class="actions"><button class="button"><?= $id ? 'Save changes' : 'Create car' ?> →</button><a href="admin.php?view=cars">Cancel</a></div></form>
<aside class="panel editor-note"><p class="eyebrow">EDITOR'S NOTE</p><h2>Make every entry clear.</h2><p class="muted">Use a specific model name, choose the correct category, and describe the car without unsupported claims.</p><hr><p class="help">Uncheck Publish to keep an entry as a draft. Existing enquiries are kept when a car is removed.</p><p class="help">These records power manager/public/index.php. The original static GitHub Pages site does not read this database.</p></aside></div>
<?php elseif ($view === 'delete'): ?>
<section class="panel confirm-panel"><p class="eyebrow">PLEASE REVIEW</p><h2>Delete <?= e($car['name']) ?>?</h2><p>This removes the car record. Existing enquiries keep its name. This action cannot be undone from the admin panel.</p><form method="post" class="actions"><?= csrf_input() ?><input type="hidden" name="action" value="delete_car"><input type="hidden" name="confirm" value="yes"><button class="button danger">Delete this car</button><a href="admin.php?view=cars">Keep car</a></form></section>
<?php elseif ($view === 'enquiries'):
    $status = field($_GET, 'status');
    if (!isset(ENQUIRY_STATUSES[$status])) $status = '';
    $where = $status ? ' WHERE status = ?' : '';
    $q = db()->prepare('SELECT COUNT(*) FROM enquiries' . $where);
    $q->execute($status ? [$status] : []);
    $total = (int) $q->fetchColumn();
    $pages = max(1, (int) ceil($total / 20));
    $page = min($pages, max(1, (int) (filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1)));
    $q = db()->prepare('SELECT * FROM enquiries' . $where . ' ORDER BY id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
    $q->execute($status ? [$status] : []);
    $items = $q->fetchAll();
?>
<nav class="filters" aria-label="Enquiry status"><a class="<?= $status === '' ? 'active' : '' ?>" href="?view=enquiries">All enquiries</a><?php foreach (ENQUIRY_STATUSES as $key => $label): ?><a class="<?= $status === $key ? 'active' : '' ?>" href="?view=enquiries&status=<?= e($key) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<div class="inbox"><?php foreach ($items as $item): ?><article class="panel enquiry"><div class="section-top"><div><p class="eyebrow">ENQUIRY #<?= e($item['id']) ?> / <?= e($item['created_at']) ?> UTC</p><h2><?= e($item['car_name']) ?></h2></div><span class="badge"><?= e(ENQUIRY_STATUSES[$item['status']] ?? $item['status']) ?></span></div><p><strong><?= e($item['name']) ?></strong> · <?= e($item['email']) ?></p><p class="message"><?= nl2br(e($item['message'])) ?></p><div class="section-top"><form method="post" class="actions"><?= csrf_input() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="enquiry_id" value="<?= e($item['id']) ?>"><label class="sr-only" for="status-<?= e($item['id']) ?>">Status for enquiry <?= e($item['id']) ?></label><select name="status" id="status-<?= e($item['id']) ?>"><?php foreach (ENQUIRY_STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= $item['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="button secondary">Update status</button></form><a class="text-link danger-text" href="?view=delete-enquiry&id=<?= e($item['id']) ?>">Delete enquiry</a></div></article><?php endforeach; ?></div>
<?php if (!$items): ?><div class="empty"><h2>No enquiries here yet.</h2><p>New enquiries appear after visitors submit the demo form.</p></div><?php endif; ?>
<nav class="pagination" aria-label="Enquiry pages"><?php if ($page > 1): ?><a href="?view=enquiries&status=<?= e($status) ?>&page=<?= $page - 1 ?>">← Previous</a><?php endif; ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if ($page < $pages): ?><a href="?view=enquiries&status=<?= e($status) ?>&page=<?= $page + 1 ?>">Next →</a><?php endif; ?></nav>
<?php elseif ($view === 'delete-enquiry'): $item = $deleteEnquiry; ?>
<section class="panel confirm-panel"><p class="eyebrow">PLEASE REVIEW</p><h2>Delete enquiry #<?= e($item['id']) ?>?</h2><p>This permanently removes <?= e($item['name']) ?>'s name, email and message from this enquiry record. It cannot be restored from the admin panel.</p><form method="post" class="actions"><?= csrf_input() ?><input type="hidden" name="action" value="delete_enquiry"><input type="hidden" name="confirm" value="yes"><button class="button danger">Delete this enquiry</button><a href="admin.php?view=enquiries">Keep enquiry</a></form></section>
<?php endif; ?>
<?php page_footer(); ?>
