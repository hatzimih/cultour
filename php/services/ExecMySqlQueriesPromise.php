<?php 
//
// Receives a string denoting a sql query and 200 if query executed successfully
//
	include('connect.php');
    $delim = "$-%@$";
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
	if ( !isset($_GET['sql_queries']) ) {
		// Could not get the data that should have been sent.
		//exit('Please fill both the username and password fields!');
		echo json_encode(array("statusCode"=>202));
		$conn->close();
		exit();
	}
	/* SQL query to get results from database */
	$sql_queries = $_GET['sql_queries'];
    $queries = explode($delim , $sql_queries);
	$conn->begin_transaction();
	try {
		foreach ($queries as $sql_query) {
			echo $sql_query;
			$statement = $conn->prepare($sql_query);
			$statement->execute();
			$statement->close();
		}   
		$conn->commit();
		echo json_encode(array("statusCode"=>200));
	} catch (mysqli_sql_exception $exception) {
		$conn->rollback();
		echo json_encode(array("statusCode"=>203));
	}
	$conn->close();
?>