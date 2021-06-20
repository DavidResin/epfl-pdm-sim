<?php
// Initialize the session
session_start();
require_once "config.php";
 
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: dispatch.php");
    exit;
}

$id = $_SESSION["id"];
$username = $_SESSION["username"];
$stmt = $link->prepare("SELECT progress FROM users WHERE id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc()["progress"];


$stmt = $link->prepare("SELECT consent FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$consent = $stmt->get_result()->fetch_assoc()["consent"];

if ($progress > 1) {
    header("location: dispatch.php");
}

$gdr = "";
$gdr_err = "";

$stop = false;

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["gender"])) {
        $gdr_err = "To proceed you must provide an answer.";
    } else{
        $gdr = (int) $_POST["gender"];
        $sql = "UPDATE users SET progress = ?, gender = ? WHERE id = ?";

        if ($consent) {
            $fn = "logs/" . $username . '-q.json';
            $jsonString = file_get_contents($fn);
            $data = json_decode($jsonString, true);
            $data[$progress] = array("time" => time());
            $newJsonString = json_encode($data);
            file_put_contents($fn, $newJsonString);
        }

        // Prepare statement
        $stmt = $link->prepare($sql);
        $progress = $progress + 1;
        $stmt->bind_param('iis', $progress, $gdr, $id);

        // execute the query
        $stmt->execute();
        header('Location: dispatch.php');  
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
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <table style="width:100%;">
                        <tr>
                            <td>
                                <br>Für Forschungszweck wäre es noch hilfreich wenn sie uns ihr Geschlecht angeben könnten. Falls sie dies nicht tun möchten, wählen Sie unten einfach “keine Angabe” aus.
                            </td>
                        </tr>
                        <tr style="height: 25px"></tr>
                        <tr>
                            <td valign="top">
                                <div style="display: flex;justify-content: center">
                                    <div class="form-group">
                                        <input type="radio" id="male" name="gender" value="1">
                                        <label for="male">Männlich</label><br>
                                        <input type="radio" id="female" name="gender" value="2">
                                        <label for="female">Weiblich</label><br>
                                        <input type="radio" id="other" name="gender" value="3">
                                        <label for="other">Divers / Nicht-binär</label><br>
                                        <input type="radio" id="none" name="gender" value="4">
                                        <label for="none">Keine Angabe</label><br>
                                        <span style="color: red;font-size: 12px;"><?php echo $gdr_err; ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                        <tr style="height: 25px">
                        </tr>
                        <tr>
                            <td style="text-align: right;">
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary" value="Lab starten" onclick="return completeAndRedirect();">
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