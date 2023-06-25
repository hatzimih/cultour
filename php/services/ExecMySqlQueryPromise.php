<?php 
//
// Receives a string denoting a sql query and 200 if query executed successfully
//
	include('connect.php');
	// Try and connect using the info above.
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		//exit('Failed to connect to MySQL: ' . mysqli_connect_error());
		echo json_encode(array("statusCode"=>201));
		$conn->close();
		exit();
	}
	// Now we check if the data from the login form was submitted, isset() will check if the data exists.
	//$_POST= json_decode(file_get_contents("php://input"),true); //if  axios posts
	if ( !isset($_GET['sql_query']) ) {
		// Could not get the data that should have been sent.
		//exit('Please fill both the username and password fields!');
		echo json_encode(array("statusCode"=>202));
		$conn->close();
		exit();
	}
	/* SQL query to get results from database */
	$sql_query = $_GET['sql_query'];
	$statement = $conn->prepare($sql_query);
	if ($statement->execute()) {
		echo json_encode(array("statusCode"=>200));
	} else {
		echo json_encode(array("statusCode"=>203));
	}
	$statement -> close();
	$conn->close();
?>