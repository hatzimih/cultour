<?php
//
// Contains the necessary online DB connection details
//
	//const APP_ROOT = 'https://dpl2022.scienceontheweb.net/';
        const APP_ROOT = 'https://cultour.scienceontheweb.net/';
	const APP_URL = APP_ROOT . "php/services/";
	const SENDER_EMAIL_ADDRESS = 'no-reply@email.com';
        
  	$DATABASE_HOST = 'pdb34.awardspace.net';
        $DATABASE_USER = '4208712_cultour';
	$DATABASE_PASS = '!Mnn8!Q77U6hhbW8';
	$DATABASE_NAME = '4208712_cultour';
	$IP = "185.176.40.180";
        //$DATABASE_USER = '4208712_dpl';
        //$DATABASE_NAME = '4208712_dpl';
        
    $conn = mysqli_init();
    //mysqli_options ($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        
    //$mysqli->ssl_set(NULL, NULL, "/etc/ssl/certs/ca-bundle.crt", NULL, NULL); 
    //$conn->ssl_set('/etc/mysql/ssl/client-key.pem', '/etc/mysql/ssl/client-cert.pem', '/etc/mysql/ssl/ca-cert.pem', NULL, NULL);
        
    //$link = mysqli_real_connect ($conn, $DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME, 3306, NULL, MYSQLI_CLIENT_SSL);
    
    $conn = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
    if ( mysqli_connect_errno() ) {
	// If there is an error with the connection, stop the script and display the error.
	//exit('Failed to connect to MySQL: ' . mysqli_connect_error());
	echo json_encode(array("statusCode"=>201));
    }
        
    if (!$conn)  {
        die ('Connect error (' . mysqli_connect_errno() . '): ' . mysqli_connect_error() . "\n");
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>