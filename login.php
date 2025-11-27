<?php
    session_start();
    require './config/db.php';

    // Redirect authenticated users away from the login page
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            header("Location: ./admin/dashboard.php");
            exit();
        }
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
            header("Location: ./seller/dashboard.php");
            exit();
        }
        header("Location: ./index.php");
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if(!filter_var($username, FILTER_VALIDATE_EMAIL) && !preg_match("/^[a-zA-Z0-9_]{5,20}$/", $username)) {
            die("Invalid username format!");
        }

        $stmt = $con->prepare("SELECT * FROM users WHERE username = :user OR email = :user LIMIT 1");
        $stmt->execute([":user" => $username]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if($data) {
            $hash = isset($data['password_hash']) ? $data['password_hash'] : (isset($data['password']) ? $data['password'] : '');
            if($hash !== '' && password_verify($password, $hash)) {
                $_SESSION['user_id'] = $data['user_id'];
                $_SESSION['role'] = $data['role'];
                $_SESSION['username'] = $data['username'];

                if($_SESSION['role'] === 'admin') {
                    header("Location: ./admin/dashboard.php");
                    exit();
                }
                if($_SESSION['role'] === 'seller') {
                    header("Location: ./seller/dashboard.php");
                    exit();
                }
                
                // if user is 'buyer'
                header("Location: ./index.php");
                exit();
            } else {
                echo "Invalid password!";
            }
        } else {
            echo "No user found!";
        }
    }
?>

<form action="" method="POST">
    <input type="text" name="username" placeholder="Username/Email Address" required><br>
    <input type="password" name="password" placeholder="Password" require><br>
    <button type="submit">Login</button><br>
    <a href="./register.php">I don't have an account</a>
</form>