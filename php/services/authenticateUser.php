<?php
//
// Check user's login credentials based on info stored in the online database
//
	session_start();
	include('connect.php');
	// Try and connect using the info above.
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		echo json_encode(array("statusCode"=>201));
	}
	// Now we check if the data from the login form was submitted, isset() will check if the data exists.
	$_POST = json_decode(file_get_contents("php://input"),true); //if  axios posts
	if ( !isset($_POST['username'], $_POST['password']) ) {
		// Could not get the data that should have been sent.
		echo json_encode(array("statusCode"=>202));
		exit(-1);
	}
	// Prepare our SQL, preparing the SQL statement will prevent SQL injection.
	if ($stmt = $conn->prepare('SELECT id, password, user_type, lang FROM users WHERE username = ? OR email=?')) {
		// Bind parameters (s = string, i = int, b = blob, etc), in our case the username is a string so we use "s"
		$stmt->bind_param('ss', $_POST['username'],$_POST['username']);
		$stmt->execute();
		// Store the result so we can check if the account exists in the database.
		$stmt->store_result();
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($id, $password,$user_type, $lang);
			$stmt->fetch();
			// Account exists, now we verify the password.
			// Note: remember to use password_hash in your registration file to store the hashed passwords.
			if ($_POST['password'] == $password) {
			//if (password_verify($_POST['password'], $password)) {
				// Verification success! User has logged-in!
				// Create sessions, so we know the user is logged in, they basically act like cookies but remember the data on the server.
				session_regenerate_id();
				$_SESSION['loggedin'.$user_type] = TRUE;
				$_SESSION['name'] = $_POST['username'];
				$_SESSION['id'] = $id;
				$_SESSION['user_type'] = $user_type;
				echo json_encode(array("statusCode"=>200, "user_id"=>$id, "user_type"=>$user_type, "user_lang"=>$lang));
			} else {
				// Incorrect password
				echo json_encode(array("statusCode"=>203));
			}
		} else {
			// Incorrect username
			echo json_encode(array("statusCode"=>204));
		}
		$stmt->close();
	}	
	mysqli_close($conn);

	function generate_activation_code(): string{
		return bin2hex(random_bytes(16));
	}
	
	function send_activation_email(string $email, string $activation_code): void{
		// create the activation link
		$activation_link = APP_URL . "/activate.php?email=$email&activation_code=$activation_code";
	
		// set email subject & body
		$subject = 'Please activate your account';
		$message = <<<MESSAGE
				Hi,
				Please click the following link to activate your account:
				$activation_link
				MESSAGE;
		// email header
		$header = "From:" . SENDER_EMAIL_ADDRESS;
	
		// send the email
		mail($email, $subject, nl2br($message), $header);
	
	}
?>