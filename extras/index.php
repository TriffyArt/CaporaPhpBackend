<?php
    require './config/db.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
</head>
<body>
    <form action="insert.php" method="POST">
        <input type="text" name="fname" placeholder="first name">
        <input type="text" name="mname" placeholder="middle name">
        <input type="text" name="lname" placeholder="last name">
        <input type="text" name="email" placeholder="email address">
        <input type="text" name="pass" placeholder="password">
        <button type="submit">Submit</button>
    </form>

    <hr>

    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Email Address</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <tr>
            <?php
                $sql = $con->query("SELECT * FROM students");

                while($data = $sql->fetch(PDO::FETCH_ASSOC)) {
                   // var_dump($data);
                    echo "<tr>
                        <td>{$data['stud_id']}</td>
                        <td>{$data['stud_fname']}</td>
                        <td>{$data['stud_mname']}</td>
                        <td>{$data['stud_lname']}</td>
                        <td>{$data['stud_email']}</td>
                        <td>{$data['created_at']}</td>
                        <td>
                            <a href='edit.php?student_id={$data['stud_id']}'>Edit</a> | 
                            <a href='delete.php?student_id={$data['stud_id']}'>Delete</a>
                        </td></tr>
                    ";
                }
            ?>
        </tr>
    </table>
</body>
</html>