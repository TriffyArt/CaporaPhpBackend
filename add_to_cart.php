<?php
    session_start();
    require './config/db.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./shop.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($productId <= 0 || $quantity <= 0) {
        header('Location: ./shop.php');
        exit;
    }

    // Ensure product exists and is active and has stock
    try {
        $stmt = $con->prepare("SELECT quantity FROM products WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $productId]);
        $stock = $stmt->fetchColumn();
        if ($stock === false) {
            header('Location: ./shop.php');
            exit;
        }
        if ($quantity > (int)$stock) {
            $quantity = (int)$stock;
        }
    } catch (Exception $e) {
        header('Location: ./shop.php');
        exit;
    }

    // Upsert into cart (unique user_id, product_id)
    try {
        $con->beginTransaction();

        $stmt = $con->prepare("SELECT quantity FROM cart WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        $existingQty = $stmt->fetchColumn();

        if ($existingQty !== false) {
            $newQty = max(1, (int)$existingQty + $quantity);
            $newQty = min($newQty, (int)$stock);
            $upd = $con->prepare("UPDATE cart SET quantity = :q WHERE user_id = :uid AND product_id = :pid");
            $upd->execute([':q' => $newQty, ':uid' => $userId, ':pid' => $productId]);
        } else {
            $ins = $con->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :q)");
            $ins->execute([':uid' => $userId, ':pid' => $productId, ':q' => $quantity]);
        }

        $con->commit();
    } catch (Exception $e) {
        if ($con->inTransaction()) { $con->rollBack(); }
    }

    header('Location: ./cart.php');
    exit;


