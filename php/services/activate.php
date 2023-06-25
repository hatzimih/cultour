<?php
//
// insert new user's info in the online database
//
	include 'connect.php';
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		//exit('Failed to connect to MySQL: ' . mysqli_connect_error());
		echo json_encode(array("statusCode"=>206));
	}

    if (is_get_request()) {
        if ( !isset($_GET['email']) || !isset($_GET['activation_code'])) {
            // Could not get the data that should have been sent.
            //exit('Please fill both the username and password fields!');
            echo json_encode(array("statusCode"=>201));
        }
        $user = find_unverified_user($_GET['activation_code'], $_GET['email']);
        // if user exists and activate the user successfully
        if ($user && activate_user($user['id'])) {
            linkWithMessage(
                APP_ROOT."index.html",
                'You account has been activated successfully. You can now login in our home page following the link below.'
            );
        }
        exit(0);
    }
    // redirect to the register page in other cases
    linkWithMessage(
        'register.php',
        'The activation link is not valid, please register again.'
    );

    function is_get_request(): bool {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }

    function activate_user(int $user_id): bool{
        global $conn;
        $sql = 'UPDATE users
                SET active = 1,
                    activated_at = CURRENT_TIMESTAMP
                WHERE id='.$user_id;
        $statement = $conn->prepare($sql);
        return $statement->execute();
    }

    function linkWithMessage($url, $msg){
        echo "<span>".$msg."</span><br>";
        echo "<a href='".$url."'>Home page</a>";
    }

    function find_unverified_user(string $activation_code, string $email) {
        global $conn;
        $sql = "SELECT id, activation_code, activation_expiry < now() as expired
                FROM users
                WHERE active = 0 AND username='".$email."'";
        $result = $conn->query($sql);
        /* If there are results from database push to result array */
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // already expired, delete the in active user with expired activation code
            if ((int)$user['expired'] === 1) {
                delete_user_by_id($user['id']);
                return null;
            }
            // verify the password
            //if (password_verify($activation_code, $user['activation_code'])) {
            if ($activation_code===$user['activation_code'])
                return $user;
            else { 
                linkWithMessage(APP_ROOT."index.html", "Incorrect activation code..");
                return null;
            }
        }
        linkWithMessage(APP_ROOT."index.html", "No registered and unverified user has declated this email so far!");
        return null;
    }

    function delete_user_by_id(int $id, int $active = 0) {
        global $conn;
        $sql = 'DELETE FROM users
                WHERE id ='.$id.' and active='.$active;
        $statement = $conn->prepare($sql);
        return $statement->execute();
    }
    
?>