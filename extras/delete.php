<?php

    require './config/db.php';

    $id = $_GET['student_id'] ?? null;

    if($id) {
        $sql = "DELETE FROM students WHERE stud_id = :id";
        $stmt = $con->prepare($sql);
        $stmt->execute([":id" => $id]);

        if($stmt) {
            echo '<script>alert("Student Deleted")</script>';

            header("Location: index.php");
            exit();
        } else {
            echo '<script>alert("Failed Deletion")</script>';
        }
    }



?>