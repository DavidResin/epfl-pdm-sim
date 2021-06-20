<?php
// Initialize the session
session_start();
require_once "config.php";
 
// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$id = $_SESSION["id"];
$stmt = $link->prepare("SELECT progress FROM users WHERE id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc()["progress"];

switch ($progress) {
    case 0:
        header("location: intro1.php");
        break;
    case 1:
        header("location: intro2.php");
        break;
    case 2:
    case 6:
        header("location: info.php");
        break;
    case 5:
        header("location: feedback.php");
        break;
    default:
        header("location: quiz.php");
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>EPFL D-VET - Chemlab</title>
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    </head>
    <body>
    </body>
</html>