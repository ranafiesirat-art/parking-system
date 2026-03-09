<?php
session_start();

/*
|--------------------------------------------------------------------------
| Pastikan user sudah login di Parking System
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: /parking-system/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| SSO Secret Key (mesti sama dengan mileage-system)
|--------------------------------------------------------------------------
*/
$secret = "kulai_81000_strong_secret_81000";

/*
|--------------------------------------------------------------------------
| Data user
|--------------------------------------------------------------------------
*/
$username  = $_SESSION['username'] ?? '';
$timestamp = time();

/*
|--------------------------------------------------------------------------
| Generate token SSO
|--------------------------------------------------------------------------
*/
$hash  = hash('sha256', $username . '|' . $timestamp . '|' . $secret);
$token = base64_encode($username . '|' . $timestamp . '|' . $hash);

/*
|--------------------------------------------------------------------------
| Redirect ke Mileage System (ROOT SAFE REDIRECT)
|--------------------------------------------------------------------------
*/
header("Location: /mileage-system/login.php?token=" . urlencode($token));
exit;