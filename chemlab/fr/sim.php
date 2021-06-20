<?php
  // Initialize the session
  session_start();

  // Include config file
  require_once "config.php";
   
  // Check if the user is logged in, otherwise redirect to login page
  if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
      header("location: dispatch.php");
      exit;
  }

  $id = $_SESSION["id"];
  $stmt = $link->prepare("SELECT progress FROM users WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $progress = $stmt->get_result()->fetch_assoc()["progress"];

  $stmt = $link->prepare("SELECT consent FROM users WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $consent = $stmt->get_result()->fetch_assoc()["consent"];

  $pdf = 0;
  $title = "";
  $end = "Next";
  $state = $progress;
  $username = $_SESSION["username"];

  if ($progress == 2) {
    $pdf = 1;
    $title = "Activité 1";
    $state = 1;
  }
  elseif ($progress == 3) {
    $pdf = 2;
    $title = "Activité 2";
    $state = 2;
  }
  elseif ($progress == 14) {
    $pdf = 3;
    $title = "Activité 3";
    $state = 3;
  }
  else {
    header("location: dispatch.php");
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "UPDATE users SET progress = ? WHERE id = ?";

    if ($consent) {
      $fn = "../logs/" . $username . '-q.json';
      $jsonString = file_get_contents($fn);
      $data = json_decode($jsonString, true);
      $data[$progress] = array("time" => time());
      $newJsonString = json_encode($data);
      file_put_contents($fn, $newJsonString);
    }

    // Prepare statement
    $stmt = $link->prepare($sql);
    $progress = $progress + 1;
    $stmt->bind_param('ss', $progress, $id);

    // execute the query
    $stmt->execute();
    header('Location: dispatch.php');  
  }
?>

<html>
  <head>
    <meta charset="UTF-8">
	  <title>EPFL D-VET - Chemlab</title>
	  <script type="text/javascript" src="js/phetio/assert.js"></script>
	  <script type="text/javascript" src="js/phetio/SimIFrameClient.js"></script>
	  <script type="text/javascript" src="js/phetio/WrapperUtils.js"></script>
	  <script type="text/javascript" src="js/phetio/phetio.js"></script>
    <script type="text/javascript" src="js/index.js"></script>
	  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/underscore@1.12.1/underscore-min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="css/layout.css">
  </head>
  <body style="margin:0">
    <div style="position:absolute;width:100%;height:100%;z-index: 0height:100%;background-color: darkgray;display:flex">
     	<div style="flex-grow: 1"></div>
     	<div style="flex-grow: 6;display:flex;flex-direction: column">
     		<div style="flex-grow: 1;color:white;display:flex;justify-content: center;align-items: center;font-size: 60;font-family: 'Helvetica'">
          <?php echo $title; ?>
        </div>
	      <div style="background-color: green;flex-grow: 6;display:flex;">
	      	<iframe style="width: 100%; height: 100%;" id="sim" src="https://phet-io.colorado.edu/sims/beers-law-lab/1.6.15-phetio/beers-law-lab_en-phetio.html?production&amp;phetioEmitStates=false&amp;phetioEmitInputEvents=false&amp;sim=beers-law-lab"></iframe>
          <script>
            var sim = WrapperUtils.getSim( 'beers-law-lab', '1.6.15-phetio' );
            var simIFrameClient = new SimIFrameClient( document.getElementById( 'sim' ) );

            simIFrameClient.launchSim( sim.URL, {
              onPhETiOInitialized: function() {
                simIFrameClient.invoke( 'phetio', 'addPhETIOEventsListener', [ function( message ) {
                  <?php echo $consent ? "log(message);" : ""; ?>
                }]);
              }
            });
          </script>
        </div>
	      <div style="flex-grow: 1"></div>
      </div>
      <div style="flex-grow: 1"></div>
    </div>
    <div style="position:absolute;width:100%;height:100%;z-index: 1;pointer-events: none;">
      <div id="side" class="close" style="height:100%;background-color: grey;float: right;pointer-events: auto;transition: .3s;display:flex">
        <div style="display:flex;justify-content:space-between;flex-direction:column;width:100px;text-align: center;padding:20px 0 80px 0;">
          <div>
            <button style="width:90%;font-stretch: condensed;" class="btn btn-primary" id="pdf_show">Instructions</button>
          </div>
          <div></div>
          <div>
            <form id="myForm" action="javascript:void(0);" method="post">
              <div class="form-group">
                <input type="submit" style="width:90%" class="btn btn-primary" value="Continuer" onclick="return completeAndRedirect();">
              </div>
            </form>
          </div>
        </div>
        <div id="pdf" style="flex-grow: 1;display:none">
          <embed src="files/fr/<?php echo $pdf; ?>.pdf#view=FitH" type="application/pdf" width="100%" height="100%"/>
        </div>
      </div>
    </div>
    <script>
      function completeAndRedirect() {
          var r = confirm("Êtes-vous sûr(e) de vouloir procéder? Il n'est pas possible de revenir en arrière!");

          if (r == true) {
              document.getElementById("myForm").action = "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>";
              document.getElementById("myForm").submit();
          }
      }

      function log(message) {
        jQuery.ajax({
          type: "POST",
          url: 'log_sim.php',
          dataType: 'json',
          data: {functionname: 'log_data', arguments: ["<?php echo $username; ?>", "<?php echo $state; ?>", message + "\n"]},

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
      }

      function custom_entry(event) {
        date = new Date()

        message = {
          "messageIndex": null,
          "eventType": "user",
          "phetioID": "beersLawLab.wrapper",
          "componentType": "WrapperButton",
          "event": event,
          "time": date.getTime(),
          "parameters": null,
        }

        log(JSON.stringify(message, null, 2));
      }
      
      $("#pdf_show").click(function(){
        var elem = $(this);
        var flag = elem.data("state") || false;

        $("#pdf").css("display", flag ? "none" : "block");

        if (flag) {
          $("#side").addClass('close').removeClass('open');
        } else {
          $("#side").addClass('open').removeClass('close');
        }
        elem.data("state", !flag);

        var action = flag ? "hide" : "show";
        <?php if ($consent): ?>
        custom_entry(action + "PDF");
        <?php endif ?>
      });
    </script>
  </body>
</html>