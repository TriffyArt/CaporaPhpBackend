<?php
// product_upload.php
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Product</title>
</head>
<body>
    <h1>Upload Product</h1>
    <?php if (!empty($_SESSION['errors'])): ?>
        <ul style="color:red;">
            <?php foreach ($_SESSION['errors'] as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>
    <form method="POST" action="upload_handler.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <label>
            Title:<br>
            <input type="text" name="title" maxlength="100" required>
        </label><br><br>
        <label>
            Description:<br>
            <textarea name="description" rows="4" cols="50" required></textarea>
        </label><br><br>
        <label>
            Image:<br>
            <input type="file" name="image" accept="image/jpeg,image/png" required>
        </label><br><br>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
