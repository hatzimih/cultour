<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged as an admin redirect to the login page.
if (!isset($_SESSION['loggedin1'])) {
	header('Location: ../../index.html');
	exit;
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit Poll</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <!--<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">-->

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>

    <link rel="stylesheet" href="../../css/style.css">
    <script>
    function getUrlParams() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('id')) {
            pollId = urlParams.get('id');
        } else pollId = -1;
    }

    function call_parent_test() {
        window.opener.test(123);
        close();
    }

    async function fillComboBox(sql_query) {
        const result = await axiosGetQueryResultsPromise(sql_query);
        if (result.data.statusCode == 200) {
            populateComboBox(result.data.records, "pollKind");
        } else {
            handleHttpRequestErrors(result);
        }
    }

    function init() {
        initDialog('dialog');
        getUrlParams();
        let sql = "SELECT id, description from pollkinds";
        let sel_lang_html = JSON.parse(window.localStorage.getItem('LangComboHtml'));
        document.getElementById('select_lng').innerHTML = sel_lang_html;
        //window.localStorage.removeItem('LangComboHtml');
        fillComboBox(sql);
        if (pollId > -1) {
            FillForm();
        } else {
            changeCountLabel(4);
            document.getElementById("pollKind").value = 1;
            displayOption('none');
        }
        $("#answers_no").change(function() {
            changeCountLabel($(this).val());
            if ($(this).val() < ($("#option").val())) $("#option").val($(this).val());
        });

        let sel2 = $("#option");
        sel2.data("prev", sel2.val());
        $("#option").change(function(event) {
            if ($("#cntLabel2b").html() == "#Stars") return; //rating
            if ($("#answers_no").val() < $(this).val()) $(this).val($(this).data("prev"));
            else $(this).data("prev", $(this).val());
        });
    }

    function changeCountLabel(value) {
        str = "";
        for (q = 1; q <= value; q++)
            str += `<input type="text" class="form-control" id="question${q}">`;
        document.getElementById("questions").innerHTML = str;
    }


    function validateRecord() {
        err_title = "Error while saving poll";
        if (isEmpty(document.getElementById("description"))) {
            displayMsgJQ("dialog", err_title, "Give a description", ERROR_MSG);
            return false;
        }
        if (pollId == -1) {
            if (!isEmpty(sat = document.getElementById('start_at'))) {
                start_date = new Date(sat.value);
                if (start_date < new Date()) {
                    displayMsgJQ("dialog", err_title, "Start date should be in the future", ERROR_MSG);
                    return false;
                }
                if (!isEmpty(eat = document.getElementById('end_at'))) {
                    end_date = new Date(eat.value)
                    if (end_date <= start_date) {
                        displayMsgJQ("dialog", err_title, "End date should be after start date", ERROR_MSG);
                        return false;
                    }
                }
            }
        }
        pollknd = document.getElementById("pollKind").value;
        if (pollknd == APPROVAL) {
            let deffield = document.getElementById("option");
            defvar = parseInt(deffield.value);
            if (document.getElementById("questions").children.length < defvar) {
                displayMsgJQ("dialog", err_title, "Number of required answers is greater than available answers",
                    ERROR_MSG);
                return false;
            }
        }
        return true;
    }

    function closeFrm() {
        close();
    }

    async function axiosExeqQuery(query_str) {
        //console.log(query_str);
        const result = await axiosExecQueryPromise(sql_query);
        if (result.data.statusCode == 200)
            displayMsgJQ("dialog", "Poll saving", "Poll saved succesfully", closeFrm);
        else
            console.log(result);
    }

    function saveAndClose(saveOnExit) {
        console.log('close');
        if (saveOnExit) {
            if (validateRecord()) {
                sql_query = "INSERT INTO polls values(";
                descr = document.getElementById("description").value;
                if (!(url = document.getElementById("url").value)) url = null;
                pollknd = document.getElementById("pollKind").value;
                if (!(start_at = document.getElementById("start_at").value)) start_at = null;
                if (!(end_at = pollkind = document.getElementById("end_at").value)) end_at = null;
                let defvar = document.getElementById("option");
                let defvar_val = 0;
                if (defvar.offsetParent != null) //if option element is visible
                    defvar_val = parseInt(defvar.value);
                else defvar_val = 0;
                lang = document.getElementById("lang_btn").innerHTML.trim();
                let credits = document.getElementById("credit_points").value
                let questions_arr = [];
                let q = document.getElementById("questions");
                if (q.offsetParent != null) { //if question div is visible
                    //if question div is visible
                    let qchildren = q.children;
                    for (i = 0; i < qchildren.length; i++)
                        questions_arr.push(document.getElementById("question" + (i + 1)).value);
                    //console.log(questions_arr);
                    questions_str = convertArrayToDelimitedString(questions_arr);
                    //console.log(questions_str);
                } else { //Yes/No poll
                    questions_str = null;
                }
                open = 1;
                in_survey = 0;
                if (pollId == -1) {
                    sql_query =
                        `INSERT INTO polls VALUES (DEFAULT, ${sql(descr)}, ${pollknd}, ${sql(start_at)}, ${sql(end_at)}, ${open}, ${defvar_val}, ${sql(questions_str)}, ${in_survey}, ${credits}, ${sql(url)},'${lang}');`
                } else
                    sql_query = `UPDATE POLLS set description = ${sql(descr)},
                                                      kind = ${pollknd},
                                                      start_date = ${sql(start_at)}, 
                                                      end_date  = ${sql(end_at)}, 
                                                      Open = ${open}, 
                                                      defVariable = ${defvar_val}, 
                                                      questions = ${sql(questions_str)}, 
                                                      in_survey = ${in_survey}, 
                                                      credits = ${credits},
                                                      url = ${sql(url)},
                                                      lang = '${lang}' where id = ${pollId}`;
                axiosExeqQuery(sql_query);
            }
        } else close();
    }

    function displayOption(stl) {
        document.getElementById("cntLabel2b").style.display = stl;
        document.getElementById("option").style.display = stl
    }

    function displayNoAnswers(stl) {
        document.getElementById("cntLabel1b").style.display = stl;
        document.getElementById("answers_no").style.display = stl
    }

    function changeKind(new_value) {
        if (new_value == 2) {
            //Rating: display Max number of stars
            document.getElementById("cntLabel2b").innerHTML = "#Stars";
            displayNoAnswers('block');
            displayOption("block");
            document.getElementById("questions").style.display = 'block';
        } else if (new_value == 4) { //Approval: Number of possible answers
            document.getElementById("cntLabel2b").innerHTML = "#Answers";
            displayNoAnswers('block');
            displayOption("block");
            document.getElementById("questions").style.display = 'block';
        } else {
            displayOption("none");
            if (new_value == 3) { //Yes/No
                displayNoAnswers('none');
                document.getElementById("questions").style.display = 'none';
            } else {
                displayNoAnswers('block');
                document.getElementById("questions").style.display = 'block';
            }
        }
    }

    async function FillForm() {
        const result = await axiosGetQueryResultsPromise("SELECT * FROM polls where id=" + pollId);
        if (result.data.statusCode == 200) {
            const rec_data = result.data.records[0];
            //console.log(rec_data);
            /*Open: "1"
            defVariable: "5"
            description: "Rank the following"
            end_date: null
            id: "0"
            in_survey: "0"
            kind: "2"
            questions: "Selection1$-%@$Selection2$-%@$Selection3$-%@$Selection4"
            start_date: "2022-10-11 13:26:13"*/
            document.getElementById("pollKind").value = rec_data['kind'];
            changeKind(rec_data['kind']);
            document.getElementById("description").value = rec_data['description'];
            document.getElementById("start_at").value = rec_data['start_date'];
            if (rec_data['end_date']) document.getElementById("end_at").value = getTimeStamp(rec_data['end_date']);
            document.getElementById("credit_points").value = rec_data['credits'];
            questions = rec_data['questions'].split(delim);
            changeCountLabel(questions.length);
            document.getElementById("answers_no").value = questions.length;
            for (j = 0; j < questions.length; j++) {
                document.getElementById("question" + (j + 1)).value = questions[j];
            }
        } else {
            handleHttpRequestErrors(result);
        }
    }
    </script>
