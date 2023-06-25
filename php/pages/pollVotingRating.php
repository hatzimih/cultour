<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
$reg_user=isset($_SESSION['loggedin0']);
// If the user is not logged as an admin redirect to the login page.
if (!isset($_SESSION['loggedin0'])) {
	//header('Location: ../../index.html');
	//exit;
}
?>


<!DOCTYPE HTML>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cast your vote</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src=" https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js "></script>

    <script src="../../js/utils.js "></script>
    <script src="../../js/axios.js "></script>

    <link rel="stylesheet " href="../../css/style.css ">
    <link rel="stylesheet " href="../../css/rating.css ">
    <script>
    user_loggedin = <?php echo json_encode($reg_user) ?>;

    function enableMsgs() {
        document.body.innerHTML = `<div id='dialog' title=""></div>` + document.body.innerHTML;
        initDialog('dialog');
        if (!user_loggedin) document.title += " (voting:only for registered users)";
    }
    </script>
</head>

<body onload="initForm(document.body, RATING,'Rating poll', 1);enableMsgs()">
</body>

</html>