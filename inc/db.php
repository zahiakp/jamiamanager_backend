<?php

$servername = "localhost";
$password = "";
$username = "root";
$dbname = "jamia_db";
// Create conn
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check conn
if (!$conn) {
    die("conn failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn,"utf8");

// $servername = "154.41.233.1";
// $password = "JamiaMNC@321!@#";
// $username = "u753608608_jamiamnc";
// $dbname = "u753608608_jamiamnc";
// // Create conn
// $conn = mysqli_connect($servername, $username, $password, $dbname);
// // Check conn
// if (!$conn) {
//     die("conn failed: " . mysqli_connect_error());
// }
// mysqli_set_charset($conn,"utf8");

