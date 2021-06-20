<?php
 
// Include config file
require_once "../config.php";

// Define variables and initialize with empty values
$username = "";
$username_err = "";

$login_err = "";
$login_success = "";

$duration = 60 * 60 * 24 * 31;
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Bitte geben Sie einen Code ein.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Validate credentials
    if(empty($username_err)){
        // Prepare a select statement
        $sql = "SELECT created_at FROM users WHERE username = ?";
        
        if($stmt = $link->prepare($sql)){
            // Set parameters
            $param_username = $username;

            // Bind variables to the prepared statement as parameters
            $stmt->bind_param('s', $param_username);

            // Attempt to execute the prepared statement
            if($stmt->execute()){
               	$result = $stmt->get_result();
                // Check if username exists, if yes then allow through
                if($result->num_rows == 1){  
                    $datetime = $result->fetch_assoc()["created_at"];
                    $exp_time = DateTime::createFromFormat("Y-m-d H:i:s", $datetime)->getTimestamp(); 

                    if (time() > $duration + $exp_time) {
                        $login_err = "Da seit dem Experiment mehr als 31 Tage vergangen sind, wurde der Datenbankeintrag, der diesen Code mit den zugehörigen Daten verknüpft, entfernt. Daher ist es nicht mehr möglich, die mit diesem Code verbundenen Daten zu löschen. Die Daten können jedoch als vollständig anonymisiert betrachtet werden, da sie keinerlei persönliche Informationen enthalten.";
                    }
                    else {
                        $sql2 = "SELECT id, username FROM wipe WHERE username = ?";

                    	if($stmt2 = mysqli_prepare($link, $sql2)){
    			            // Bind variables to the prepared statement as parameters
    			            mysqli_stmt_bind_param($stmt2, "s", $param_username);
    			            
    			            // Attempt to execute the prepared statement
    			            if(mysqli_stmt_execute($stmt2)){
    			                // Store result
    			                mysqli_stmt_store_result($stmt2);
    			                
    			                // Check if username exists, if yes then allow through
    			                if(mysqli_stmt_num_rows($stmt2) == 1){
    			                	$login_err = "Die Daten für diesen Code wurden bereits gelöscht.";
    			                } else {
                    				$sql3 = "INSERT INTO wipe (username) VALUES (?)";

                    				if($stmt3 = mysqli_prepare($link, $sql3)){
    			                        // Bind variables to the prepared statement as parameters
    			                        mysqli_stmt_bind_param($stmt3, "s", $param_username);
    			                        
    			                        // Attempt to execute the prepared statement
    			                        if (mysqli_stmt_execute($stmt3)) {
    			                			$login_success = "Die Daten für diesen Code wurden erfolgreich gelöscht.";
    			                        } else{
    			                            echo "Oops! Something went wrong. Please try again later.";
    			                        }

    			                        // Close statement
    			                        mysqli_stmt_close($stmt3);
    			                    }
    			                }
    			            }

                    		mysqli_stmt_close($stmt2);
    			        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Der eingegebene Code ist ungültig.";
                }
            } else{
                echo "Ups! Etwas ist schief gegangen. Bitte probieren Sie es später nochmal.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EPFL D-VET - Chemlab - Data deletion</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Chemlab Daten löschen</h2>
        <p>Liebe Eltern,
        Mit Hilfe dieses Formulars können Sie die Löschung der Daten Ihres Kindes im Zusammenhang mit der Chemlab Forschungsstudie veranlassen. Geben Sie dazu den Zufallscode ein, welcher sich auf dem Informationsblatt befindet. Die Daten Ihres Kindes werden dann unverzüglich gelöscht.
        </p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }   

        if(!empty($login_success)){
            echo '<div class="alert alert-danger">' . $login_success . '</div>';
        }  
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Abschicken">
            </div>
        </form>
    </div>
</body>
</html>