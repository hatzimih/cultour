<?php
	session_start();
	include('connect.php');
	// Try and connect using the info above.
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		echo json_encode(array("statusCode"=>201));
	}
	$_POST = json_decode(file_get_contents("php://input"),true); //if  axios posts
	if ( !isset($_POST['query']) ) {
		// Could not get the data that should have been sent.
		echo json_encode(array("statusCode"=>202));
		exit(-1);
	}
	// Prepare our SQL, preparing the SQL statement will prevent SQL injection.
	if ($stmt = $conn->prepare($_POST['query'])) {
		$stmt->execute();
		// Store the result so we can check if the account exists in the database.
		$stmt->store_result();
		$stmt->bind_result($count);
		$stmt->fetch();
		echo json_encode(array("statusCode"=>200, "count"=>$count));
	} else {
		echo json_encode(array("statusCode"=>203));
	}
	$stmt->close();
	mysqli_close($conn);
?>