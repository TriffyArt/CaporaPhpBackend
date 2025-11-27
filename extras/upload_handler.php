<?php
// upload_handler.php
session_start();
require 'db.php';  // contains PDO connection $pdo

$errors = [];

// CSRF token check
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = "Invalid CSRF token";
}

// Validate price (if included) – commented out since this form has no price
// $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
// if ($price === false || $price < 0) {
//     $errors[] = "Invalid price format";
//}

// Validate title
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
if (empty($title) || mb_strlen($title) > 100) {
    $errors[] = "Title must be 1–100 characters";
}
if (!preg_match('/^[a-zA-Z0-9\s\-\.]+$/', $title)) {
    $errors[] = "Title contains invalid characters";
}

// Validate description
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
if (empty($description)) {
    $errors[] = "Description is required";
}

// Validate file upload
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Image upload failed";
} else {
    $file = $_FILES['image'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!array_key_exists($mime, $allowed)) {
        $errors[] = "Invalid image type";
    }
    if ($file['size'] > 2_000_000) {
        $errors[] = "Image must be under 2MB";
    }
}

// If errors, redirect back with errors
if ($errors) {
    $_SESSION['errors'] = $errors;
    header("Location: product_upload.php");
    exit;
}

// Generate safe filename
$ext = $allowed[$mime];
$basename = bin2hex(random_bytes(8));
$targetDir = __DIR__ . '/uploads';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
$filename = "$basename.$ext";
$targetPath = "$targetDir/$filename";

// Move file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $_SESSION['errors'] = ["Failed to move uploaded file"];
    header("Location: product_upload.php");
    exit;
}

// Insert into database
$stmt = $pdo->prepare("
    INSERT INTO products (title, description, image_path, created_at)
    VALUES (:title, :desc, :img, NOW())
");
$stmt->execute([
    ':title' => $title,
    ':desc'  => $description,
    ':img'   => $filename
]);

// Unset CSRF token to enforce one-time use
unset($_SESSION['csrf_token']);

header("Location: gallery.php");
exit;
