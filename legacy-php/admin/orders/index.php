<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$error = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    $payment = (string) ($_POST['payment_status'] ?? '');
    $validStatus = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    $validPayment = ['unpaid', 'pending', 'paid', 'failed', 'refunded'];
    if (!$id || !in_array($status, $validStatus, true) || !in_array($payment, $validPayment, true)) {
        $error = 'Invalid order update.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT status,payment_status,email,order_number,customer_name FROM orders WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $orderData = $stmt->fetch();
            if (!$orderData) throw new RuntimeException('Order not found.');
            if ($status === 'cancelled' && $orderData['status'] !== 'cancelled') {
                $items = $pdo->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL');
                $items->execute([$id]);
                $restore = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
                foreach ($items->fetchAll() as $item) $restore->execute([(int) $item['quantity'], (int) $item['product_id']]);
            }
            if ($status !== 'cancelled' && $orderData['status'] === 'cancelled') {
                throw new RuntimeException('A cancelled order cannot be reopened; contact the customer to place a new order.');
            }
            if ($status === 'cancelled' && $orderData['payment_status'] === 'paid' && $payment !== 'refunded') {
                throw new RuntimeException('Mark the payment refunded before cancelling a paid order.');
            }
            $pdo->prepare('UPDATE orders SET status=?,payment_status=? WHERE id=?')->execute([$status, $payment, $id]);
            $paymentRowStatus = match ($payment) {
                'paid' => 'paid',
                'failed' => 'failed',
                'refunded' => 'refunded',
                default => 'pending',
            };
            $pdo->prepare('UPDATE payments SET status=? WHERE order_id=?')->execute([$paymentRowStatus, $id]);
            $pdo->commit();
            if ($orderData['status'] !== $status || $orderData['payment_status'] !== $payment) {
                send_business_email(
                    $orderData['email'],
                    'Yucca Fabrics order update ' . $orderData['order_number'],
                    "Hello {$orderData['customer_name']},\n\nYour order {$orderData['order_number']} is now marked {$status}. Payment status: {$payment}.\n\nYucca Fabrics"
                );
            }
            $notice = 'Order updated.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Order update failed.';
            error_log('Order update failed: ' . $exception->getMessage());
        }
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT * FROM orders';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE order_number LIKE ? OR customer_name LIKE ? OR email LIKE ?';
    array_push($params, "%{$search}%", "%{$search}%", "%{$search}%");
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
$adminTitle = 'Orders · Yucca Fabrics';
require __DIR__ . '/../../includes/admin-header.php';
?>
<h1 class="font-serif text-4xl">Orders</h1>
<?php if ($error || $notice): ?><p class="mt-4 rounded p-3 text-sm <?= $error ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' ?>"><?= e($error ?: $notice) ?></p><?php endif; ?>
<form class="mt-5 flex max-w-lg gap-2"><input class="form-control" type="search" name="q" value="<?= e($search) ?>" placeholder="Search order, customer or email"><button class="rounded-full bg-forest px-5 text-sm text-white">Search</button></form>
<section class="mt-6 space-y-3">
<?php foreach ($orders as $order): ?>
    <article class="grid gap-4 bg-white p-5 lg:grid-cols-[1fr_auto]">
        <div><p class="font-semibold"><?= e($order['order_number']) ?> · <?= e($order['customer_name']) ?></p><p class="mt-1 text-xs text-gray-500"><?= e($order['email']) ?> · <?= e($order['phone']) ?> · <?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></p><p class="mt-2 text-sm"><?= e($order['address_line']) ?>, <?= e($order['city']) ?> · <?= e(money($order['total'])) ?> · <?= e(str_replace('_', ' ', $order['payment_method'])) ?></p></div>
        <form method="post" class="flex flex-wrap items-end gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><label class="text-xs">Order status<select class="form-control mt-1 text-xs" name="status"><?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $value): ?><option value="<?= e($value) ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option><?php endforeach; ?></select></label><label class="text-xs">Payment<select class="form-control mt-1 text-xs" name="payment_status"><?php foreach (['unpaid','pending','paid','failed','refunded'] as $value): ?><option value="<?= e($value) ?>" <?= $order['payment_status'] === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option><?php endforeach; ?></select></label><button class="rounded-full bg-forest px-4 py-2 text-xs text-white">Update</button></form>
    </article>
<?php endforeach; ?>
</section>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
