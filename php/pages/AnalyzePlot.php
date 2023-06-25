<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged as an admin redirect to the login page.
if (!isset($_SESSION['loggedin1'])) {
	//header('Location: ../../index.html');
	//exit;
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Poll Analysis</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.2.0/css/all.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://gw.alipayobjects.com/os/lib/antv/g6/4.3.11/dist/g6.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/plugins/exporting.js"></script>
    <script src="//cdn.amcharts.com/lib/5/themes/Responsive.js"></script>
    <script type="text/x-mathjax-config">
        MathJax.Hub.Config({
            CommonHTML: {
                scale: 190
            }
        });
    </script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script type="module">
    import {
        LaTeXJSComponent
    } from "https://cdn.jsdelivr.net/npm/latex.js/dist/latex.mjs"
    customElements.define("latex-js", LaTeXJSComponent)
    </script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>
    <script src="../../js/pcm.js"></script>
    <script src="../../js/graphs.js"></script>
    <script src="../../js/charts.js"></script>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/graph.css">

    <script>
    var nodesInfo = [];
    var majorityMargin = [];
    var maxMM = 0;
    var relation = [];
    var mgGraph;
    var wmgGraph;
    var ecmgGraph;
    const ACCURACY = 2;
    var chart;
    var piesData = [];
    var candidateLabel = [];
    var Candidates = 0;
    var Descriptions = [];
    var prefProfileColors = ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee08b', '#d9ef8b', '#a6d96a', '#66bd63',
        '#1a9850', '#006837'
    ];

    function getUrlParams() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('id')) {
            pollId = urlParams.get('id');
        } else pollId = -1;
    }

    function truncate(num, digits) {
        return 1.0 * (Math.round(num * Math.pow(10, digits)) / Math.pow(10, digits));
    }

    function sortDataTable(table, id, order) {
        let p = (order == 'asc') ? 1 : -1;
        //console.log(id, order, p);
        //console.log(nodesInfo);
        let nodesInfo1;
        if (id == 'id_sd') {
            nodesInfo1 = nodesInfo.sort((a, b) => p * (a.simplifiedDogson - b.simplifiedDogson));
        } else if (id == 'id_etsd') {
            nodesInfo1 = nodesInfo.sort((a, b) => p * (a.etsd - b.etsd));
        } else if (id == 'id_cld') {
            nodesInfo1 = nodesInfo.sort((a, b) => p * (a.copeland - b.copeland));
        } else if (id == 'pcm_descr') {
            nodesInfo1 = nodesInfo.sort((a, b) => (a.info > b.info) ? p * 1 : p * (-1));
        } else if (id == 'pcm_abbrev') {
            nodesInfo1 = nodesInfo.sort((a, b) => p * (a.index - b.index));
        }
        for (var i = 1, row; row = table.rows[i]; i++) {
            row.cells[0].innerHTML = nodesInfo1[i - 1].label;
            row.cells[1].innerHTML = nodesInfo1[i - 1].info;
            row.cells[2].innerHTML = nodesInfo1[i - 1].simplifiedDogson;
            row.cells[3].innerHTML = truncate(nodesInfo1[i - 1].etsd, 2);
            row.cells[4].innerHTML = nodesInfo1[i - 1].copeland;
        }
    }

    function appendCol(row, txt, id = null) {
        var td = document.createElement("td");
        if (id) {
            td.id = id;
            td.innerHTML =
                `<span class="sorting" data-sortDir='' id="id_${id}">${txt}<span id=id_${id}_arrow></span></span>`;
        } else {
            td.innerHTML = txt;
        }
        row.appendChild(td);
    }

    function addScoreColumns(tableId) {
        var table = document.getElementById(tableId);
        var rows = table.rows;
        nodesInfo = nodesInfo.sort((a, b) => a.index - b.index);

        appendCol(rows[0], 'sd', 'sd');
        appendCol(rows[0], 'etsd', 'etsd');
        appendCol(rows[0], 'cld', 'cld');

        for (var i = 1; i < rows.length; ++i) {
            appendCol(rows[i], nodesInfo[i - 1].simplifiedDogson);
            appendCol(rows[i], truncate(nodesInfo[i - 1].etsd, 2));
            appendCol(rows[i], nodesInfo[i - 1].copeland);
        }
        rows[0].cells[0].classList.add('sorting');
        rows[0].cells[0].id = 'pcm_abbrev';
        rows[0].cells[0].setAttribute("data-sortDir", "");
        rows[0].cells[0].innerHTML += "<span id=pcm_abbrev_arrow></span>";

        rows[0].cells[1].classList.add('sorting');
        rows[0].cells[1].setAttribute("data-sortDir", "");
        rows[0].cells[1].id = 'pcm_descr';
        rows[0].cells[1].innerHTML += "<span id=pcm_descr_arrow></span>";

        var table = document.getElementById(tableId);
        var display_sorting_id = 1;
        $(".sorting").each(function(i, obj) {
            obj.addEventListener('click', function(event) {
                $(".sorting").each(function(j, obj_j) {
                    $("#" + this.id + "_arrow").html('');
                });
                sortDir = this.getAttribute("data-sortDir");
                if (sortDir.length == 0 || sortDir == "desc") {
                    this.setAttribute("data-sortDir", "asc");
                    $("#" + this.id + "_arrow").html(` <i class="fa-solid fa-arrow-up">`);
                    sortDataTable(table, this.id, "asc");
                } else {
                    this.setAttribute("data-sortDir", "desc");
                    $("#" + this.id + "_arrow").html(` <i class="fa-solid fa-arrow-down">`);
                    sortDataTable(table, this.id, "desc");
                }
                display_sorting_id = this.id;
            });
        });
    }

    function createPie(ind, values, kind) {
        let data = piesData[ind];
        let prefix = kind == RANKING ? "Ranked:" : "Rated:";
        for (k = 0; k < values.length; k++) {
            data[k].label = prefix + (k + 1);
            data[k].val = values[k];
            data[k].color = prefProfileColors[k + 1];
        }
        return pie("pie_" + ind, data, questions[ind]);
    }



    async function init() {
        initDialog('dialog');
        getUrlParams(); //extracts pollId from url;
        const result = await axiosGetQueryResultsPromise("SELECT * FROM polls where id=" + pollId);
        if (result.data.statusCode == 200) {
            const poll_descr = result.data.records[0];
            //Open: "1", defVariable: "5", description: "Rank the following", end_date: null,  id: "0"
            //in_survey: "0", kind: "2", start_date: "2022-10-11 13:26:13"
            //questions: "Selection1$-%@$Selection2$-%@$Selection3$-%@$Selection4"
            let kind = poll_descr['kind'];
            if (poll_descr['questions'])
                questions = poll_descr['questions'].split(delim);
            else questions = ['Yes/No'];
            table_pcm_index_html_str = "";
            document.getElementById("poll_descr").innerHTML = poll_descr['description'];
            let pcm_index_body = document.getElementById("pcm_index").getElementsByTagName('tbody')[0];
            pcm_str = "";
            let pcm_title_row_str = "";
            prefprof_title_row_str = "";
            piesData = new Array(questions.length).fill(0).map(() => Array.from({
                length: questions.length
            }, () => ({
                label: '',
                val: 0,
                color: ''
            })));

            let pie_str = "";
            for (k = 0; k < questions.length; k++) {
                pie_str += `<div id='pie_${k}' style="width:100%;height:600px;"></div>`;
                let lbl = String.fromCharCode(65 + k);
                candidateLabel.push(lbl);
                let row_str = `<tr><td>${lbl}</td><td>${questions[k]}</td></tr>`;
                if (kind != YES_NO) {
                    pcm_title_row_str += `<td id="qhor_${k+1}">${lbl}</td>`;
                    table_pcm_index_html_str += row_str;
                    pcm_str +=
                        `<tr id="row_${k+1}"><td id="qver_${k+1}">${lbl}</td>${"<td></td>".repeat(questions.length)}</tr>`;
                }
                prefprof_title_row_str += `<td id="ppqhor_${k+1}">${lbl}</td>`;
            }
            document.getElementById("other_plots").innerHTML = pie_str;
            Candidates = candidateLabel.length;
            if (kind != YES_NO) {
                pcm_index_body.innerHTML = table_pcm_index_html_str;
                pcm_title_row_str = "<tr><td></td>" + pcm_title_row_str + "</tr>";
                pcm_str = pcm_title_row_str + pcm_str;
                document.getElementById("pcm_table").innerHTML = pcm_str;
                for (k = 0; k < questions.length; k++) {
                    cells = document.getElementById("row_" + (k + 1)).children;
                    for (var j = 1; j < cells.length; j++) {
                        cells[j].id = getPCMCellId(k + 1, j);
                    }
                }
            }
            var imgs = [];
            /*
            var imgs = [
                "https://www.amcharts.com/lib/images/faces/C02.png",
                "https://www.amcharts.com/lib/images/faces/A04.png",
                "https://www.amcharts.com/lib/images/faces/D02.png",
                "https://www.amcharts.com/lib/images/faces/E01.png",
                "https://www.amcharts.com/lib/images/faces/E02.png",
                "https://www.amcharts.com/lib/images/faces/E03.png"
            ];*/

            if (kind != YES_NO) {
                document.getElementById("pref_prof_index").innerHTML = document.getElementById("pcm_index")
                    .innerHTML;
                document.getElementById("pcm_index_ranking").innerHTML = document.getElementById("pcm_index")
                    .innerHTML;
            }
            const answer_set = await axiosGetQueryResultsPromise(
                "SELECT * FROM usercompletedpolls where pollid=" +
                pollId);
            if (answer_set.data.statusCode == 200) {
                const answers = answer_set.data.records;
                //console.log(answers);
                var Avgs = [...Array(questions.length)].fill(0);
                if (kind!=YES_NO) var Cnts = new Array(questions.length).fill(0).map(() => new Array(questions.length).fill(0));
                prefprof_str = "";
                for (k = 0; k < answers.length; k++) {
                    ans = answers[k].answers.split(',');
                    row = `<tr><td>#${k+1}</td>`
                    prev = 1;
                    let a = 0;
                    //console.log(ans);
                    for (j = 0; j < ans.length; j++) {
                        a = parseInt(ans[j]);
                        if (kind!=YES_NO) Cnts[a - 1][j]++;
                        //console.log(a + "-->" + (j + 1));
                        let b = a;
                        if (kind == YES_NO) {
                            b = a ? 'Yes' : 'No';
                            Avgs[0] += a;
                        } else if (kind == APPROVAL) {
                            b = 'Yes';
                            tm = a - prev;
                            if (tm >= 0) row += "<td></td>".repeat(tm);
                            prev = a + 1;
                            row += `<td><i class="fa-solid fa-flag-checkered"></i></td>`;
                            Avgs[a - 1]++;
                        } else {
                            row +=
                                `<td style="background-color:${quantifiedPrefProfileColor(a, questions.length)}">${b}</td>`;
                            Avgs[j] += a;
                        }
                    }
                    if (kind == APPROVAL)
                        row += "<td></td>".repeat(questions.length - a);
                    row += "</tr>";
                    prefprof_str += row;
                }
                document.getElementById("pref_prof").innerHTML = "<td width='5%'></td>" +
                    prefprof_title_row_str +
                    prefprof_str;
                if (kind == APPROVAL||kind==RATING) {
                        //console.log(answers.length);
                        //console.log(Avgs);
                    for (k = 0; k < Avgs.length; k++)
                        Avgs[k] = Math.floor(Math.pow(10, ACCURACY) * Avgs[k] / answers.length) / Math
                        .pow(10, ACCURACY);
                    //document.getElementById("tb1").classList.add('disabled');
                    //document.getElementById("tb2").classList.add('disabled');
                    //document.getElementById("tb3").classList.add('disabled');
                    //document.getElementById("tb5").classList.add('disabled');
                    document.getElementById("li1").remove();
                    document.getElementById("li2").remove();
                    document.getElementById("li3").remove();
                    document.getElementById("li5").remove();
                    let title="Average rating per answer";
                    if (kind==APPROVAL) title="Number of votes per answer";
                    chart = plot('chartdiv', questions, Avgs, title);
                } else if (kind != YES_NO) {
                    console.log("Cnts");
                    colorizePCM(questions, answers);
                    for (k = 0; k < Avgs.length; k++)
                        Avgs[k] = Math.floor(Math.pow(10, ACCURACY) * Avgs[k] / answers.length) / Math.pow(10,
                            ACCURACY);
                    chart = plot('chartdiv', questions, Avgs, "Average grade per answer");
                    $("#plot_descr").html("Average grade per answer");
                    for (n = 0; n < Cnts.length; n++) {
                        createPie(n, Cnts[n], kind);
                    }
                    await graph_wmg();
                    graph_ecmg();
                    addScoreColumns('pcm_index');
                    await computeRankingBasedSemantics();
                    //console_log(nodesInfo);
                    //testgraph();
                } else {
                    questions = ['Yes', 'No'];
                    //Yes as 1 have been accumulated in Avgs[0]. The rest up to answers.length is No (0)
                    Avgs.push(answers.length - Avgs[0]);
                    //document.getElementById("li0").classList.add('disabled');
                    //document.getElementById("tb2").classList.add('disabled');
                    //document.getElementById("tb3").classList.add('disabled');
                    //document.getElementById("tb0").classList.add('disabled');
                    //document.getElementById("tb5").classList.add('disabled');
                    document.getElementById("plots_tab").classList.add('show');
                    document.getElementById("plots_tab").classList.add('active');
                    document.getElementById("prefprof_tab").remove();
                    document.getElementById("li0").remove();
                    document.getElementById("li1").remove();
                    document.getElementById("li2").remove();
                    document.getElementById("li3").remove();
                    document.getElementById("li5").remove();
                    // document.getElementById("li5").remove();
                    chart = pie('chartdiv', [{
                        'label': 'Yes',
                        'val': Avgs[0],
                        'color': 0xe06543
                    }, {
                        'label': 'No',
                        'val': Avgs[1],
                        'color': 0x087f8c
                    }]);
                    $("#plot_descr").html("Perentages per answer");
                }
            }
        }
    }

    function closeFrm() {
        close();
    }
    </script>
