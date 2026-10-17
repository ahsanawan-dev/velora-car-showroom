<?php
declare(strict_types=1);

const CATEGORIES = ['hybrid' => 'Hybrid', 'performance' => 'Performance', 'touring' => 'Grand touring'];
const ENQUIRY_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'];
const CAR_IMAGES = [
    'revuelto' => ['Lamborghini photo', 'revuelto/revuelto-front.jpg'],
    'sf90' => ['Ferrari photo', 'sf90/sf90-front.jpg'],
    'gt3rs' => ['Porsche photo', 'gt3rs/gt3rs-front (1).jpg'],
    'mclaren750s' => ['McLaren photo', 'mclaren750s/750s-front.jpg'],
    'amggt' => ['Mercedes-AMG photo', 'amggt/amggt-front.jpg'],
];

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function field(array $input, string $key): string
{
    return isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
}

/** Byte limits intentionally match or stay below the database character limits. */
function validate_car(array $input): array
{
    $data = [
        'name' => field($input, 'name'), 'category' => field($input, 'category'),
        'model_year' => field($input, 'model_year'), 'price_usd' => field($input, 'price_usd'),
        'description' => field($input, 'description'), 'image_key' => field($input, 'image_key'),
        'is_published' => field($input, 'is_published') === '1' ? 1 : 0,
    ];
    $errors = [];
    if (strlen($data['name']) < 2 || strlen($data['name']) > 120) $errors[] = 'Car name must be 2–120 bytes.';
    if (!isset(CATEGORIES[$data['category']])) $errors[] = 'Choose a valid category.';
    if (!preg_match('/^\d{4}$/D', $data['model_year']) || (int) $data['model_year'] < 1950 || (int) $data['model_year'] > (int) date('Y') + 1) $errors[] = 'Enter a valid model year from 1950 to next year.';
    if ($data['price_usd'] !== '' && !preg_match('/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/D', $data['price_usd'])) $errors[] = 'Price must be a non-negative USD amount with up to two decimal places, or blank.';
    if (strlen($data['description']) < 10 || strlen($data['description']) > 3000) $errors[] = 'Description must be 10–3000 bytes.';
    if (!isset(CAR_IMAGES[$data['image_key']])) $errors[] = 'Choose one of the existing project photographs.';
    $data['price_usd'] = $data['price_usd'] === '' ? null : $data['price_usd'];
    return [$data, $errors];
}

function validate_enquiry(array $input): array
{
    $data = ['name' => field($input, 'name'), 'email' => field($input, 'email'), 'message' => field($input, 'message')];
    $errors = [];
    if (strlen($data['name']) < 2 || strlen($data['name']) > 100) $errors[] = 'Name must be 2–100 bytes.';
    if (strlen($data['email']) > 254 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($data['message']) < 10 || strlen($data['message']) > 3000) $errors[] = 'Message must be 10–3000 bytes.';
    return [$data, $errors];
}

function car_image(array $car): string
{
    $key = isset(CAR_IMAGES[$car['image_key']]) ? $car['image_key'] : 'revuelto';
    return 'photo.php?key=' . rawurlencode($key);
}

function price_label(mixed $price): string
{
    return $price === null || $price === '' ? 'Price on enquiry' : '$' . number_format((float) $price, 2) . ' USD';
}
