<?php
$servername = "localhost";
$username = "icon_nid";
$password = "icon_nid";
$dbname = "icon_nid";

//Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8mb4");
?>