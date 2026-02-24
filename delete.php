<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";

if(isset($_GET['id'])){

    $id = $_GET['id'];

    $sql = "DELETE FROM permohonan WHERE id = $id";

    if($conn->query($sql) === TRUE){
        header("Location: index.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>
