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
		echo json_encode(array("statusCode"=>201));
	}

	$user_id=$_POST['id'];
	$password=$_POST['password'];
	$country=$_POST['country'];
	$lang=$_POST['lang'];
	$gender=$_POST['gender'];
	$birthyear=$_POST['birthyear'];
	$username=$_POST['username'];
	$email=$_POST['email'];
	$count=1;
	$sql_unique = "SELECT count(*) FROM users where id<>".$user_id." and (email='".$email."' OR username='".$username."')";
	if ($stmt = $conn->prepare($sql_unique)) {
		$stmt->execute();
		// Store the result so we can check if the account exists in the database.
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();
		if ($count>0) {
			echo json_encode(array("statusCode"=>205));
		}
	}
	if ($count==0) {
		// Bind parameters (s = string, i = int, b = blob, etc), hash the password using the PHP password_hash function.
		if ($stmt = $conn->prepare('UPDATE users SET username=?, email=?, password=?, country=?, lang=?, gender=?, birthyear=? WHERE id=?')) {
			// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
			//$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
			$stmt->bind_param('sssssiii', $username, $email, $password, $country, $lang, $gender, $birthyear, $user_id);
			if ($stmt->execute()) echo json_encode(array("statusCode"=>200, "info"=>mysqli_info($conn)));
			else echo json_encode(array("statusCode"=>203));
		} else {
			// Something is wrong with the sql statement, check to make sure accounts table exists with all 3 fields.
			echo json_encode(array("statusCode"=>204));
		}
	}
	$stmt->close();
	mysqli_close($conn);
?>