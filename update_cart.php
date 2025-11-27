<?php
    session_start();
    require './config/db.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    // Remove single item via GET
    if (isset($_GET['remove'])) {
        $pid = (int)$_GET['remove'];
        if ($pid > 0) {
            try {
                $stmt = $con->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
                $stmt->execute([':uid' => $userId, ':pid' => $pid]);
            } catch (Exception $e) {}
        }
        header('Location: ./cart.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./cart.php');
        exit;
    }

    $qtyMap = isset($_POST['qty']) && is_array($_POST['qty']) ? $_POST['qty'] : [];
    if (empty($qtyMap)) {
        header('Location: ./cart.php');
        exit;
    }

    try {
        $con->beginTransaction();

        foreach ($qtyMap as $pidStr => $qtyVal) {
            $pid = (int)$pidStr; $qty = (int)$qtyVal;
            if ($pid <= 0) { continue; }

            // Check stock
            $stock = 0;
            $s = $con->prepare("SELECT quantity FROM products WHERE id = :id");
            $s->execute([':id' => $pid]);
            $stockRes = $s->fetchColumn();
            if ($stockRes === false) { // product removed, drop from cart
                $del = $con->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
                $del->execute([':uid' => $userId, ':pid' => $pid]);
                continue;
            }
            $stock = (int)$stockRes;

            if ($qty <= 0) {
                $del = $con->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
                $del->execute([':uid' => $userId, ':pid' => $pid]);
                continue;
            }

            $qty = min($qty, max(0, $stock));
            if ($qty <= 0) {
                $del = $con->prepare("DELETE FROM cart WHERE user_id = :uid AND product_id = :pid");
                $del->execute([':uid' => $userId, ':pid' => $pid]);
                continue;
            }

            $upd = $con->prepare("UPDATE cart SET quantity = :q WHERE user_id = :uid AND product_id = :pid");
            $upd->execute([':q' => $qty, ':uid' => $userId, ':pid' => $pid]);
        }

        $con->commit();
    } catch (Exception $e) {
        if ($con->inTransaction()) { $con->rollBack(); }
    }

    header('Location: ./cart.php');
    exit;


