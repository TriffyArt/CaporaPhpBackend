<?php
    session_start();

    if(!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit;
    }

    require '../config/db.php';

    // Fetch active categories for the form
    $categories = [];
    try {
        $categories = $con->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        try {
            $categories = $con->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            $categories = [];
        }
    }

    // Handle create product (schema from db_ecommerce.sql)
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? $_POST['price'] : '';
        $stock = isset($_POST['stock']) ? $_POST['stock'] : '';
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        $errors = [];
        if ($name === '') { $errors[] = 'Name is required'; }
        if ($category_id <= 0) { $errors[] = 'Category is required'; }
        if (!is_numeric($price) || (float)$price < 0) { $errors[] = 'Price must be a non-negative number'; }
        if (!is_numeric($stock) || (int)$stock < 0) { $errors[] = 'Stock must be a non-negative integer'; }

        if (empty($errors)) {
            try {
                // Insert per schema: seller_id, category_id, name, description, price, quantity, image_path
                $stmt = $con->prepare("INSERT INTO products (seller_id, category_id, name, description, price, quantity, image_path) VALUES (:seller_id, :category_id, :name, :description, :price, :quantity, :image_path)");
                $stmt->execute([
                    ':seller_id' => (int)$_SESSION['user_id'],
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => (float)$price,
                    ':quantity' => (int)$stock,
                    ':image_path' => ''
                ]);
                header('Location: ./products.php?created=1');
                exit;
            } catch (Exception $e) {
                $create_error = 'Failed to create product: ' . $e->getMessage();
            }
        } else {
            $create_error = implode(' | ', $errors);
        }
    }

    // Fetch products per schema, join categories for display
    $products = [];
    try {
        $stmt = $con->query("SELECT p.id, p.name, p.description, p.price, p.quantity, p.image_path, p.created_at, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
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
    <title>Admin - Products</title>
</head>
<body>
    <header>
        <h1>Products</h1>
        <p>Signed in as: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</p>
    </header>

    <nav>
        <ul>
            <li><a href="./index.php">Overview</a></li>
            <li><a href="./users.php">Users</a></li>
            <li><a href="./categories.php">Categories</a></li>
            <li><a href="./products.php">Products</a></li>
            <li><a href="./orders.php">Orders</a></li>
            <li><a href="./reports.php">Reports</a></li>
            <li><a href="./settings.php">Settings</a></li>
            <li><a href="../logout.php?logout=true">Logout</a></li>
        </ul>
    </nav>

    <main>
        <section>
            <h2>Product Management</h2>
            <h3>Add New Product</h3>
            <?php if (isset($create_error)): ?>
                <p><?php echo htmlspecialchars($create_error); ?></p>
            <?php endif; ?>
            <?php if (isset($_GET['created'])): ?>
                <p>Product created successfully.</p>
            <?php endif; ?>
            <form action="" method="POST">
                <label>Name:</label>
                <input type="text" name="name" required>
                <br>
                <label>Price:</label>
                <input type="number" name="price" step="0.01" required>
                <br>
                <label>Stock:</label>
                <input type="number" name="stock" min="0" required>
                <br>
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars((string)$cat['id']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <br>
                <label>Description:</label>
                <textarea name="description" rows="3"></textarea>
                <br>
                <button type="submit">Create Product</button>
            </form>

            <h3>All Products</h3>
            <table border="1" cellpadding="6" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Image</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$p['id']); ?></td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo htmlspecialchars(isset($p['category_name']) ? $p['category_name'] : ''); ?></td>
                                <td><?php echo htmlspecialchars((string)$p['price']); ?></td>
                                <td><?php echo htmlspecialchars(isset($p['quantity']) ? (string)$p['quantity'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($p['image_path']) ? $p['image_path'] : ''); ?></td>
                                <td><?php echo htmlspecialchars(isset($p['created_at']) ? $p['created_at'] : ''); ?></td>
                                <td>
                                    <a href="#">View</a> |
                                    <a href="#">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Admin Panel</p>
    </footer>
</body>
</html>


