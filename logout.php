<?php
session_start();
session_destroy(); // Musnahkan semua session
header("Location: login.php"); // Redirect balik ke login
exit;
?>