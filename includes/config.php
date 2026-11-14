<?php 
define('DB_HOST','localhost');
define('DB_USER','sheb_nid');
define('DB_PASS','sheb_nid');
define('DB_NAME','sheb_nid');

// Establish database connection.
try
{
$dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME,DB_USER, DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
}
catch (PDOException $e)
{
exit("Error: " . $e->getMessage());
}
?>