</head>

<body class="p-3 m-0 border-0 bd-example" style="background-color:rgb(211, 211, 211)" onload="init();">
    <div id="dialog" title=""></div>
    <div class="row p-2 " style="background-color:rgb(211, 211, 211)">
        <div class="col">
            <h3>Visualization of poll</h3>
        </div>
        <div class="col">
            <a class="printbtn text-white" onclick="window.print();" target="_blank"></a>
        </div>
    </div>
    <div class="row" style="background-color:rgb(132, 136, 132)">
        <h5 id="poll_descr" class="p-2"></h5>
    </div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li id="li0" class="nav-item" role="presentation">
            <button id="tb0" class="nav-link active" id="prefprof-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#prefprof_tab" type="button" role="tab" aria-controls="users1"
                aria-selected="true">Preference Profile</button>
        </li>
        <li id="li1" class="nav-item" role="presentation">
            <button id="tb1" class="nav-link" id="home-tab" onclick="currentTab=SURVEY;" data-bs-toggle="tab"
                data-bs-target="#pcm_tab" type="button" role="tab" aria-controls="home" aria-selected="false">Pairwise
                Comparison Matrix</button>
        </li>
        <li id="li2" class="nav-item" role="presentation">
            <button id="tb2" class="nav-link" id="profile-tab" onclick="currentTab=POLL;" data-bs-toggle="tab"
                data-bs-target="#mg_tab" type="button" role="tab" aria-controls="profile" aria-selected="false">Weighted
                Majority Graph</button>
        </li>
        <li id="li3" class="nav-item" role="presentation">
            <button id="tb3" class="nav-link" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#ecmg_tab" type="button" role="tab" aria-controls="users"
                aria-selected="false">Edge-Compressed Majority Graph</button>
        </li>
        <li id="li4" class="nav-item" role="presentation">
            <button id="tb4" class="nav-link" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#plots_tab" type="button" role="tab" aria-controls="users2"
                aria-selected="false">Plots</button>
        </li>
        <li id="li5" class="nav-item" role="presentation">
            <button id="tb5" class="nav-link" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#ranking_tab" type="button" role="tab" aria-controls="users2"
                aria-selected="false">Ranking-Based Semantics</button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="prefprof_tab" role="tabpanel" aria-labelledby="prefprof-tab">
            <table id="pref_prof_index" class="table data_table w-auto" style="background-color:rgb(161, 240, 197)">
            </table>
            <table id="pref_prof" class="table data_table" style="background-color:rgb(161, 240, 197)"></table>
        </div>
        <div class="tab-pane fade" id="pcm_tab" role="tabpanel" aria-labelledby="pcm-tab">
            <table id="pcm_index" class="table data_table w-auto" style="background-color:rgb(161, 240, 197)">
                <thead>
                    <tr>
                        <th style="width:10%">Abbrev</th>
                        <th>Full description</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <i class="text-success">(Options in table have been ordered in ascending ETSD score of each option)</i>
            <table id="pcm_table" class="table data_table pcm " style="background-color:rgb(161, 230, 207)"></table>
        </div>
        <div class="tab-pane fade" id="mg_tab" role="tabpanel" aria-labelledby="wmg-tab">
            <div class="row">
                <div class="col p-3 mb-2 bg-success bg-gradient text-white">
                    <h4 class="p-2"><span class="text-warning">Majority Graph</span><span id='info_txt1'></span></h4>
                </div>
                <div class="d-grid gap-2 d-md-block">
                    <button class="btn btn-info" type="button"
                        onclick="mgGraph.downloadImage(fn('mg'), 'image/png', 'white')">SaveImage (MG)</button>
                </div>
                <div id="mg"></div>
            </div>
            <div class="row">
                <div class="col p-3 mb-2 bg-success bg-gradient text-white">
                    <h4 class="p-2"><span class="text-warning">Weighted Majority Graph</span><span
                            id='info_txt2'></span></h4>
                </div>
                <div class="d-grid gap-2 d-md-block">
                    <button class="d-flex btn btn-info" type="button"
                        onclick="wmgGraph.downloadImage(fn('wmg'), 'image/png', 'white');">Save Image (WMG)</button>
                </div>
                <div id="wmg"></div>
            </div>
        </div>
        <div class="tab-pane fade" id="ecmg_tab" role="tabpanel" aria-labelledby="ecmg-tab">
            <div class="row">
                <div class="col p-3 mb-2 bg-success bg-gradient text-white">
                    <h4 class="p-2"><span class="text-warning">Edge Compressed Majority Graph</span><span
                            id='info_txt2'></span></h4>
                </div>
                <div class="d-grid gap-2 d-md-block">
                    <button class="btn btn-info" type="button"
                        onclick="ecmgGraph.downloadImage(fn('ecmg'), 'image/png', 'white')">SaveImage (ECMG)</button>
                </div>
                <div id="ecmg"></div>
                <div id="testmg"></div>
            </div>
        </div>
        <div class="tab-pane fade" id="plots_tab" role="tabpanel" aria-labelledby="plots-tab">
            <div class="row">
                <div class="col p-3 mb-2 bg-success bg-gradient text-white">
                    <h4 class="p-2"><span class="text-warning" id="plot_descr">Poll chart</span><span
                            id='info_txt2'></span></h4>
                </div>
                <div id="chartdiv"></div>
                <div id="other_plots"></div>
            </div>

        </div>
        <div class="tab-pane fade" id="ranking_tab" role="tabpanel" aria-labelledby="plots-tab">
            <table id="pcm_index_ranking" class="table data_table w-auto" style="background-color:rgb(161, 240, 197)">
                <thead>
                    <tr>
                        <th style="width:10%">Abbrev</th>
                        <th>Full description</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div class="col p-3">
                <h4>Scores</h4>
            </div>
            <table class="table" style="background-color:rgb(161, 240, 197)">
                <tr>
                    <td style="width:15%">Copeland</td>
                    <td id="copeland_score"></td>
                </tr>
                <tr>
                    <td>Simplified Dogson</td>
                    <td id="sdogson_score"></td>
                </tr>
                <tr>
                    <td>ETSD</td>
                    <td id="etsd_score"></td>
                </tr>
            </table>
            <div class="col p-3">
                <h4>Ranking-Based Semantics</h4>
            </div>
            <table class="table" style="background-color:rgb(161, 240, 197)">
                <tr>
                    <td style="width:15%">Burden</td>
                    <td id="bbs_score"></td>
                </tr>
                <tr>
                    <td>Categorizer</td>
                    <td id="cat_score"></td>
                </tr>
                <tr>
                    <td>Discussion</td>
                    <td id="dbs_score"></td>
                </tr>
            </table>
        </div>
</body>

</html>