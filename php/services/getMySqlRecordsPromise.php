<?php 
//
// Receives a string denoting a sql query and returns an array 
// containing the query results 
//
	include('connect.php');
	// Try and connect using the info above.
	$conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
	if ( mysqli_connect_errno() ) {
		// If there is an error with the connection, stop the script and display the error.
		//exit('Failed to connect to MySQL: ' . mysqli_connect_error());
		echo json_encode(array("statusCode"=>201));
	}
	// Now we check if the data from the login form was submitted, isset() will check if the data exists.
	//$_GET = json_decode(file_get_contents("php://input"),true); //if  axios posts
	if ( !isset($_GET['sql_query']) ) {
		// Could not get the data that should have been sent.
		//exit('Please fill both the username and password fields!');
		echo json_encode(array("statusCode"=>202));
	}
	$result_array = array();
	/* SQL query to get results from database */

	$sql_query = $_GET['sql_query'];
	$result = $conn->query($sql_query);
	/* If there are results from database push to result array */
	if($result){ 
		/* If there are results from database push to result array */
			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					array_push($result_array, $row);
				}
			}
		} else{ 
			echo "Error in ".$sql_query."".$conn->error; 
		}
	/* send a JSON encded array to client */
	//header('Content-type: application/json');
	echo json_encode(array("statusCode"=>200, "records"=>$result_array));
	$conn->close();

?>