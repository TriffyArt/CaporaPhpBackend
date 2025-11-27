<?php
    session_start();
    require './config/db.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    // Fetch cart items joined with product data
    $items = [];
    $totals = [ 'subtotal' => 0.0, 'count' => 0 ];
    try {
        $stmt = $con->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.quantity AS stock
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; }
        header, footer { background: #2563eb; color: #fff; padding: 12px 16px; }
        nav a { color: #fff; margin-right: 10px; text-decoration: none; }
        main { max-width: 1000px; margin: 16px auto; padding: 0 12px; }
        table { width: 100%; background: #fff; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #eee; text-align: left; }
        .right { text-align: right; }
        button, .button { background: #2563eb; color: #fff; padding: 6px 10px; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <header>
        <strong>My Cart</strong>
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
        <h2>Cart Items</h2>
        <form action="./update_cart.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="right">Price</th>
                        <th class="right">Quantity</th>
                        <th class="right">Line Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $row): $line = ((float)$row['price']) * (int)$row['quantity']; ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <span class="muted">In stock: <?php echo htmlspecialchars((string)$row['stock']); ?></span>
                                </td>
                                <td class="right">₱<?php echo htmlspecialchars(number_format((float)$row['price'], 2)); ?></td>
                                <td class="right">
                                    <input type="number" name="qty[<?php echo (int)$row['product_id']; ?>]" min="0" max="<?php echo (int)$row['stock']; ?>" value="<?php echo (int)$row['quantity']; ?>">
                                </td>
                                <td class="right">₱<?php echo htmlspecialchars(number_format((float)$line, 2)); ?></td>
                                <td><a class="button" href="./update_cart.php?remove=<?php echo (int)$row['product_id']; ?>">Remove</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Your cart is empty.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <a class="button" href="./shop.php">Continue Shopping</a>
                </div>
                <div class="right">
                    <div>Items: <?php echo htmlspecialchars((string)$totals['count']); ?></div>
                    <div><strong>Subtotal: ₱<?php echo htmlspecialchars(number_format((float)$totals['subtotal'], 2)); ?></strong></div>
                    <div style="margin-top: 8px;">
                        <button type="submit">Update Cart</button>
                        <a class="button" href="./checkout.php">Checkout</a>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <footer>
        <small>&copy; <?php echo date('Y'); ?> My Shop</small>
    </footer>
</body>
</html>


