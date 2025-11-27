<?php
    session_start();
    require './config/db.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./checkout.php');
        exit;
    }

    // Basic CSRF check (lightweight)
    $csrf = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($csrf !== md5(session_id().'checkout')) {
        header('Location: ./checkout.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $shippingAddress = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
    $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'paymongo';
    if ($shippingAddress === '') {
        header('Location: ./checkout.php');
        exit;
    }

    try {
        // Fetch cart with prices and validate stock
        $stmt = $con->prepare("SELECT c.product_id, c.quantity, p.price, p.quantity AS stock
                               FROM cart c JOIN products p ON c.product_id = p.id
                               WHERE c.user_id = :uid FOR UPDATE");
        $con->beginTransaction();
        $stmt->execute([':uid' => $userId]);
        $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart)) {
            $con->rollBack();
            header('Location: ./cart.php');
            exit;
        }

        $total = 0.0;
        foreach ($cart as $row) {
            if ((int)$row['quantity'] <= 0 || (int)$row['stock'] <= 0) {
                continue;
            }
            $qty = min((int)$row['quantity'], (int)$row['stock']);
            $total += ((float)$row['price']) * $qty;
        }

        if ($total <= 0) {
            $con->rollBack();
            header('Location: ./cart.php');
            exit;
        }

        // Create order (schema uses buyer_id, total_amount, order_status/payment_status)
        $orderStmt = $con->prepare("INSERT INTO orders (buyer_id, total_amount, shipping_address, payment_method, order_status, payment_status)
                                    VALUES (:buyer_id, :total_amount, :shipping_address, :payment_method, 'pending', 'pending')");
        $orderStmt->execute([
            ':buyer_id' => $userId,
            ':total_amount' => $total,
            ':shipping_address' => $shippingAddress,
            ':payment_method' => $paymentMethod,
        ]);
        $orderId = (int)$con->lastInsertId();

		// Record initial payment transaction
		$txnStmt = $con->prepare("INSERT INTO payment_transactions (order_id, transaction_id, amount, currency, status, payment_method)
			VALUES (:order_id, :transaction_id, :amount, 'PHP', :status, :payment_method)");
		$initialStatus = 'pending';
		// Generate a simple transaction reference based on method
		if ($paymentMethod === 'cod') {
			$transactionId = 'COD-' . $orderId . '-' . time();
		} else {
			$transactionId = 'PM-' . bin2hex(random_bytes(6));
		}
		$txnStmt->execute([
			':order_id' => $orderId,
			':transaction_id' => $transactionId,
			':amount' => $total,
			':status' => $initialStatus,
			':payment_method' => $paymentMethod,
		]);

        // Insert order items and decrement stock
        $itemStmt = $con->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                                   VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)");
        $decStmt = $con->prepare("UPDATE products SET quantity = quantity - :q WHERE id = :pid AND quantity >= :q");

        foreach ($cart as $row) {
            $qty = min((int)$row['quantity'], (int)$row['stock']);
            if ($qty <= 0) { continue; }
            $lineTotal = ((float)$row['price']) * $qty;
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':product_id' => (int)$row['product_id'],
                ':quantity' => $qty,
                ':unit_price' => (float)$row['price'],
                ':total_price' => $lineTotal,
            ]);
            $decStmt->execute([':q' => $qty, ':pid' => (int)$row['product_id']]);
        }

        // Clear cart
        $del = $con->prepare("DELETE FROM cart WHERE user_id = :uid");
        $del->execute([':uid' => $userId]);

        $con->commit();
    } catch (Exception $e) {
        if ($con->inTransaction()) { $con->rollBack(); }
        header('Location: ./checkout.php');
        exit;
    }

    header('Location: ./order_view.php?id=' . urlencode((string)$orderId));
    exit;


