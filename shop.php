<?php
    session_start();
    require './config/db.php';

    // Optional: allow browsing without login, but personalize if logged in
    $currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // Load categories for filtering
    $categories = [];
    try {
        $categories = $con->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $categories = [];
    }

    // Filters
    $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Build query
    $query = "SELECT p.id, p.name, p.description, p.price, p.quantity, p.image_path, c.name AS category_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.is_active = 1";
    $params = [];

    if ($categoryId > 0) {
        $query .= " AND p.category_id = :categoryId";
        $params[':categoryId'] = $categoryId;
    }

    if ($search !== '') {
        $query .= " AND (p.name LIKE :q OR p.description LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }

    $query .= " ORDER BY p.id DESC";

    $products = [];
    try {
        $stmt = $con->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $products = [];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop</title>
    <style>
        html, body { height: 100%; }
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; display: flex; min-height: 100vh; flex-direction: column; }
        header, footer { background: #2563eb; color: #fff; padding: 12px 16px; }
        main { max-width: 1100px; margin: 16px auto; padding: 0 12px; flex: 1 0 auto; }
        .toolbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; }
        .card h3 { margin: 6px 0; font-size: 16px; color: #111; }
        .price { color: #2563eb; font-weight: bold; margin: 6px 0; }
        .muted { color: #666; font-size: 12px; }
        .actions { margin-top: auto; display: flex; gap: 8px; }
        a.button, button { background: #2563eb; color: #fff; padding: 8px 10px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; }
        a.button.secondary { background: #6b7280; }
        nav a { color: #fff; margin-right: 10px; text-decoration: none; }
        form.inline { display: inline; }
        footer { margin-top: auto; }
    </style>
    <script>
        function addToCart(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add_to_cart.php';
            const id = document.createElement('input');
            id.type = 'hidden'; id.name = 'product_id'; id.value = productId;
            const qty = document.createElement('input');
            qty.type = 'hidden'; qty.name = 'quantity'; qty.value = 1;
            form.appendChild(id); form.appendChild(qty);
            document.body.appendChild(form); form.submit();
        }
    </script>
    </head>
<body>
    <header>
        <strong>My Shop</strong>
        <nav style="float:right;">
            <a href="./index.php">Home</a>
            <a href="./shop.php">Shop</a>
            <a href="./cart.php">Cart</a>
            <a href="./orders.php">My Orders</a>
            <?php if ($currentUserId): ?>
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
        <form method="GET" class="toolbar">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
            <select name="category">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars((string)$cat['id']); ?>" <?php echo $categoryId === (int)$cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filter</button>
        </form>

        <div class="grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <div class="card">
                        <div class="muted"><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></div>
                        <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                        <div class="price">₱<?php echo htmlspecialchars(number_format((float)$p['price'], 2)); ?></div>
                        <div class="muted">Stock: <?php echo htmlspecialchars((string)($p['quantity'])); ?></div>
                        <p class="muted"><?php echo htmlspecialchars(mb_strimwidth((string)$p['description'], 0, 120, '...')); ?></p>
                        <div class="actions">
                            <a class="button secondary" href="./product.php?id=<?php echo htmlspecialchars((string)$p['id']); ?>">View</a>
                            <button type="button" onclick="addToCart(<?php echo (int)$p['id']; ?>)">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <small>&copy; <?php echo date('Y'); ?> My Shop</small>
    </footer>
</body>
</html>


