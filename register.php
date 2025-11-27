<?php
    require './config/db.php';

    if($_SERVER['REQUEST_METHOD'] === "POST") {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = 'buyer';

        if(!preg_match("/^[a-zA-Z0-9_]{5,20}$/", $username)) {
            die("Username must be 5-20 characters, letters/numbers/underscore only.");
        }

        if(!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
            die("Invalid email format.");
        }

        if(!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $password)) {
            die("Password must be at least 8 chars with upper, lower, number, and special character.");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $check = $con->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $check->execute([$username]);

        if($check->rowCount() > 0) {
            die("Username already taken. Choose another one.");
        }

        $stmt = $con->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password, :role)");
        $check = $stmt->execute([
            ":username" => $username,
            ":email" => $email,
            ":password" => $hashedPassword,
            ":role" => $role
        ]);

        if($check) {
            echo "<script>alert('Registration successful!')</script>";
        } else {
            echo "Error: ". $stmt->error;
        }
    }


?>


<form action="" method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="text" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button><br>
    <a href="./login.php">I already have an account</a>
</form>