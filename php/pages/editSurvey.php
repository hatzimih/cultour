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

    <title>Edit survey</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>

    <link rel="stylesheet" href="../../css/style.css">
    <script>
    function getUrlParams() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('id')) {
            surveyId = urlParams.get('id');
        } else surveyId = -1;
    }

    function call_parent_test() {
        window.opener.test(123);
        close();
    }

    function init() {
        initDialog('dialog');
        getUrlParams();
        let sel_lang_html = JSON.parse(window.localStorage.getItem('LangComboHtml'));
        document.getElementById('select_lng').innerHTML = sel_lang_html;
        let sql = "SELECT id, description from pollkinds";
        FillForm(surveyId);
    }

    function closeFrm() {
        close();
    }

    async function axiosExeqQuery(query_str) {
        console.log(query_str);
        const result = await axiosExecQueryPromise(query_str);
        if (result.data.statusCode == 200)
            displayMsgJQ("dialog", "Survey answers' submission", "Your answers submitted succesfully", INFO_MSG,
                closeFrm);
        else
            console.log(result);
    }

    async function saveAndClose(saveOnExit) {
        let mult_values = "";
        if (saveOnExit) {
            descr = document.getElementById("description").value;
            if (descr.length == 0) {
                -displayMsgJQ("dialog", "Survey answers' submission", "Description cannot by empty", ERROR_MSG);
                return;
            }
            if (!document.getElementById('selpolls').hasChildNodes()) {
                displayMsgJQ("dialog", "Survey answers' submission", "Selected polls' list cannot be empty",
                    ERROR_MSG);
                return;
            }
            if (!(start_at = document.getElementById("start_at").value)) start_at = null;
            if (!(end_at = pollkind = document.getElementById("end_at").value)) end_at = null;
            if (!(url = document.getElementById("url").value)) url = null;
            lang = document.getElementById("lang_btn").innerHTML.trim();
            if (surveyId == -1) {
                open = 1;
                sql_query =
                    `INSERT INTO surveys VALUES (DEFAULT, ${sql(descr)}, ${sql(start_at)}, ${sql(end_at)}, ${open}, ${sql(url)}, '${lang}');`
                const new_survey = await axiosExeqQuery(sql_query);
                const get_last_inserted_id = await axiosGetQueryResultsPromise(
                    "SELECT max(id) as sid from surveys");
                console.log(get_last_inserted_id);
                if (get_last_inserted_id.data.statusCode == 200) {
                    new_survey_id = get_last_inserted_id.data.records[0]['lid'];
                    mult_values = "";
                    $(".selpoll").each(function(i, obj) {
                        mult_values += `, (${new_survey_id}, ${obj.dataset.pollid}, ${i+1}) `;
                    });
                }
            } else {
                sql_query = `UPDATE surveys set description = ${sql(descr)},
                                                      start_date = ${sql(start_at)}, 
                                                      end_date  = ${sql(end_at)}, 
                                                      url = ${sql(url)}, 
                                                      lang = '${lang}' where id = ${surveyId}`;
                const update_survey = await axiosExeqQuery(sql_query);
                sql_query = "DELETE from surveysdefinitions WHERE surveyId=" + surveyId;
                await axiosExeqQuery(sql_query);
                mult_values = "";
                $(".selpoll").each(function(i, obj) {
                    mult_values += `, (${surveyId}, ${obj.dataset.pollid}, ${i+1}) `;
                });
            }
            sql_query = `INSERT INTO surveysdefinitions VALUES ${mult_values.substring(1)}`;
            await axiosExeqQuery(sql_query);
        } else close();
    }

    function displayOption(stl) {
        document.getElementById("cntLabel2a").style.display = stl;
        document.getElementById("cntLabel2b").style.display = stl;
        document.getElementById("cntLabel2c").style.display = stl;
        document.getElementById("incr2").style.display = stl;
        document.getElementById("decr2").style.display = stl;
        document.getElementById("option").style.display = stl
    }

    function displayNoAnswers(stl) {
        document.getElementById("cntLabel1a").style.display = stl;
        document.getElementById("cntLabel1b").style.display = stl;
        document.getElementById("cntLabel1c").style.display = stl;
        document.getElementById("incr").style.display = stl;
        document.getElementById("decr").style.display = stl;
        document.getElementById("answers_no").style.display = stl
    }

    async function FillForm(survey_id) {
        saved_poll_ids = "";
        if (survey_id > -1) {
            let sql_str = "SELECT * FROM surveys where id=" + survey_id;
            const survey_info = await axiosGetQueryResultsPromise(sql_str);
            if (survey_info.data.statusCode == 200) {
                rec_data = survey_info.data.records[0];
                document.getElementById("description").value = rec_data['description'];
                if (rec_data['url']) document.getElementById("url").value = rec_data['url'];
                document.getElementById("start_at").value = rec_data['start_date'];
                if (rec_data['end_date']) document.getElementById("end_at").value = getTimeStamp(rec_data[
                    'end_date']);
            }

            let sql =
                "SELECT p.id as pid, p.description as descr, pk.description as kind from surveysdefinitions as sd INNER JOIN polls as p on sd.pollId = p.id INNER JOIN pollkinds as pk on p.kind = pk.id where surveyId = " +
                survey_id + " order by pollOrder";
            const result = await axiosGetQueryResultsPromise(sql);
            if (result.data.statusCode == 200) {
                const pollsOfSurvey = result.data.records;
                polls_html = "";
                for (j = 0; j < pollsOfSurvey.length; j++) {
                    p = pollsOfSurvey[j];
                    polls_html += `<div class="row">
                            <div id='id${j}' class="form-check">
                                <label data-pollid="${p['pid']}" class="selpoll form-check-label" data-sel="0" onclick="tongleSelected(this)" for="chkbox${j}">${p['descr']} (${p['kind']})</label>
                            </div>
                        </div>`;
                    saved_poll_ids += "," + p['pid'];
                }
                if (saved_poll_ids != "") {
                    saved_poll_ids = ` and p.id not in (${saved_poll_ids.substring(1)})`;
                }
                document.getElementById("selpolls").innerHTML = polls_html;
            }
        }
        let sql1 =
            "SELECT p.id as pid, p.description as descr, pk.description as kind, p.start_date, p.end_date, p.open, p.url"
        sql1 += " FROM polls as p INNER JOIN pollkinds as pk ON p.kind=pk.id where p.in_survey=false";
        if (saved_poll_ids != "") sql1 += saved_poll_ids;
        const result1 = await axiosGetQueryResultsPromise(sql1);
        polls_html = "";
        if (result1.data.statusCode == 200) {
            const polls = result1.data.records;
            for (j = 0; j < polls.length; j++) {
                p = polls[j];
                //<input class="m-2 form-check-input" type="checkbox" value="" data-pollid="${p['pid']}" id="chkbox${j}">
                polls_html += `<div class="row">
                            <div id='id${j}' class="form-check">
                                <label data-pollid="${p['pid']}" class="avpoll form-check-label" data-sel="0" onclick="tongleSelected(this)" for="chkbox${j}">${p['descr']} (${p['kind']})</label>
                            </div>
                        </div>`;
            }
            document.getElementById("avpolls").innerHTML = polls_html;
        } else {
            handleHttpRequestErrors(result1);
        }
    }

    function tongleSelected(el) {
        if (el.dataset.sel == 0) {
            el.classList.add('text-primary');
            el.dataset.sel = 1;
        } else {
            el.dataset.sel = 0;
            el.classList.remove('text-primary');
        }
    }

    function moveToSelected() {
        $(".avpoll").each(function(i, obj) {
            if (obj.dataset.sel == "1") {
                rem_div = obj.parentNode;
                parent_node = rem_div.parentNode
                parent_node.removeChild(rem_div);
                document.getElementById("selpolls").appendChild(rem_div);
                obj.classList.remove('text-primary');
                obj.classList.remove('avpoll');
                obj.classList.add('selpoll');
                obj.dataset.sel = 0;
            }
        });
    }

    function moveToAvailable() {
        $(".selpoll").each(function(i, obj) {
            if (obj.dataset.sel == "1") {
                rem_div = obj.parentNode;
                parent_node = rem_div.parentNode
                parent_node.removeChild(rem_div);
                document.getElementById("avpolls").appendChild(rem_div);
                obj.classList.remove('text-primary');
                obj.classList.add('avpoll');
                obj.classList.remove('selpoll');
                obj.dataset.sel = 0;
            }
        });
    }

    function moveChildUp() {
        console.log('moveUp');
        user_sel = 0;
        let selected_obj = null;
        $(".selpoll").each(function(i, obj) {
            console.log(obj);
            if (obj.dataset.sel == "1") {
                user_sel++;
                selected_obj = obj;
                if (user_sel > 1) {
                    console.log(0);
                    return;
                } //cannot move multiple selections
            }
        });
        if (selected_obj == null) {
            console.log('1');
            return;
        } //nothing is selected to move upward
        rem_div = selected_obj.parentNode;
        parent_node = rem_div.parentNode;
        prev_div = rem_div.previousSibling;
        if (prev_div == null) {
            console.log('2');
            return;
        } //top element cannot move upward
        parent_node.removeChild(rem_div);
        parent_node.insertBefore(rem_div, prev_div);
    }

    function insertAfter(newNode, existingNode) { //dom does not support insertAfter
        if (existingNode && existingNode.nextSibling)
            existingNode.parentNode.insertBefore(newNode, existingNode.nextSibling);
        else
            existingNode.parentNode.appendChild(newNode);
    }

    function moveChildDown() {
        console.log('moveDown');
        user_sel = 0;
        let selected_obj = null;
        $(".selpoll").each(function(i, obj) {
            if (obj.dataset.sel == "1") {
                user_sel++;
                selected_obj = obj;
                if (user_sel > 1) return; //cannot move multiple selections
            }
        });
        if (selected_obj == null) return; //nothing is selected to move upward
        rem_div = selected_obj.parentNode;
        parent_node = rem_div.parentNode;
        next_div = rem_div.nextSibling;
        if (next_div != null) { //bottom element cannot move downward
            parent_node.removeChild(rem_div);
            insertAfter(rem_div, next_div);
        }
    }
    </script>
