<?php
//
// insert new user's info in the online database
//
	include 'connect.php';
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	//Import PHPMailer classes into the global namespace
	require("PHPMailer/PHPMailer.php");
	require("PHPMailer/SMTP.php");
	require("PHPMailer/Exception.php");


	//Load Composer's autoloader
	//require 'vendor/autoload.php';
	
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		//exit('Failed to connect to MySQL: ' . mysqli_connect_error());
		echo json_encode(array("statusCode"=>206));
	}
	if ( !isset($_POST['username']) ) {
		// Could not get the data that should have been sent.
		//exit('Please fill both the username and password fields!');
		echo json_encode(array("statusCode"=>201));
	}

	$username=$_POST['username'];
	$email=$_POST['email'];
	$password=$_POST['password'];
	$country=$_POST['country'];
	$lang=$_POST['lang'];
	$gender=$_POST['gender'];
	$birthyear=$_POST['birthyear'];
	$user_type=0;
	$active=0;
	$activation_code = generate_activation_code();

	if (strlen($username)) 
		if (strlen($email)) 
			$sqlunique = "SELECT id password FROM users WHERE username = '". $username."' OR email = '".$email ."'";
		else {
			$sqlunique = "SELECT id password FROM users WHERE username = '". $username."'";
			$email="";
		}
	else {
		$sqlunique = "SELECT id password FROM users WHERE email = '".$email ."'";
		$username="";
	}
	//echo $sqlunique;
	if ($stmt = $conn->prepare($sqlunique)) {
		// Bind parameters (s = string, i = int, b = blob, etc), hash the password using the PHP password_hash function.
		//$stmt->bind_param('ss', $username,$email);
		$stmt->execute();
		$stmt->store_result();
		// Store the result so we can check if the account exists in the database.
		if ($stmt->num_rows > 0) {
			// Username already exists
			//echo 'Username exists, please choose another!';
			echo json_encode(array("statusCode"=>202));
		} else {
			// Insert new account// Username doesnt exists, insert new account
			//INSERT INTO users VALUES(DEFAULT, 
			//'${username}'', '${password}', user_type, '${country}', '${lang}', ${gender}, ${birthyear}`
			//comments=NULL, created_at, active, '${activation_code}';
			if ($stmt = $conn->prepare('INSERT INTO users VALUES (DEFAULT, ?, ?, ?, 0, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP, 0, ?, TIMESTAMPADD(HOUR, 24, CURRENT_TIMESTAMP), NULL)')) {
				// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
				//$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
				//$activation_code = password_hash($activation_code, PASSWORD_DEFAULT);
				$stmt->bind_param('sssssiis', $username, $email, $password, $country, $lang, $gender, $birthyear, $activation_code);
				if ($stmt->execute()) {
					$new_user_id = mysqli_insert_id($conn);
					$conf_mail_msg = send_activation_PHPMail($username, $activation_code);
					session_start();
					$_SESSION['loggedin'.$user_type] = TRUE;
					$_SESSION['name'] = $_POST['username'];
					$_SESSION['id'] = $new_user_id;
					$_SESSION['user_type'] = $user_type;
					echo json_encode(array("statusCode"=>200, "id"=> $new_user_id, "statusEmail"=>$conf_mail_msg));
				} else echo json_encode(array("statusCode"=>203));
			} else {
				// Something is wrong with the sql statement, check to make sure accounts table exists with all 3 fields.
				echo json_encode(array("statusCode"=>204));
			}
		}
		$stmt->close();
	} else {
		// Something is wrong with the sql statement, check to make sure accounts table exists with all 3 fields.
		echo json_encode(array("statusCode"=>205));
	}
	mysqli_close($conn);

	function generate_activation_code(): string{
		return bin2hex(random_bytes(16));
	}
/*
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
*/
	function send_activation_PHPMail(string $email, string $activation_code) {
		//Create an instance; passing `true` enables exceptions
		//$mail = new PHPMailer(true);
		$mail = new \PHPMailer\PHPMailer\PHPMailer();
		$activation_link = APP_URL . "/activate.php?email=$email&activation_code=$activation_code";
		try {
			//Server settings
			//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			//$mail->isSMTP();                                            //Send using SMTP
			//$mail->Host       = 'smtp.example.com';                     //Set the SMTP server to send through
			//$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			//$mail->Username   = 'user@example.com';                     //SMTP username
			//$mail->Password   = 'secret';                               //SMTP password
			//$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
			//$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			//$mail->SMTPDebug  = 1;  
			//$mail->SMTPAuth   = TRUE;
			//$mail->SMTPSecure = "tls";
			//$mail->Port       = 587;
			//$mail->Host       = "smtp.gmail.com";
			//$mail->Username   = "hatzimih2@gmail.com";
			//$mail->Password   = DPW;

			//Recipients
			$mail->addAddress($email); //Name is optional
			$mail->setFrom('from@example.com', 'Mailer',0);
			//$mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
			//$mail->addReplyTo('info@example.com', 'Information');
			//$mail->addCC('cc@example.com');
			//$mail->addBCC('bcc@example.com');

			//Attachments
			//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
			//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

			//Content
			$mail->isHTML(true);                                  //Set email format to HTML
			$mail->Subject = 'Important!Please activate your account';
			$mail->Subject = 'dpl2022->Important!Please activate your account';
			$mail->Body    = 'Hi,<br>Please click the following link to activate your account <b>within the next 24hours</b>:'.$activation_link;

			//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			$mail->send();
			return 'Message has been sent';
		} catch (Exception $e) {
			return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	}
?>