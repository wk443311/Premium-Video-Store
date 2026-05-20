<?php
$servername = "localhost";
$username = "axmirstore_topupbd";
$password = "axmirstore_topupbd";
$dbname = "axmirstore_topupbd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("DB ERROR: " . $conn->connect_error);
    $conn = false; // important
}

$conn && $conn->set_charset("utf8mb4");
?>