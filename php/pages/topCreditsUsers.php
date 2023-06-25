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

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>
    <script src="../../js/FileSaver.min.js"></script>

    <link rel="stylesheet" href="../../css/style.css">

    <script>
    async function fillUsersTable(sqlQuery) {
        const result = await axiosGetQueryResultsPromise(sqlQuery);
        if (result.data.statusCode == 200) {
            let users = result.data.records
            table_body_html = "";
            for (j1 = 0; j1 < users.length; j1++) {
                let user = users[j1];
                row_html = `<tr><td>${user.username}</td>
                                <td>${user.email}</td>
                                <td>${genders[user.gender]}</td>
                                <td>${user.birthyear}</td>
                                <td>${user.country}</td>
                                <td>${user.lang}</td>
                                <td>${user.crts}</td></tr>`;
                table_body_html += row_html;
            }
            $("#tbl_body").html(table_body_html);
        } else {
            console.log(result.data);
            displayMsgJQ("dialog", "Users Credits", "Internal Error! Try again later", ERROR_MSG, false, "OK");
        }
    }

    function ExportCSV() {
        let rows = document.getElementById("tbl_body").children;
        let header = document.getElementById("dbusers").rows[0].cells;
        table_data = "";
        for (i = 0; i < header.length; i++) {
            table_data += "," + header[i].innerText;
        }
        table_data = table_data.substring(1) + "\r\n";
        let top = $("#credit_points").val();

        for (j1 = 0; j1 < rows.length; j1++) {
            let row = rows[j1].children;
            let row_csv = "";
            for (k1 = 0; k1 < row.length; k1++)
                row_csv += "," + row[k1].innerHTML;
            table_data += row_csv.substring(1) + "\r\n";
        }
        var blob = new Blob([table_data], {
            type: "csv/plain;charset=utf-8"
        });
        saveAs(blob, `${new Date().toJSON().slice(0, 10)}_users_top${top}.csv`);
    }

    function init() {
        initDialog('dialog');
        let sql3 = "CALL SelectTopCredits(-1)";
        fillUsersTable(sql3);
    }

    function displayTopCreditUsers() {
        let top = $("#credit_points").val();
        let sql = `CALL SelectTopCredits(${top})`;
        fillUsersTable(sql);
    }
    </script>
</head>

<body onload="init()">
    <div id="dialog" title=""></div>
    <div class="col container-fluid">
        <form>
            <nav class="navbar navbar-expand-lg navbar-light"
                style="color:rgb(69, 52, 148);background-color:rgb(95, 195, 200)">
                <div class="container-fluid ">
                    <div class="row">
                        <div class="col-md-auto">
                            <label for="credit_points" style="color:black" class="form-label">Select top users</label>
                            <input id="credit_points" class="form-control" type="number" min="1" max="100" step="1"
                                value="5">
                        </div>
                        <div class="col-md-auto">
                            <button id="goBtn" class="btn btn-primary" type="button"
                                onclick="displayTopCreditUsers();"><span>Go</span></button>
                        </div>
                        <div class="col-md-auto">
                            <button id="goBtn" class="btn btn-primary" type="button" onclick="ExportCSV();"><span>Export
                                    CSV</span></button>
                        </div>
                    </div>

            </nav>
        </form>
        <div class="table-responsive p-2">
            <table id="dbusers" class="table data_table">
                <thead>
                    <tr>
                        <th data-celltype="txt_dbinfo"><span>Username</span></th>
                        <th data-celltype="txt_dbinfo"><span>Email</span></th>
                        <th data-celltype="gnd"><span>Gender</span></th>
                        <th data-celltype="txt"><span>Birth year</span></th>
                        <th data-celltype="txt"><span>Country</span></th>
                        <th data-celltype="pref_lang"><span>Pref Language</span></th>
                        <th data-celltype="pref_lang"><span>Credits</span></th>
                    </tr>
                </thead>
                <tbody id="tbl_body">
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-primary" onclick="closeFrm();">Close</button>
    </div>
</body>

</html>