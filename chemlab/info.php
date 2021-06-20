<?php
// Initialize the session
session_start();
require_once "config.php";

$title = "";
$subtitle = "";
$button = "Weiter";
$name = "";
 
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $title = "Die Lernaktivität ist beendet. Sie können das Browserfenster nun schliessen.";
    $button = "Aufgaben erneut bearbeiten";

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Location: login.php');
    }

} else {
    $id = $_SESSION["id"];
    $stmt = $link->prepare("SELECT progress FROM users WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $progress = $stmt->get_result()->fetch_assoc()["progress"];

    $stmt = $link->prepare("SELECT consent FROM users WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $consent = $stmt->get_result()->fetch_assoc()["consent"];

    switch ($progress) {
        case 2:
            $title = "Ihre erste Aufgabe ist es das “Beer’s Law” Lab zu erkunden. Wählen Sie dazu im Anschluss die “Beer’s Law” Simulation.";
            break;
        case 3:
            $title = "In diesem Teil müssen Sie nun eine Aufgabe lösen. Wählen Sie dazu wieder das “Beer’s Law” Lab aus.";
            $subtitle = "Hinweis: Schreiben Sie sich Ihre Lösung auf damit Sie diese später abgeben können.";
            break;
        case 14:
            $title = "Sie haben es fast geschafft - dies ist die letzte Aufgabe für heute. Auch hier müssen sie wieder das “Beer’s Law” Lab auswählen.";
            $subtitle = "Hinweis: Schreiben Sie sich Ihre Lösung auf damit Sie diese später abgeben können.";
            break;
        case 17:
            $name = $consent ? $_SESSION["username"] : "";

            $title = "Sie haben alle Aufgaben bearbeitet - vielen Dank für Ihre Teilnahme! <a href='files/de/solution.pdf' target=”_blank”>Hier können Sie die Lösungen konsultieren.</a>";
            $button = "Lab beenden";
            break;
        default:
            header("location: dispatch.php");
            break;
    }

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if ($progress == 17) {
            $username = $_SESSION["username"];
            $fn = "logs/" . $username . '-q.json';
            $jsonString = file_get_contents($fn);
            $data = json_decode($jsonString, true);
            $data[$progress] = array("time" => time());
            $newJsonString = json_encode($data);
            file_put_contents($fn, $newJsonString);
            header('Location: logout.php');
        }
        else {
            header('Location: sim.php');
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>EPFL D-VET - Chemlab</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
        <link rel="stylesheet" href="css/layout.css">
    </head>
    <body>
        <div class="base-container">
            <div class="content-container">
                <table style="width:100%;">
                    <tr>
                        <td style="text-align: justify">
                            <h2><?php echo $title ?></h2>
                            <h5><?php echo $subtitle ?></h5>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right">
                            <form id="myForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary" value="<?php echo $button; ?>" onclick="return completeAndRedirect();">
                                </div>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>