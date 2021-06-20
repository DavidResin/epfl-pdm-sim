<?php
// Initialize the session
session_start();
require_once "config.php";

$title = "";
$subtitle = "";
$button = "Continuer";
$name = "";
 
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $title = "L'activité est maintenant terminée. Vous pouvez fermer la fenêtre du navigateur.";
    $button = "Recommencer l'expérience";

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
            $title = "Votre première tâche consiste à explorer le laboratoire «Beer's Law». Sélectionnez la simulation «Beer's Law» dans la fenêtre suivante.";
            break;
        case 3:
            $title = "Dans cette partie, vous devrez résoudre un problème. Pour ce faire, sélectionnez à nouveau le laboratoire «Beer's Law».";
            $subtitle = "Remarque: Notez bien votre solution afin de pouvoir nous la donner à l'étape qui suivra.";
            break;
        case 14:
            $title = "Vous y êtes presque - c'est la dernière tâche pour aujourd'hui. Encore une fois, vous devez sélectionner le laboratoire «Beer's Law».";
            $subtitle = "Remarque: Notez bien votre solution afin de pouvoir nous la donner à l'étape qui suivra.";
            break;
        case 17:
            $name = $consent ? $_SESSION["username"] : "";

            $title = "Vous avez terminé toutes les tâches - merci d'avoir participé! <a href='files/fr/solution.pdf' target=”_blank”>Vous pouvez consulter les solutions ici.</a>";
            $button = "Mettre fin à l'expérience";
            break;
        default:
            header("location: dispatch.php");
            break;
    }

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if ($progress == 17) {
            $username = $_SESSION["username"];
            $fn = "../logs/" . $username . '-q.json';
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