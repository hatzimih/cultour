<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged as an admin redirect to the login page.
if (!isset($_SESSION['loggedin1'])) {
	header('Location: ../../index.html');
	exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Home Page</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.2.0/css/all.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>
    <script src="../../js/FileSaver.min.js"></script>

    <link rel="stylesheet" href="../../css/style.css">

    <script>
    function test(a) {
        alert(a);
    }

    function closeFrm() {
        close();
    }

    function openTopUsers() {
        window_width = 800;
        window_height = 600;
        [window_left, window_top] = calculateNewWindowPosition(window_width, window_height);
        var href = window.location.href;
        //newWindowUrl = href.substring(0, href.lastIndexOf('/')) + "/php/pages/pollVoting" + fname + ".php?id=" + ref_id;
        newWindowUrl = "topCreditsUsers.php";
        var myWindow = window.open(newWindowUrl, "Top Credits",
            `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`);
    }

    function EditRecord(type, mode, record_id) {

        [window_left, window_top] = calculateNewWindowPosition(window_width, window_height);
        var href = window.location.href;
        newWindowUrl = href.substring(0, href.lastIndexOf('/')) + (type == POLL ? "/editPoll.php" : "/editSurvey.php");
        console.log(newWindowUrl);
        if (mode == EDIT_RECORD) {
            ref_id = parseInt(record_id.split("_")[1]);
            var myWindow = window.open(newWindowUrl + "?id=" + ref_id, "Edit record",
                `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`);
        } else window.open(newWindowUrl, "New record",
            `width=800,height=800, left=${window_left}, top=${window_top},scrollbars=yes`);
    }

    async function SaveRecordToCsv(type, mode, record_id) {
        ref_id = parseInt(record_id.split("_")[1]);
        if (type == POLL) {
            const sql1 =
                "SELECT p.*, pk.description as type FROM polls as p INNER JOIN pollkinds as pk on p.kind = pk.id where p.id =" +
                ref_id;
            const poll = await axiosGetQueryResultsPromise(sql1);
            poll_data = "";
            if (poll.data.statusCode == 200) {
                let p = poll.data.records[0];
                poll_data = `${p.description}\r\n`;
                poll_data += `${p.type}, ${p.start_date}, ${sql(p.end_date)}, ${sql(p.url)}\r\n`;
                if (p.kind == YES_NO) poll_data = "YES\r\nNO\r\n";
                else {
                    qs = p.questions.split(delim);
                    for (j = 0; j < qs.length; j++)
                        poll_data += `${qs[j]}\r\n`;
                }
                poll_data += '-------\r\n';
                const sql2 = "SELECT answers, completedAt FROM usercompletedpolls WHERE pollId =" + ref_id;
                const poll_answers = await axiosGetQueryResultsPromise(sql2);
                if (poll_answers.data.statusCode == 200) {
                    let pa = poll_answers.data.records;
                    for (j = 0; j < pa.length; j++)
                        poll_data += `${pa[j].completedAt}:${pa[j].answers}\r\n`;
                }
            } else handleHttpRequestErrors(poll);

            var blob = new Blob([poll_data], {
                type: "csv/plain;charset=utf-8"
            });
            saveAs(blob, `${new Date().toJSON().slice(0, 10)}_poll${ref_id}.csv`);
        }
    }

    function plotPoll(type, mode, record_id) {
        window_width = 800;
        window_height = 600;
        ref_id = parseInt(record_id.split("_")[1]);
        [window_left, window_top] = calculateNewWindowPosition(window_width, window_height);
        var href = window.location.href;
        newWindowUrl = href.substring(0, href.lastIndexOf('/')) + "/AnalyzePlot.php?id=" + ref_id;
        var myWindow = window.open(newWindowUrl, "Poll Voting",
            `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`);
    }

    var dtable;

    cPrev = -1; // global var saves the previous c, used to
    // determine if the same column is clicked again

    function sortBy(c) {
        rows = document.getElementById("dbusers").rows.length; // num of rows
        columns = document.getElementById("dbusers").rows[0].cells.length; // num of columns
        arrTable = [...Array(rows)].map(e => Array(columns)); // create an empty 2d array


        let clicked = document.getElementById("dbusers").rows[0].cells[c];
        if (c != cPrev) {
            let prev_clicked = document.getElementById("dbusers").rows[0].cells[cPrev];
            if (prev_clicked) {
                let str = prev_clicked.innerHTML;
                str = str.substring(0, str.indexOf("<i class") - 1);
                prev_clicked.innerHTML = str;
            }
            clicked.dataset.sortorder = "asc";
            clicked.innerHTML += "<i class='fa-solid fa-arrow-down'></i>";
        } else {
            if (clicked.dataset.sortorder == "asc") {
                clicked.dataset.sortorder = "desc";
                clicked.innerHTML = clicked.innerHTML.replace("down", "up");
            } else {
                clicked.dataset.sortorder = "asc"
                clicked.innerHTML = clicked.innerHTML.replace("up", "down");
            }
        }

        for (ro = 0; ro < rows; ro++) { // cycle through rows
            for (co = 0; co < columns; co++) { // cycle through columns
                // assign the value in each row-column to a 2d array by row-column
                arrTable[ro][co] = document.getElementById("dbusers").rows[ro].cells[co].innerHTML;
            }
        }
        console.log(arrTable);

        th = arrTable.shift(); // remove the header row from the array, and save it

        if (c !== cPrev) { // different column is clicked, so sort by the new column
            arrTable.sort(
                function(a, b) {
                    if ($.isNumeric(a[c]) && $.isNumeric(b[c])) {
                        let na = parseInt(a[c]);
                        let nb = parseInt(b[c]);
                        if (na == nb) return 0;
                        else return (na < nb) ? -1 : 1;
                    } else {
                        if (a[c] === b[c]) {
                            return 0;
                        } else {
                            return (a[c] < b[c]) ? -1 : 1;
                        }
                    }
                }
            );
        } else { // if the same column is clicked then reverse the array
            arrTable.reverse();
        }

        cPrev = c; // save in previous c

        arrTable.unshift(th); // put the header back in to the array

        // cycle through rows-columns placing values from the array back into the html table
        for (ro = 0; ro < rows; ro++) {
            for (co = 0; co < columns; co++) {
                document.getElementById("dbusers").rows[ro].cells[co].innerHTML = arrTable[ro][co];
            }
        }
    }

    async function init() {
        userType = ADMIN;
        initDialog('dialog');
        let sql1 = "SELECT id,description,start_date,end_date, open,lang FROM surveys";
        await fillTable("surveys", sql1, SURVEY, ADMIN);

        let sql2 =
            "SELECT p.id, p.description as descr, pk.description as kind, p.start_date, p.end_date, p.open, p.lang"
        sql2 += " FROM polls as p INNER JOIN pollkinds as pk ON p.kind=pk.id where p.in_survey=false order by p.id";
        await fillTable("polls", sql2, POLL, ADMIN);

        let sql3 = "SELECT id, username, email, gender, birthyear, country, '1' as credits, lang FROM users";
        await fillTable("dbusers", sql3, USERS, ADMIN);

        SetClassTooltip('editbtn', "Edit record");
        SetClassTooltip('deletebtn', "Delete record");
        SetClassTooltip('savecsvbtn', "Export data to a csv file");
        SetClassTooltip('plotpollbtn', "Poll's analysis");
        SetClassTooltip('th_dbuser', "Click to sort");
        /*dtable = $('#dbusers').DataTable({
            "columnDefs": [{
                "targets": 6,
                "orderable": false
            }, {
                "targets": 4,
                "orderable": true
            }]
        });
        $('#dbusers').on('click', 'th', function() {
            //this.innerHTML = parseInt(this.innerHTML) + 1;
            console.log('invalidate');
            dtable.cell(this).invalidate().draw();
            $('#dbusers').off('click');
        });*/
    }
    </script>
</head>

<body onload="init()" style="background-color:rgb(216, 248, 225)">
    <div id="dialog" title=""></div>
    <div class="row">
        <div class="col p-3 mb-2 bg-primary bg-gradient text-white">
            <span>Welcome back admin..</span>
        </div>
        <div class="col p-3 mb-2 bg-primary bg-gradient">
            <button class="btn btn-info" type="button" onclick="logoutUser();">Logout</button>
        </div>
    </div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" onclick="currentTab=POLL;" data-bs-toggle="tab"
                data-bs-target="#profile" type="button" role="tab" aria-controls="profile"
                aria-selected="true">Polls</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link " id="home-tab" onclick="currentTab=SURVEY;" data-bs-toggle="tab"
                data-bs-target="#home" type="button" role="tab" aria-controls="home"
                aria-selected="false">Surveys</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#users" type="button" role="tab" aria-controls="users"
                aria-selected="false">Users</button>
        </li>
    </ul>

    <div class="tab-content" id="SurveysTabContent">
        <div class="tab-pane fade" id="home" role="tabpanel" aria-labelledby="home-tab">
            <nav class="navbar navbar-expand-lg navbar-light"
                style="color:rgb(69, 52, 148);background-color:rgb(89, 196, 98)">
                <div class="container-fluid">
                    <span class="h4 text-white">Surveys</span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                        <div class="navbar-nav">
                            <a class="nav-link btn btn-primary text-white" aria-current="page" href="#"
                                onclick="EditRecord(SURVEY, NEW_RERORD , -1);">New survey</a>
                            <!--<a class="nav-link" href="#">Features</a>-->
                        </div>
                    </div>
                </div>
            </nav>
            <!--Surveys Tab : list of surveys-->
            <div class="col container-fluid">
                <div class="table-responsive p-2">
                    <table id="surveys" class="table table-hover stables" style="table-layout: fixed;">
                        <thead>
                            <tr>
                                <th data-celltype="txt">Title</th>
                                <th data-celltype="txt">Start date</th>
                                <th data-celltype="txt">End date</th>
                                <th data-celltype="bool">Status</th>
                                <th data-celltype="pref_lang">Language</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <nav class="navbar navbar-expand-lg navbar-light"
                style="color:rgb(69, 52, 148);background-color:rgb(95, 165, 100)">
                <div class="container-fluid">
                    <span class="h4 text-white">Polls</span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                        <div class="navbar-nav ">
                            <a class="nav-link btn btn-primary text-white" aria-current="page" href="#"
                                onclick="EditRecord(POLL, NEW_RERORD , -1);">Add new poll</a>
                        </div>
                    </div>
                </div>
            </nav>
            <!--Polls Tab : list of polls-->
            <div class="col container-fluid">
                <div class="table-responsive p-2">
                    <table id="polls" class="table data_table table-hover stablep">
                        <thead>
                            <tr>
                                <th data-celltype="txt"><span>Title</span></th>
                                <th data-celltype="txt"><span>Info</span></th>
                                <th data-celltype="txt"><span>Start date</span></th>
                                <th data-celltype="txt"><span>End date</span></th>
                                <th data-celltype="bool"><span>Status</span></th>
                                <th data-celltype="pref_lang">Language</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="profile-tab">
            <nav class="navbar navbar-expand-lg navbar-light"
                style="color:rgb(169, 52, 148);background-color:rgb(225, 105, 100)">
                <div class="container-fluid">
                    <span class="h4 text-white">Users</span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-center" id="navbarNavAltMarkup">
                        <div class="navbar-nav ">
                            <a class="nav-link btn btn-primary text-white" aria-current="page" href="#"
                                onclick="openTopUsers();">Export users' info</a>
                        </div>
                    </div>
                </div>
            </nav>
            <!--Users Tab : list of polls-->
            <div class="col container-fluid">
                <div class="table-responsive p-2">
                    <table id="dbusers" class="table data-table table-hover table-responsive stables">
                        <thead>
                            <tr>
                                <th data-celltype="txt" onclick="sortBy(0)"><span class="btn btn-info">Username</span>
                                </th>
                                <th data-celltype="txt" onclick="sortBy(1)"><span class="btn btn-info">email</span></th>
                                <th data-celltype="gnd" onclick="sortBy(2)"><span class="btn btn-info">Gender</span>
                                </th>
                                <th data-celltype="txt" onclick="sortBy(3)"><span class="btn btn-info">Birth year</span>
                                </th>
                                <th data-celltype="txt" onclick="sortBy(4)"><span class="btn btn-info">Country</span>
                                </th>
                                <th data-celltype="txt_dbinfo" onclick="sortBy(5)">
                                    <span class="btn btn-info">Credits</span>
                                </th>
                                <th data-celltype="pref_lang" onclick="sortBy(6)"><span class="btn btn-info">Pref
                                        Language</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>