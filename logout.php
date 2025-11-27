<?php
    session_start();
    
    if(isset($_GET['logout']) && $_GET['logout'] === 'true') {
        $_SESSION = [];
        session_destroy();

        // Redirect to landing page
        header("Location: ./index.php");
        exit;
    }
    
    header("Location: ./index.php");
    exit;
?>