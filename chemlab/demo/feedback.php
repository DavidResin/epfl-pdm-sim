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
$stmt = $link->prepare("SELECT progress FROM users WHERE id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc()["progress"];

$stmt = $link->prepare("SELECT consent FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$consent = $stmt->get_result()->fetch_assoc()["consent"];

$stmt = $link->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$username = $stmt->get_result()->fetch_assoc()["username"];

$t_good = "";
$t_good_err = "";
$t_bad = "";
$t_bad_err = "";
$t_what = "";
$t_what_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {

  if ($consent) {
    $s_entertain = $_POST["s_entertain"];
    $s_difficult = $_POST["s_difficult"];

    $t_good = $_POST["t_good"];
    $t_bad = $_POST["t_bad"];
    $t_what = $_POST["t_what"];
  }

  $pass = true;

  if ($pass) {
    $sql = "UPDATE users SET progress = ? WHERE id = ?";

    // Prepare statement
    $stmt = $link->prepare($sql);
    $progress = $progress + 1;
    $stmt->bind_param('ss', $progress, $id);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/11.0.2/css/bootstrap-slider.min.css">
    <link rel="stylesheet" href="css/layout.css">
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/11.0.2/bootstrap-slider.min.js"></script>
    <style>
      body{ font: 14px sans-serif; }

      .wrapper{ width: 350px; padding: 20px; }

      .slider-selection {
        background: #BABABA;
      }

      movable {
        cursor:move;
      }
    </style>
  </head>
  <body>
    <div class="base-container">
      <div class="content-container">
        <form id="myForm" action="javascript:void(0);" method="post">
        <table>
          <tr>
            <td colspan="2" style="width: 100%">
              <h1 style="font-weight: bold">Feedback</h1>
            </td>
          </tr>

          <tr>
            <td colspan="2">
              <h3>Fanden sie die Aufgaben eher langweilig (0) oder eher spannend (100)?</h3>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input id="s_entertain" name="s_entertain" data-slider-id='s_entertain' class="mySlider" style="width:100%" type="text" data-slider-min="0" data-slider-max="100" data-slider-step="5" data-slider-value="0"/>
            </td>
          </tr>
          <script>
            var slider = new Slider('#s_entertain', {
              formatter: function(value) {
                return 'Aktueller Wert: ' + value;
              }
            });
          </script>

          <tr>
            <td colspan="2">
              <h3>Fanden sie die Aufgaben eher einfach (0) oder eher schwierig (100)?</h3>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input id="s_difficult" name="s_difficult" data-slider-id='s_difficult' class="mySlider" style="width:100%" type="text" data-slider-min="0" data-slider-max="100" data-slider-step="5" data-slider-value="0"/>
            </td>
          </tr>
          <script>
            var slider = new Slider('#s_difficult', {
              formatter: function(value) {
                return 'Aktueller Wert: ' + value;
              }
            });
          </script>

          <tr>
            <td colspan="2">
              <h3>Was fanden Sie besonders gut?</h3>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <textarea type="text" name="t_good" id="t_good" rows="3" class="form-control <?php echo (!empty($t_good_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $t_good; ?>"></textarea><br>
              <span style="color: red;font-size: 12px;"><?php echo $t_good_err; ?></span>
            </td>
          </tr>

          <tr>
            <td colspan="2">
              <h3>Was fanden Sie besonders schlecht?</h3>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <textarea type="text" name="t_bad" id="t_bad" rows="3" class="form-control <?php echo (!empty($t_bad_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $t_bad; ?>"></textarea><br>
              <span style="color: red;font-size: 12px;"><?php echo $t_bad_err; ?></span>
            </td>
          </tr>

          <tr>
            <td colspan="2">
              <h3>Wie beurteilen Sie Ihre eigene Lösungsstrategie? Würden Sie bei einer ähnlichen Fragestellung wieder gleich vorgehen? Falls nein, was könnten Sie besser machen? Falls ja, wieso hat sich Ihr Vorgehen bewährt?</h3>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <textarea type="text" name="t_what" id="t_what" rows="3" class="form-control <?php echo (!empty($t_what_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $t_what; ?>"></textarea><br>
              <span style="color: red;font-size: 12px;"><?php echo $t_what_err; ?></span>
            </td>
          </tr>
          
          <tr style="height: 25px"></tr>
          <tr>
              <td colspan="2" style="text-align: right;">
                  <div class="form-group">
                      <input type="submit" class="btn btn-primary" value="Weiter" onclick="return completeAndRedirect();">
                  </div>
              </td>
          </tr>
        </table>
        </form>
        <script>
            function completeAndRedirect() {
                var r = confirm("Ist das Ihre entgültige Antwort? Es ist nicht möglich später zurückzukehren!");

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