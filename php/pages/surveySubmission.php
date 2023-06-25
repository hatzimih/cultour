<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged as an admin redirect to the login page.
$reg_user=isset($_SESSION['loggedin0']);
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

    <title>Survey submission</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>
    <script src="../../js/dragging.js"></script>

    <link rel="stylesheet" href="../../css/dragging.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/rating.css">



    <script>
    user_loggedin = <?php echo json_encode($reg_user) ?>;
    if (!user_loggedin) document.title += " (voting:only for registered users)";
    var pollsCount = 0;
    var pollsInfo;
    var currentUserId;
    async function init() {
        initDialog('dialog');
        surveyId = getUrlParams();
        if (surveyId.startsWith("-")) {
            surveyId = surveyId.substring(1);
            document.title += ' -closed '
            $("#submit_btn").hide();
        }
        currentUserId = window.opener.currentUserId;
        const details = await axiosGetQueryResultsPromise("SELECT * FROM surveys WHERE id = " + surveyId);
        if (details.data.statusCode == 200) {
            rec_data = details.data.records[0];
            document.getElementById("description").innerHTML = rec_data['description'];
            //aaaaaa
            if (rec_data['url']) {
                //document.getElementById(`url${cnt}`).value = rec_data['url'];
                if (rec_data['url'].startsWith("/"))
                    $("#survey_img").attr("src", path_to_root + rec_data['url']);
                else $("#survey_img").attr("src", rec_data['url']);
            } else {
                document.getElementById("survey_img").style.display = "none";
            }
            document.getElementById("start_at").value = rec_data['start_date'];
            document.getElementById("end_at").value = rec_data['end_date'];

            const polls = await axiosGetQueryResultsPromise(
                "SELECT sd.pollId as pid, p.kind as kind from surveysdefinitions as sd INNER JOIN polls as p on p.id=sd.pollId where surveyid= " +
                surveyId + " order by pollOrder");
            if (polls.data.statusCode == 200) {
                pollsInSurvey = polls.data.records;
                pollsInfo = [];
                str = "";
                pollsCount = pollsInSurvey.length;
                for (j = 0; j < pollsInSurvey.length; j++)
                    str += `<div id=surveypoll${j+1} class="border border-dark"></div>`;
                document.getElementById("pollInSurvey").innerHTML = str;
                for (j = 0; j < pollsInSurvey.length; j++) {
                    pollsInfo.push([parseInt(pollsInSurvey[j].pid), parseInt(pollsInSurvey[j].kind)]);
                    //console.log(pollsInSurvey[j].pid, pollsInSurvey[j].kind);
                    target_div = document.getElementById(`surveypoll${j+1}`);
                    displayBtnsInEveryPoll = false;
                    initPoll(target_div, pollsInSurvey[j].pid, parseInt(pollsInSurvey[j].kind), `Poll#${j+1}`, (j +
                        1), displayBtnsInEveryPoll);
                }

            }
        }
    }

    async function submitAnswers(sql) {
        let sql_query = "INSERT into usercompletedpolls VALUES " + sql;
        const result1 = await axiosExecQueryPromise(sql_query);
        if (result1.data.statusCode == 200) {
            displayMsgJQ("dialog", "Survey submit",
                "Your answers submitted succesfully. Thank you for your valuable contribution.", closeFrm);
        } else handleHttpRequestErrors(result1);
    }

    function validateSurvey1() {
        if (!user_loggedin) {
            displayMsgJQ("dialog", "Error", `Voting is allowed only for registered users.`, ERROR_MSG);
            return;
        }
        sql = "";
        for (j1 = 1; j1 <= pollsCount; j1++) {
            v = validatePoll1(pollsInfo[j1 - 1][1], j1); //kind cnt
            if (v) sql += "," + v;
            else {
                sql = null;
                console.log(`Error in ${j1} poll..`);
                return;
            }
        }
        sql = sql.substring(1); //remove initial ,
        submitAnswers(sql);
    }

    function validatePoll1(pollkind, cnt) {
        console.log(pollkind, cnt);
        let answer = "";
        let sql_query = null;
        let pid = parseInt(document.getElementById(`description${cnt}`).name);
        switch (pollkind) {
            case RATING:
                answer = "";
                let q = document.getElementById("questions" + cnt).children;
                filled_lines = 0;
                for (j = 0; j < q.length; j++) {
                    star_lines = q[j].children;
                    for (i = 1; i < star_lines.length; i = i + 2) {
                        line_val = 0;
                        let line_stars = star_lines[i].children;
                        for (p = 0; p < line_stars.length; p++) {
                            if (line_stars[p].className.includes("rating-color")) line_val++;
                            else break;
                        }
                    }
                    if (line_val > 0) filled_lines++;
                    answer += "," + line_val;
                }
                answer = answer.substring(1);
                if (filled_lines == q.length)
                    sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
                else {
                    $("#gotopoll__" + cnt).click();
                    displayMsgJQ("dialog", "Error saving your answer in poll " + cnt, "You have to rate all lines",
                        ERROR_MSG);
                }
                break;
            case RANKING:
                if (!document.getElementById('rating_numbers' + cnt).hasChildNodes()) {
                    answer = "";
                    let q = document.getElementById("questions" + cnt).children;
                    for (j = 0; j < q.length; j++) {
                        btn_name = q[j].children[0].children[0].id;
                        answer += ("," + btn_name.substring(btn_name.lastIndexOf("_") + 1));
                    }
                    answer = answer.substring(1);
                    sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
                } else {
                    $("#gotopoll__" + cnt).click();
                    displayMsgJQ("dialog", "Error saving your answer in poll " + cnt,
                        "You have to use all available ranking numbers", ERROR_MSG);
                }
                break;
            case YES_NO:
                if (document.getElementById("Yes" + cnt).checked) answer = 1;
                else answer = 0;
                sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
                break;
            case APPROVAL:
                let r = document.getElementById("questions" + cnt).children;
                answer = "";
                given_answers = 0;
                for (j = 0; j < r.length; j++)
                    if (document.getElementById("chk" + cnt + "_" + j).checked) {
                        answer += "," + (j + 1);
                        given_answers++;
                    }
                answer = answer.substring(1);
                needed_answers = parseInt(document.getElementById(`url_row${cnt}`).name);
                if (needed_answers == given_answers)
                    sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
                else if (needed_answers > given_answers) {
                    $("#gotopoll__" + cnt).click();
                    displayMsgJQ("dialog", "Error saving your answer in poll " + cnt,
                        `You have to fill ${needed_answers-given_answers} more options`, ERROR_MSG);
                } else if (needed_answers == 0 && given_answers > 0) {
                    sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
                } else {
                    $("#gotopoll__" + cnt).click();
                    displayMsgJQ("dialog", "Error saving your answer in poll " + cnt,
                        `You have to fill ${needed_answers} at most1`, ERROR_MSG);
                }
                break;
        }
        console.log(sql_query);
        return sql_query;
    }

    function closeWindow() {
        close();
    }
    </script>
</head>

<body onload="init()">
    <div id="dialog" title=""></div>
    <div class="row p-2">
        <div class="form-group d-flex flex-column">
            <h2 id="description" class="bg-info p-2 text-white">aaaaa</h2>
        </div>
    </div>
    <div class="row p-2">
        <div class="form-group col-md-4">
            <label class="form-label" for="start_at">Starting at</label>
            <input type="datetime-local" class="form-control" id="start_at" disabled>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label" for="end_at">Ending at</label>
            <input type="datetime-local" class="form-control" id="end_at" disabled>
        </div>
    </div>
    <div class="form-group mb-3 m-2">
        <img id="survey_img" class="img-fluid">
    </div>
    <div id="pollInSurvey"></div>
    <button id="submit_btn" type="button" class="btn btn-primary" onclick="validateSurvey1()">Submit</button>
    <button type="button" class="btn btn-secondary" onclick="closeWindow()">Close</button>
</body>

</html>