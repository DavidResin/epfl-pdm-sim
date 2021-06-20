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
        $cons_err = "Pour continuer, vous devez donner une réponse.";
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
                $file = fopen("../logs/{$username}-{$value}.log", "w");
                fclose($file);
            }

            $arr = array("name" => $username);                        
            file_put_contents("../logs/{$username}-q.json", json_encode($arr));
        }

        if ($consent) {
            $fn = "../logs/" . $username . '-q.json';
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
                                <h1>Des expériences virtuelles au succès de l'apprentissage</h1>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">
                                <p>Avant de commencer l'activité d'apprentissage, quelques points importants:</p>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Chemlab vous offre un environnement d'apprentissage protégé au-delà des notes et des évaluations. Expérimentez sans craindre de faire des erreurs, personne ne peut vous observer. L'activité d'apprentissage n'a aucune incidence sur vos notes et votre enseignant n'a pas accès à vos données. À propos, saviez-vous que Thomas Edison a fait près de 9000 essais avant de mettre au point l'ampoule électrique jusqu'à sa maturité commerciale?</td>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Afin d'améliorer continuellement les environnements d'apprentissage virtuels, nous aimerions analyser l'activité d'apprentissage de tous les utilisateurs du Chemlab. À cette fin, les données d'interaction avec la simulation (données clickstream), les solutions aux tâches et les réponses au questionnaire sont stockées <b>sous forme anonyme</b> (conformément à la loi fédérale suisse sur la protection des données (RS 235.1)). Nous stockons vos données à l'aide d'un code généré de manière aléatoire et n'avons donc aucune connaissance de votre véritable identité pendant l'analyse des données. En partageant vos données, vous contribuez de manière significative à l'amélioration de l'activité d'apprentissage pour les futurs utilisateurs.</td>
                        </tr>
                        <tr style="height: 25px"></tr>
                        <tr>
                            <td style="width: 50px"></td>
                            <th>Veuillez indiquer si vous souhaitez participer à l'étude de recherche:</th>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td colspan="2">
                                <div style="display: flex;justify-content: center">
                                    <div class="form-group">
                                        <input type="radio" id="yes" <?php if (isset($cons) && $cons=="yes") echo "checked";?> name="cons" value="yes">
                                        <label for="yes">Oui, je partage mes données anonymes à des fins de recherche.</label><br>
                                        <input type="radio" id="no" <?php if (isset($cons) && $cons=="no") echo "checked";?> name="cons" value="no">
                                        <label for="no"> Non, je ne partage pas mes données anonymes à des fins de recherche.</label><br>
                                        <span style="color: red;font-size: 12px;"><?php echo $cons_err; ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"></td>
                            <td style="text-align: justify;">Bien entendu, vous pouvez également participer à l'activité d'apprentissage si vous ne souhaitez pas partager vos données avec nous!</td>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td valign="top"></td>
                            <td>Maintenant, nous vous souhaitons beaucoup de plaisir au Chemlab.</td>
                            <td style="text-align: right;">
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary" value="Continuer" onclick="return completeAndRedirect();">
                                </div>
                            </td>
                        </tr>
                    </table>
                </form>
                <script>
                    function completeAndRedirect() {
                        var r = confirm("Êtes-vous sûr(e) de vouloir procéder?");

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