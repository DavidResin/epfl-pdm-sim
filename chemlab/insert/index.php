<?php
// Include config file
require_once "../config.php";
 
// Define variables and initialize with empty values
$username = "";
$username_err = $alert = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter usernames.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Validate credentials
    if(empty($username_err)){
        $names = explode("\n", $username);
        $new = 0;

        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        $sql2 = "INSERT INTO users (username) VALUES (?)";

        for ($i = 0; $i < count($names); $i++) {
        
            if($stmt = mysqli_prepare($link, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                
                // Set parameters
                $param_username = trim($names[$i]);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Store result
                    mysqli_stmt_store_result($stmt);
                    
                    // Check if username exists, if yes then verify password
                    if(mysqli_stmt_num_rows($stmt) == 0){
                        // Username doesn't exist, display a generic error message
                        $new += 1;

                        if($stmt2 = mysqli_prepare($link, $sql2)){
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($stmt2, "s", $param_username);
                            
                            // Attempt to execute the prepared statement
                            if (mysqli_stmt_execute($stmt2)) {
                            } else{
                                echo "Oops! Something went wrong. Please try again later.";
                            }

                            // Close statement
                            mysqli_stmt_close($stmt2);
                        }
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }

        $alert = "Added " . $new . " new usernames.";
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EPFL D-VET - Chemlab - Populate</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Populate</h2>
        <p>Please provide usernames to populate the database.</p>

        <?php 
        if(!empty($alert)){
            echo '<div class="alert alert-danger" style="text-color:green">' . $alert . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <textarea type="text" name="username" rows="20" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>"></textarea>
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>
            <p><a href="../dispatch.php">Go to the experiment</a></p>
        </form>
    </div>
</body>
</html>