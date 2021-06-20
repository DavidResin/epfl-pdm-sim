<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define('DB_SERVER', 'mysql-fac.epfl.ch');
define('DB_PORT', '33002');
define('DB_USERNAME', 'ic_dvet_chemlab');
define('DB_PASSWORD', 'kvVK(zfE.?;e1:Qteu,/-_');
define('DB_NAME', 'chemlab');
 
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER . ":" . DB_PORT, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>