<?php
    session_start();
    require './config/db.php';

    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($productId <= 0) {
        header('Location: ./shop.php');
        exit;
    }

    $product = null;
    try {
        $stmt = $con->prepare("SELECT p.id, p.name, p.description, p.price, p.quantity, p.image_path, c.name AS category_name
                                FROM products p LEFT JOIN categories c ON p.category_id = c.id
                                WHERE p.id = :id AND p.is_active = 1");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $product = null;
    }

    if (!$product) {
        header('Location: ./shop.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Product</title>
    <style>
        html, body { height: 100%; }
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; display: flex; min-height: 100vh; flex-direction: column; }
        header, footer { background: #2563eb; color: #fff; padding: 12px 16px; }
        nav a { color: #fff; margin-right: 10px; text-decoration: none; }
        main { max-width: 960px; margin: 16px auto; padding: 0 12px; flex: 1 0 auto; }
        .layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px; }
        .card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 16px; }
        .price { color: #2563eb; font-weight: bold; font-size: 20px; }
        label { display: block; margin-top: 10px; }
        input[type=number] { width: 100px; }
        button { background: #2563eb; color: #fff; padding: 8px 10px; border-radius: 6px; border: none; cursor: pointer; margin-top: 12px; }
        a.button { background: #6b7280; color: #fff; padding: 8px 10px; border-radius: 6px; text-decoration: none; }
        footer { margin-top: auto; }
    </style>
</head>
<body>
    <header>
        <strong>My Shop</strong>
        <nav style="float:right;">
            <a href="./index.php">Home</a>
            <a href="./shop.php">Shop</a>
            <a href="./cart.php">Cart</a>
            <a href="./orders.php">My Orders</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="./index.php">Dashboard</a>
                <a href="./logout.php?logout=true">Logout</a>
            <?php else: ?>
                <a href="./login.php">Login</a>
                <a href="./register.php">Sign Up</a>
            <?php endif; ?>
        </nav>
        <div style="clear:both"></div>
    </header>

    <main>
        <div class="layout">
            <div class="card">
                <div class="muted">Category: <?php echo htmlspecialchars($product['category_name'] ?? ''); ?></div>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="price">₱<?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?></div>
                <div>Stock: <?php echo htmlspecialchars((string)$product['quantity']); ?></div>
                <p style="margin-top: 12px; color: #333;"><?php echo nl2br(htmlspecialchars((string)$product['description'])); ?></p>
            </div>
            <div class="card">
                <form action="./add_to_cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string)$product['id']); ?>">
                    <label for="qty">Quantity</label>
                    <input id="qty" type="number" name="quantity" min="1" max="<?php echo (int)$product['quantity']; ?>" value="1" required>
                    <br>
                    <button type="submit">Add to Cart</button>
                    <a class="button" href="./shop.php">Continue Shopping</a>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <small>&copy; <?php echo date('Y'); ?> My Shop</small>
    </footer>
</body>
</html>


