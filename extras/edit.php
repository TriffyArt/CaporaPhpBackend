<?php

    require './config/db.php';

    $id = $_GET['student_id'] ?? null;
    if(!$id) { die("Invalid ID"); }

    if($_SERVER['REQUEST_METHOD'] == "POST") {  

        $update = "UPDATE students SET 
            stud_fname = :stud_fname, 
            stud_mname = :stud_mname, 
            stud_lname = :stud_lname, 
            stud_email = :stud_email, 
            stud_password = :stud_pass 
        WHERE stud_id = :id";
        $stmt = $con->prepare($update);
        $stmt->execute([
            ':stud_fname' => $_POST['fname'],
            ':stud_mname' => $_POST['mname'],
            ':stud_lname' => $_POST['lname'],
            ':stud_email' => $_POST['email'],
            ':stud_pass' => $_POST['pass'],
            ':id' => $id,
        ]);

        header("Location: index.php");
        exit();
    }

    $sql = $con->prepare("SELECT * FROM students WHERE stud_id = :id");
    $sql->execute([":id" => $id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);

    //var_dump($row);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
</head>
<body>
    

<form method="POST">
    <input type="text" name="fname" value="<?php echo $row['stud_fname']; ?>" placeholder="first name">
    <input type="text" name="mname" value="<?php echo $row['stud_mname']; ?>" placeholder="middle name">
    <input type="text" name="lname" value="<?php echo $row['stud_lname']; ?>" placeholder="last name">
    <input type="text" name="email" value="<?php echo $row['stud_email']; ?>" placeholder="email address">
    <input type="text" name="pass" value="<?php echo $row['stud_password']; ?>" placeholder="password">
    <button type="submit">Update</button>
</form>
</body>
</html>