<?php
    session_start();
    require './config/db.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    // Load cart and compute totals
    $items = [];
    $totals = [ 'subtotal' => 0.0, 'count' => 0 ];
    try {
        $stmt = $con->prepare("SELECT c.product_id, c.quantity, p.name, p.price
                               FROM cart c JOIN products p ON c.product_id = p.id
                               WHERE c.user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $row) {
            $totals['subtotal'] += ((float)$row['price']) * (int)$row['quantity'];
            $totals['count'] += (int)$row['quantity'];
        }
    } catch (Exception $e) {
        $items = [];
    }

    if (empty($items)) {
        header('Location: ./cart.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; }
        header, footer { background: #2563eb; color: #fff; padding: 12px 16px; }
        nav a { color: #fff; margin-right: 10px; text-decoration: none; }
        main { max-width: 1000px; margin: 16px auto; padding: 0 12px; }
        .grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px; }
        .card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
        .right { text-align: right; }
        button { background: #2563eb; color: #fff; padding: 8px 10px; border: none; border-radius: 6px; cursor: pointer; }
        input, textarea, select { width: 100%; padding: 8px; margin: 6px 0; }
    </style>
</head>
<body>
    <header>
        <strong>Checkout</strong>
        <nav style="float:right;">
            <a href="./index.php">Home</a>
            <a href="./shop.php">Shop</a>
            <a href="./cart.php">Cart</a>
            <a href="./orders.php">My Orders</a>
            <a href="./index.php">Dashboard</a>
            <a href="./logout.php?logout=true">Logout</a>
        </nav>
        <div style="clear:both"></div>
    </header>

    <main>
        <div class="grid">
            <div class="card">
                <h3>Shipping Information</h3>
                <form action="./place_order.php" method="POST">
                    <label>Shipping Address</label>
                    <textarea name="shipping_address" rows="4" required></textarea>
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="cod">Cash on Delivery</option>
                        <option value="paymongo">PayMongo</option>
                    </select>
                    <input type="hidden" name="csrf" value="<?php echo md5(session_id().'checkout'); ?>">
                    <button type="submit">Place Order</button>
                    <a href="./cart.php" style="margin-left:8px;" class="button">Back to Cart</a>
                </form>
            </div>
            <div class="card">
                <h3>Order Summary</h3>
                <table>
                    <thead>
                        <tr><th>Item</th><th class="right">Qty</th><th class="right">Price</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="right"><?php echo (int)$row['quantity']; ?></td>
                                <td class="right">₱<?php echo htmlspecialchars(number_format((float)$row['price'] * (int)$row['quantity'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="right">Subtotal</th><th class="right">₱<?php echo htmlspecialchars(number_format((float)$totals['subtotal'], 2)); ?></th></tr>
                        <tr><th colspan="2" class="right">Shipping</th><th class="right">₱0.00</th></tr>
                        <tr><th colspan="2" class="right">Total</th><th class="right">₱<?php echo htmlspecialchars(number_format((float)$totals['subtotal'], 2)); ?></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <small>&copy; <?php echo date('Y'); ?> My Shop</small>
    </footer>
</body>
</html>


