<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$category = field($_GET, 'category');
if (!isset(CATEGORIES[$category])) $category = '';
$q = db()->prepare('SELECT * FROM cars WHERE is_published = 1' . ($category ? ' AND category = ?' : '') . ' ORDER BY id DESC');
$q->execute($category ? [$category] : []);
$cars = $q->fetchAll();
$errors = [];
$form = ['name' => '', 'email' => '', 'message' => ''];
$carId = filter_var($_GET['car'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$selected = $carId ? find_car((int) $carId, true) : false;
if ($carId && !$selected) http_response_code(404);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    [$form, $errors] = validate_enquiry($_POST);
    $carId = filter_var($_POST['car_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $selected = $carId ? find_car((int) $carId, true) : false;
    if (!$selected) $errors[] = 'This car is unavailable. Return to the collection and choose a published car.';
    if (!$errors && !rate_limit('enquiry', 5, 900)) { http_response_code(429); $errors[] = 'Too many enquiries. Please try again in 15 minutes.'; }
    if (!$errors) {
        $q = db()->prepare('INSERT INTO enquiries (car_id, car_name, name, email, message) VALUES (?, ?, ?, ?, ?)');
        $q->execute([$selected['id'], $selected['name'], $form['name'], $form['email'], $form['message']]);
        unset($_SESSION['csrf']);
        flash('Your demo enquiry has been saved. No email was sent and no booking was made.');
        redirect('index.php');
    }
    if (http_response_code() === 200) http_response_code(422);
}
page_header($selected ? $selected['name'] : 'The collection');
?>
<section class="intro"><p class="eyebrow">VELORA / LIVE DATABASE COLLECTION</p><h1>Exceptional cars.<br><span>A considered collection.</span></h1><p class="muted">A PHP and MySQL portfolio project. Browse published demonstration entries and explore the enquiry workflow.</p></section>
<?php if ($selected || $errors): ?>
<section class="detail-grid" id="enquiry">
<?php if ($selected): ?><div><img class="detail-image" src="<?= e(car_image($selected)) ?>" alt="<?= e($selected['name']) ?> — project photograph"><p class="eyebrow"><?= e(CATEGORIES[$selected['category']] ?? $selected['category']) ?> / <?= e($selected['model_year']) ?></p><h2><?= e($selected['name']) ?></h2><p><?= nl2br(e($selected['description'])) ?></p><strong class="price"><?= e(price_label($selected['price_usd'])) ?></strong></div><?php endif; ?>
<div class="panel"><p class="eyebrow">START A CONVERSATION</p><h2>Send a demo enquiry</h2><p class="help">This form stores your name, email and message in this project's database for the administrator to review. Use fictional details while testing. No email is sent.</p>
<?php show_errors($errors); ?>
<form method="post" action="index.php" class="form-stack"><?= csrf_input() ?><input type="hidden" name="car_id" value="<?= e($carId) ?>">
<label>Name<input name="name" value="<?= e($form['name']) ?>" maxlength="100" autocomplete="name" required></label>
<label>Email<input type="email" name="email" value="<?= e($form['email']) ?>" maxlength="254" autocomplete="email" required></label>
<label>Message<textarea name="message" rows="5" minlength="10" maxlength="3000" required><?= e($form['message']) ?></textarea></label>
<button class="button">Save enquiry →</button></form></div></section>
<?php elseif ($carId): ?><div class="empty"><h2>Car unavailable</h2><p>This entry may be a draft or may have been removed.</p><a class="button secondary" href="index.php">Return to collection</a></div><?php endif; ?>
<section aria-labelledby="collection-title"><div class="section-top"><h2 id="collection-title">The collection</h2><span class="muted"><?= count($cars) ?> published <?= count($cars) === 1 ? 'car' : 'cars' ?></span></div>
<nav class="filters" aria-label="Filter collection"><a class="<?= $category === '' ? 'active' : '' ?>" href="index.php">All cars</a><?php foreach (CATEGORIES as $key => $label): ?><a class="<?= $category === $key ? 'active' : '' ?>" href="?category=<?= e($key) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<div class="car-grid"><?php foreach ($cars as $car): ?><article class="car-card"><a href="?car=<?= e($car['id']) ?>#enquiry"><img src="<?= e(car_image($car)) ?>" alt="<?= e($car['name']) ?> — project photograph" width="800" height="500" loading="lazy"></a><div class="car-copy"><p class="eyebrow"><?= e(CATEGORIES[$car['category']] ?? $car['category']) ?> / <?= e($car['model_year']) ?></p><h3><?= e($car['name']) ?></h3><p class="price"><?= e(price_label($car['price_usd'])) ?></p><a class="text-link" href="?car=<?= e($car['id']) ?>#enquiry">View car & enquire ↗</a></div></article><?php endforeach; ?></div>
<?php if (!$cars): ?><div class="empty"><h3>No published cars in this collection yet.</h3><p>The administrator can review a draft and publish it from the Cars page.</p></div><?php endif; ?></section>
<?php page_footer(); ?>
