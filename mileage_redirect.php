<?php
session_start();

// Pastikan user dah login di parking-system
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Secret key (mesti sama 100% dengan yang ada di login.php mileage-system)
$secret = "kulai_81000_strong_secret_81000";

// Data untuk token
$username = $_SESSION['username'];
$timestamp = time();

// Buat hash untuk keselamatan (guna sha256 supaya lebih kuat)
$hash = hash('sha256', $username . '|' . $timestamp . '|' . $secret);

// Token ringkas & selamat (base64 encode)
$token = base64_encode($username . '|' . $timestamp . '|' . $hash);

// Redirect ke mileage-system dengan token
header("Location: https://nrinnovations.my/mileage-system/login.php?token=" . urlencode($token));
exit;
?>