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
	if ( !isset($_GET['user_id']) ) {
		// Could not get the data that should have been sent.
		//exit('Please fill both the username and password fields!');
		echo json_encode(array("statusCode"=>202));
		$conn->close();
		exit();
	}
	/* SQL query to get results from database */
	$id_to_delete = $_GET['user_id'];
	$conn->begin_transaction();
	try {
		$sql_query1 = "DELETE FROM usercompletedpolls where pollId= ". $id_to_delete;
		$statement = $conn->prepare($sql_query1);
		$statement->execute();
		$statement->close();

		$sql_query2 = "DELETE FROM surveysdefinitions where pollId= ". $id_to_delete;
		$statement = $conn->prepare($sql_query2);
		$statement->execute();
		$statement->close();

		$sql_query3 = "DELETE FROM polls where id= ". $id_to_delete;
		$statement = $conn->prepare($sql_query3);
		$statement->execute();
		$statement->close();
		$conn->commit();
		echo json_encode(array("statusCode"=>200));
	} catch (mysqli_sql_exception $exception) {
		$conn->rollback();
		echo json_encode(array("statusCode"=>203, "Error"=>$exception->getMessage()));
	}
	$conn->close();
?>