</head>

<body onload="init();">
    <div id="dialog" title=""></div>
    <h2 class="bg-primary p-2">Edit survey</h2>
    <div class="m-4 p-4">
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
                    <!--<img src="https://img.icons8.com/fluency/512/worldwide-location.png" width="28" />-->
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
            <label class="form-label" for="url">url</label>
            <input type="text" class="form-control" id="url" placeholder="url">
        </div>

        <h2 class="bg-info p-2">Select polls</h2>
        <!--<a href="#" onclick="call_parent_test();">Test</a>-->
        <div class="row">
            <div class="col">Available</div>
            <div style="width:2%"></div>
            <div class="col">Selected</div>
        </div>
        <form>
            <div class="row">
                <div class="col border border-primary">
                    <div id="avpolls">
                    </div>
                </div>
                <div style="width:5%">
                    <center><span><i class='mt-4 fa fa-arrow-right' aria-hidden="true"
                                onclick="moveToSelected();"></i></span></center><br>
                    <center><span><i class='mt-2 fa fa-arrow-left' aria-hidden="true"
                                onclick="moveToAvailable();"></i></span></center>
                </div>
                <div class="col border border-primary">
                    <div id="selpolls">
                    </div>
                </div>
                <div style="width:5%">
                    <center><span><i class='mt-4 fa fa-arrow-up fa-2x' aria-hidden="true"
                                onclick="moveChildUp();"></i></span></center><br>
                    <center><span><i class='mt-2 fa fa-arrow-down fa-2x' aria-hidden="true"
                                onclick="moveChildDown()"></i></span></center>
                </div>
            </div>
        </form>
    </div>
    <button type="button" class="btn btn-primary" onclick="saveAndClose(true); ">Save survey</button>
    <button type="button" class="btn btn-secondary" onclick="saveAndClose(false)">Close</button>
</body>

</html>