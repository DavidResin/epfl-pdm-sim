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

$offset = 3;

if ($progress - $offset < 0 || $progress - $offset > 11) {
  header("location: dispatch.php");
}

$contents = json_decode(file_get_contents("files/q-de.json"), true);
$q_idx = min($progress - $offset, 10);
$entry = $contents[strval($q_idx)];

$d_text = $entry["text"];
$d_imgs = $entry["imgs"];
$d_ans = $entry["ans"];
$d_conf = $entry["conf"];

$at = "";
$at_err = "";
$slider_err = "";
$choice_err = "";

switch ($d_ans) {
  case 'rank':
    $d_bnds = $entry["labels"];
    $d_choices = $entry["choices"];
    break;
  case 'sliders':
    $d_sliders = $entry["sliders"];
    break;
  case 'checkboxes':
    $d_choices = $entry["choices"];
    break;
  case 'choices':
    $d_choices = $entry["choices"];
    $d_labels = $entry["labels"];
  default:
    break;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {

  if ($d_ans != "rank") {

    $a_choices = array();
    $a_sliders = array();
    $a_text = "";
    $a_conf = "";
    $a_ranks = array();
    $sum_sliders = 100;
    $empty_choice = false;

    switch ($d_ans) {
      case 'text':
        $a_text = $_POST["ans_text"];
        break;
      case 'sliders':
        $sum_sliders = 0;

        for ($i=0; $i<4; $i++) {
          $a_sliders[$i] = $_POST["s" . $i];
          $sum_sliders += $a_sliders[$i];
        }
        break;
      case 'checkboxes':
        for ($i=0; $i<4; $i++) {
          $a_choices[$i] = $_POST["t" . $i];

          echo "<script>console.log('" . $_POST["c" . $i] . "')</script>";

          if ($_POST["c" . $i] == $d_choices[$i] && $_POST["t" . $i] == "") {
            $empty_choice = true;
          }
        }
        break;
      case 'choices':
        for ($i=0; $i<3; $i++) {
          $a_choices[$i] = $_POST["x" . $i];

          if ($a_choices[$i] == "") {
            $empty_choice = true;
          }
        }
        break;
      default:
        break;
    }

    if ($d_conf) {
        $a_conf = $_POST["conf"];
    }

    if ($consent) {
    }
  }

  if ($d_ans == "sliders" && $sum_sliders != 100) {
      $slider_err = "Die Summe der Gewichte muss 100 ergeben.";
  } else if ($d_ans == "choices" && $empty_choice) {
      $choice_err = "Sie m??ssen f??r jeden Becher eine Antwort abgeben.";
  } else if ($d_ans == "checkboxes" && $empty_choice) {
      $choice_err = "Bitte geben Sie eine kurze Beschreibung f??r jede ausgew??hlte Option an.";
  } else {
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
              <h1 style="font-weight: bold">Frage X</h1>
            </td>
          </tr>
          <?php if (count($d_imgs) == 2): ?>
          <tr>
            <td colspan="2" style="text-align:center">
              <img style="width:49%" src="files/img/<?php echo $progress - $offset;?>-1.png">
              <img style="width:49%" src="files/img/<?php echo $progress - $offset;?>-2.png">
            </td>
          </tr>
          <?php endif ?>
          <?php if (count($d_imgs) == 1): ?>
          <tr>
            <td colspan="2" style="text-align: center;">
              <img width="50%" src="files/img/<?php echo min($progress - $offset, 10);?>.png">
            </td>
          </tr>
          <?php endif ?>
          <tr style="height: 25px"></tr>
          <tr>
            <td colspan="2">
              <h4><?php echo $d_text; ?></h4>
            </td>
          </tr>
          <?php if ($d_ans == "text"): ?>
            <tr>
              <td colspan="2">
                <textarea type="text" name="ans_text" id="ans_text" rows="5" class="form-control <?php echo (!empty($at_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $at; ?>"></textarea><br>
                <span style="color: red;font-size: 12px;"><?php echo $at_err; ?></span>
              </td>
            </tr>
          <?php endif ?>
          <?php if ($d_ans == "rank"): ?>
            <tr style="height: 25px"></tr>
            <tr>
              <td style="padding:0;height:100%;">
                <div style="height:100%; display: flex;flex-direction: column;justify-content: space-between;">
                  <?php
                    for ($i=0, $len=count($d_bnds); $i<$len; $i++) {
                      echo "<div style='font-size:18px;text-align:center;'>" . $d_bnds[$i] . "</div>";
                    }
                  ?>
                </div>
              </td>
              <td style="height:100%;width:100%;border: solid 1px black">
                <table style="height:100%;" class="table table-hover" id="ranking">
                  <tbody>
                    <?php
                      for ($i=0, $len=count($d_choices); $i<$len; $i++) {
                        echo "<tr id='" . $i . "'><td class='movable'>" . $d_choices[$i] . "</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </td>
            </tr>
            <script>
              var fixHelperModified = function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                  $(this).width($originals.eq(index).width())
                });
                return $helper;
              }

              var updateIndex = function(e, ui) {
                  $('td.index', ui.item.parent()).each(function (i) {
                    $(this).html(i+1);
                  });
                  $('input[type=text]', ui.item.parent()).each(function (i) {
                    $(this).val(i + 1);
                  });
                };

              $("#ranking tbody").sortable({
                helper: fixHelperModified,
                stop: updateIndex
              }).disableSelection();

              $("#ranking tbody").sortable({
                distance: 5,
                delay: 100,
                opacity: 0.6,
                cursor: 'move',
                update: function() {}
              });
            </script>
          <?php endif ?>
          <?php if ($d_ans == "sliders"): ?>
            <tr style="height: 25px"></tr>
            
              <?php 
                for ($i=0; $i<4; $i++) {
                  echo  '<tr><td>' . $d_sliders[$i] . '<input id="s' . $i . '" name="s' . $i . '" data-slider-id=s' . $i . ' class="mySlider" style="width:100%" type="text" data-slider-min="0" data-slider-max="100" data-slider-step="5" data-slider-value="0"/></td></tr>';
                }

                if(!empty($slider_err)){
                    echo '<tr><td><span style="color: red;font-size: 12px;">' . $slider_err . '</span></td></tr>';
                }
              ?>
            
            <script>
              sliders = [];

              for (let i = 0; i < 4; i++) {
                var slider = new Slider('#s' + i, {
                  formatter: function(value) {
                    return 'Aktueller Wert: ' + value;
                  }
                });

                sliders.push(slider);
              }
            </script>
          <?php endif ?>
          <?php if ($d_ans == "checkboxes"): ?>
            <?php 
                for ($i=0; $i<4; $i++) {
                  echo '<tr><td><input type="checkbox" id="c' . $i . '" name="c' . $i . ' value="' . $d_choices[$i] .'" onclick="var input = document.getElementById(\'t' . $i . '\'); if(this.checked){ input.disabled = false; input.focus();}else{input.disabled=true;}"/> <label for="c' . $i . '">' . $d_choices[$i] . '</label></td>';
                  echo '<td><textarea style="width:100%" type="text" name="t' . $i . '" id="t' . $i . '" rows="5" disabled="disabled"/></textarea></td></tr>';
                }


                if(!empty($choice_err)){
                    echo '<tr><td colspan="2"><span style="color: red;font-size: 12px;">' . $choice_err . '</span></td></tr>';
                }
              ?>
          <?php endif ?>
          <?php if ($d_ans == "choices"): ?>
            <tr style="height: 25px"></tr>
            <tr><td>
              <table>
            
              <?php 
                for ($i=0; $i<3; $i++) {
                  echo  '<tr><td>' . $d_labels[$i] . '&nbsp</td><td><select name="x'.$i.'">';
                  echo '<option value="" selected></option>';

                  for ($j=0; $j<count($d_choices); $j++) {
                    echo '<option value="'. $d_choices[$j] .'">' . $d_choices[$j] . '</option>';
                  }

                  echo '</select><br/><br/></td></tr>';
                }

                if(!empty($choice_err)){
                    echo '<tr><td colspan="2"><span style="color: red;font-size: 12px;">' . $choice_err . '</span></td></tr>';
                }
              ?>
              </table>
            </td></tr>
            <script>
              sliders = [];

              for (let i = 0; i < 4; i++) {
                var slider = new Slider('#s' + i, {
                  formatter: function(value) {
                    return 'Aktueller Wert: ' + value;
                  }
                });

                sliders.push(slider);
              }
            </script>
          <?php endif ?>
          <?php if ($d_conf == true): ?>
            <tr style="height: 25px"></tr>
            <tr>
              <td colspan="2"><h4>Wie sicher sind Sie sich bei Ihrer Antwort? (0-100%)</h4></td>
            </tr>
            <tr>
              <td colspan="2">
                <input id="conf" name="conf" data-slider-id='conf' class="mySlider" style="width:100%" type="text" data-slider-min="0" data-slider-max="100" data-slider-step="5" data-slider-value="0"/>
              </td>
            </tr>
            <script>
              var slider = new Slider('.mySlider', {
                formatter: function(value) {
                  return 'Aktueller Wert: ' + value;
                }
              });
            </script>
          <?php endif ?>
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
                var r = confirm("Ist das Ihre entg??ltige Antwort? Es ist nicht m??glich sp??ter zur??ckzukehren!");

                if (r == true) {
                    <?php if ($consent && $d_ans == "rank"): ?>

                    jQuery.ajax({
                      type: "POST",
                      url: 'log_quiz.php',
                      dataType: 'json',
                      data: {functionname: 'log_data', arguments: ['<?php echo $username; ?>', '<?php echo $progress; ?>', $("#ranking").children().sortable('toArray', { attribute: 'id'})]},

                      success: function (obj, textstatus) {
                        if( !('error' in obj) ) {
                          yourVariable = obj.result;
                          console.log(yourVariable);
                        }
                        else {
                          console.log(obj.error);
                        }
                      }
                    });

                    <?php endif ?>

                    document.getElementById("myForm").action = "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>";
                    document.getElementById("myForm").submit();
                }
            }
        </script>
      </div>
    </div>
  </body>
</html>