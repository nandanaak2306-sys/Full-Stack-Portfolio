<?php
$servername = "sql308.infinityfree.com";
$username = "if0_41753444";
$password = "NandanaAK2306";
$dbname = "if0_41753444_portfolio_db";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
