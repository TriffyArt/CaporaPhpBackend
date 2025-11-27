<?php
    require './config/db.php';
?>

<form action="edit.php" method="POST">
    <input type="text" name="fname" placeholder="first name">
    <input type="text" name="mname" placeholder="middle name">
    <input type="text" name="lname" placeholder="last name">
    <input type="text" name="email" placeholder="email address">
    <input type="text" name="pass" placeholder="password">
    <button type="submit">Update</button>
</form>