<?php
$servername = "localhost"; 
$username   = "root"; 
$password   = ""; 
$dbname     = "culture_connect_grp_11";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>