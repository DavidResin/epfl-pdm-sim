<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
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

$username = "";
$username_err = "";

$stop = false;

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is empty
    if (empty($_POST["username"])) {
        $username_err = "Um fortzufahren, müssen Sie eine Antwort eingeben.";
    } else{
        $username = $_POST["username"];
    }

    if(empty($username_err)){
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $username);

            if ($username == "over18") {
                echo "HELLO WORLD";
                do {
                    $username = generateRandomString(8);

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_store_result($stmt);
                    } else {
                        $stop = true;
                        echo "Oops! Something went wrong. Please try again later.";
                    }

                } while (mysqli_stmt_num_rows($stmt) == 1 && !$stop);

                if (!$stop) {
                    $sql = "INSERT INTO users (username, progress, test) VALUES (?, ?, ?)";

                    if($stmt = mysqli_prepare($link, $sql)){
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt, "sii", $param_username, $param_progress, $param_test);
                        
                        // Set parameters
                        $param_username = $username;
                        $param_progress = 0;
                        $param_test = 1;
                        
                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($stmt)) {
                        } else{
                            echo "Oops! Something went wrong. Please try again later.";
                        }

                        // Close statement
                        mysqli_stmt_close($stmt);
                    }
                }
            }
            else {
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 0) {
                        $stop = true;
                        $username_err = "Username does not exist.";
                    }
                } else {
                    $stop = true;
                    echo "Oops! Something went wrong. Please try again later.";
                }
            }
        }

        

        if (!$stop) {
            

            $sql = "SELECT id, username FROM users WHERE username = ?";
        
            if($stmt = mysqli_prepare($link, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                
                // Set parameters
                $param_username = $username;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Store result
                    mysqli_stmt_store_result($stmt);
                    
                    // Check if username exists, if yes then allow through
                    if(mysqli_stmt_num_rows($stmt) == 1){   
                        mysqli_stmt_bind_result($stmt, $id, $username);
                        if(mysqli_stmt_fetch($stmt)){
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to welcome page
                            header("location: dispatch.php");
                        }
                    } else{
                        // Username doesn't exist, display a generic error message
                        $username_err = "Invalid username.";
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
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
                                <p>Herzlich Willkommen im Chemlab! Hier wird Theorie zum Leben erweckt. Ergründen Sie naturwissenschaftliche Phänomene, indem Sie selber Versuche durchführen. Um mit den Lernaktivitäten zu beginnen, geben Sie bitte den Code ein, den Sie von Ihrer Lehrperson erhalten haben:</p>
                            </td>
                        </tr>
                        <tr style="height: 25px"></tr>
                        <tr>
                            <td style="width: 50px"></td>
                            <td>
                                <div class="form-group">
                                    <label>Code</label>
                                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                </div>
                            </td>
                        </tr>
                        <tr style="height: 10px"></tr>
                        <tr>
                            <td valign="top"></td>
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