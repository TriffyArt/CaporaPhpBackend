<?php

    require './config/db.php';

    if($_SERVER['REQUEST_METHOD'] == "POST") {
        $insert = "INSERT INTO students (stud_fname, stud_mname, stud_lname, stud_email, stud_password) VALUES (:stud_fname,:stud_mname,:stud_lname,:stud_email,:stud_pass)";
        $stmt = $con->prepare($insert);
        $stmt->execute([
            ':stud_fname' => $_POST['fname'],
            ':stud_mname' => $_POST['mname'],
            ':stud_lname' => $_POST['lname'],
            ':stud_email' => $_POST['email'],
            ':stud_pass' => $_POST['pass'],
        ]);
        

        header("Location: index.php");
        exit();
    } else {
        header("Location: notfound.php");
        exit();
    }

?>