<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
verify_csrf();
$action = (string) ($_POST['action'] ?? '');
$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
if (!$productId || !in_array($action, ['add', 'set', 'remove'], true)) json_response(['error' => 'Invalid cart request.'], 422);
$stmt = db()->prepare('SELECT id, stock FROM products WHERE id=? AND is_active=1');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product && $action !== 'remove') json_response(['error' => 'That fabric is no longer available.'], 404);
$_SESSION['cart'] ??= [];
if ($action === 'remove') {
    unset($_SESSION['cart'][$productId]);
} else {
    $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
    if (!$quantity || $quantity < 1) json_response(['error' => 'Choose a valid quantity.'], 422);
    $requested = $action === 'add' ? (int) ($_SESSION['cart'][$productId] ?? 0) + $quantity : $quantity;
    if ($requested > (int) $product['stock']) json_response(['error' => 'There is not enough stock for that quantity.'], 409);
    $_SESSION['cart'][$productId] = $requested;
}
persist_cart();
json_response(['count' => cart_count(), 'message' => $action === 'remove' ? 'Removed from your bag.' : 'Added to your bag.']);
