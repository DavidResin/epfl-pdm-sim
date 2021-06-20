<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: dispatch.php");
    exit;
}

require_once "config.php";

function generateRandomString($length = 10) {
    $characters = '23456789abcdefghjkmnpqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$cons = "";
$cons_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
 
    // Check if username is empty
    if (empty($_POST["cons"])) {
        $cons_err = "Um fortzufahren, müssen Sie eine Antwort eingeben.";
    } else{
        $cons = $_POST["cons"];
    }

    if(empty($cons_err)){
        $sql = "UPDATE users SET consent = ?, progress = ? WHERE id = ?";

        // Prepare statement
        $stmt = $link->prepare($sql);
        $id = $_SESSION["id"];
        $username = $_SESSION["username"];
        $param_cons = $cons == "yes" ? 1 : 0;
        $param_progress = 2 - $param_cons;
        echo $param_progress;
        $stmt->bind_param('iii', $param_cons, $param_progress, $id);
        $stmt->execute();

        if ($cons == "yes") {
            $arr = array("1", "2", "3");

            foreach ($arr as &$value) {
                $file = fopen("logs/{$username}-{$value}.log", "w");
                fclose($file);
            }

            $arr = array("name" => $username);                        
            file_put_contents("logs/{$username}-q.json", json_encode($arr));
        }

        if ($consent) {
            $fn = "logs/" . $username . '-q.json';
            $jsonString = file_get_contents($fn);
            $data = json_decode($jsonString, true);
            $data[$progress] = array("time" => time());
            $newJsonString = json_encode($data);
            file_put_contents($fn, $newJsonString);
        }

        header("location: dispatch.php");
    }
    
    // Close connection
    mysqli_close($link);
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
                <form id="myForm" action="javascript:void(0)" method="post">
                    <table style="width:100%;">
                        <tr>
                            <td colspan="2" style="text-align: center">
                                <h1>Mit virtuellen Experimenten zum Lernerfolg</h1>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">
                                <p>Bevor es mit der Lernaktivität losgeht, noch einige wichtige Punkte:</p>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Das Chemlab bietet Ihnen eine geschützte Lernumgebung jenseits von Noten und Bewertungen. Experimentieren Sie ohne Angst vor Fehlern, niemand kann Sie dabei beobachten. Die Lernaktivität hat keinerlei Einfluss auf Ihre Noten und Ihre Lehrperson hat keinen Zugriff auf Ihre Daten. Übrigens: Wussten Sie, dass Thomas Edison fast 9000 Versuche unternahm, bis er die Glühlampe zur Marktreife entwickelt hatte?</td>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Um virtuelle Lernumgebungen stetig zu verbessern, würden wir gerne die Lernaktivität aller Nutzer im Chemlab analysieren. Dazu werden die Interaktionsdaten mit der Simulation (Clickstream-Daten), die Lösungen zu den Aufgaben und die Antworten zum Fragebogen in <b>anonymisierter Form</b> gespeichert (unter Beachtung des  Schweizerischen Bundesgesetzes über Datenschutz (SR 235.1)). Wir speichern Ihre Daten mit Hilfe eines zufällig generierten Codes ab und haben deshalb während der Datenanalyse keine Kenntnisse über Ihre wahre Identität. Durch das Teilen ihrer Daten tragen Sie wesentlich dazu bei, die Lernaktivität für künftige Nutzer zu verbessern.</td>
                        </tr>
                        <tr style="height: 25px"></tr>
                        <tr>
                            <td style="width: 50px"></td>
                            <th>Bitte geben Sie an, ob Sie an der Forschungsstudie teilnehmen wollen:</th>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td colspan="2">
                                <div style="display: flex;justify-content: center">
                                    <div class="form-group">
                                        <input type="radio" id="yes" <?php if (isset($cons) && $cons=="yes") echo "checked";?> name="cons" value="yes">
                                        <label for="yes">Ja, ich teile meine anonymisierten Daten für Forschungszwecke.</label><br>
                                        <input type="radio" id="no" <?php if (isset($cons) && $cons=="no") echo "checked";?> name="cons" value="no">
                                        <label for="no">Nein, ich teile meine anonymisierten Daten nicht für Forschungszwecke.</label><br>
                                        <span style="color: red;font-size: 12px;"><?php echo $cons_err; ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Selbstverständlich können Sie auch an der Lernaktivität teilnehmen, wenn Sie uns Ihre Daten nicht zur Verfügung stellen wollen!</td>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td valign="top"></td>
                            <td>Nun wünschen wir Ihnen viel Spass im Chemlab!</td>
                            <td style="text-align: right;">
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary" value="Weiter" onclick="return completeAndRedirect();">
                                </div>
                            </td>
                        </tr>
                    </table>
                </form>
                <script>
                    function completeAndRedirect() {
                        var r = confirm("Sind Sie sicher, dass sie fortfahren wollen?");

                        if (r == true) {
                            document.getElementById("myForm").action = "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>";
                            document.getElementById("myForm").submit();
                        }
                    }
                </script>
            </div>
        </div>
    </body>
</html>