</head>

<body onload="init();">
    <div id="dialog" title=""></div>
    <h2 class="bg-primary p-2">Edit poll</h2>

    <!--<a href="#" onclick="call_parent_test();">Test</a>-->
    <form>
        <div id="rec" class="form-row m-2">
            <div class="container">
                <div class="row pb-1 aligncolor">
                    <div class="col-md-auto">
                        <label class="form-label" style="color:black" for="pollKind">Poll type</label>
                        <select id="pollKind" onchange="changeKind(this.value);" class="form-select">
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <label id="cntLabel2" class="form-label aligncolor">-----</label>
                    </div>
                    <div class="col-md-auto">
                        <label for="credit_points" style="color:black" class="form-label">Credit points</label>
                        <input id="credit_points" class="form-control ms-n1" type="number" min="1" max="100" step="1"
                            value="5">
                    </div>
                    <div class="col-md-auto">
                        <label id="cntLabel1b" for="answers_no" style="color:black" class="form-label">#Choices</label>
                        <input id="answers_no" class="form-control ms-n1" type="number" min="1" max="10" step="1"
                            value="4">
                    </div>
                    <div class="col-md-auto">
                        <label id="cntLabel2b" for="option" style="color:black" class="form-label">Option</label>
                        <input id="option" class="form-control ms-n1" type="number" min="0" max="10" step="1" value="4">
                    </div>
                </div>
            </div>
        </div>
        <div class="row p-2">
            <div class="form-group d-flex flex-column">
                <label class="form-label" for="description">Description</label>
                <textarea form="rec" form-class="form-control" id="description" row="5" cols="40"
                    placeholder="Enter poll's description"></textarea>
            </div>
        </div>
        <div class="row p-2">
            <div class="form-group col-md-4">
                <label class="form-label" for="start_at">Starting at</label>
                <input type="datetime-local" class="form-control" id="start_at" placeholder="Starts">
            </div>
            <div class="form-group col-md-4">
                <label class="form-label" for="end_at">Ending at</label>
                <input type="datetime-local" class="form-control" id="end_at" placeholder="Ending">
            </div>
            <div class="form-group col-md-4">
                <label class="form-label" for="select_lng">Poll Language</label>
                <div class="dropdown">
                    <button id="lang_btn" class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        En
                    </button>
                    <ul id="select_lng" class="dropdown-menu"
                        style="z-index:9999!important;cursor:pointer;background-color: rgb(182, 239, 243);"
                        aria-labelledby="dropdownMenuButton"></ul>
                </div>
            </div>
        </div>
        <div class="form-group mb-3 m-2">
            <label class="form-label" for="url">Related image url</label>
            <input type="text" class="form-control" id="url" placeholder="url of the related image">
        </div>
        <div class="form-row m-2">
            <div class="form-group col-md-6">
                <label for="questions">Choices</label>
                <div id="questions" class="form-group">
                </div>
            </div>

        </div>
        <button type="button" class="btn btn-primary" onclick="saveAndClose(true); ">Save changes</button>
        <button type="button" class="btn btn-secondary" onclick="saveAndClose(false)">Close</button>
    </form>
</body>

</html>