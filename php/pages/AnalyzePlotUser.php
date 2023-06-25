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
            document.getElementById("poll_descr").innerHTML = poll_descr['description'];
            var imgs = [];

            const answer_set = await axiosGetQueryResultsPromise(
                "SELECT * FROM usercompletedpolls where pollid=" +
                pollId);
            if (answer_set.data.statusCode == 200) {
                const answers = answer_set.data.records;
                var Avgs = [...Array(questions.length)].fill(0);
                for (k = 0; k < answers.length; k++) {
                    ans = answers[k].answers.split(',');
                    prev = 1;
                    let a = 0;
                    for (j = 0; j < ans.length; j++) {
                        a = parseInt(ans[j]);
                        let b = a;
                        if (kind == YES_NO) {
                            b = a ? 'Yes' : 'No';
                            Avgs[0] += a;
                        } else if (kind == APPROVAL) {
                            b = 'Yes';
                            tm = a - prev;
                            prev = a + 1;
                            Avgs[a - 1]++;
                        } else {
                            Avgs[j] += a;
                        }
                    }
                }
                if (kind == APPROVAL) {
                    for (k = 0; k < Avgs.length; k++)
                        Avgs[k] = 100 * Math.floor(Math.pow(10, ACCURACY) * Avgs[k] / answers.length) / Math
                        .pow(10,
                            ACCURACY);

                    chart = plot('chartdiv', questions, Avgs, imgs);
                } else if (kind != YES_NO) {

                    for (k = 0; k < Avgs.length; k++)
                        Avgs[k] = Math.floor(Math.pow(10, ACCURACY) * Avgs[k] / answers.length) / Math.pow(10,
                            ACCURACY);
                    chart = plot('chartdiv', questions, Avgs, imgs);
                    $("#plot_descr").html("Average grade per answer");
                } else {
                    questions = ['Yes', 'No'];
                    //Yes as 1 have been accumulated in Avgs[0]. The rest up to answers.length is No (0)
                    $("#plot_descr").html("Perentages per answer");
                    Avgs.push(answers.length - Avgs[0]);
                    chart = pie('chartdiv', [{
                        'label': 'Yes',
                        'val': Avgs[0],
                        'color': 0xe06543
                    }, {
                        'label': 'No',
                        'val': Avgs[1],
                        'color': 0x087f8c
                    }]);
                }
            }
        }
    }

    function closeFrm() {
        close();
    }
    </script>
</head>

<body class="p-3 m-0 border-0 bd-example" style="background-color:rgb(192, 192, 192)" onload="init();">
    <div id="dialog" title=""></div>
    <div class="row p-2" style="background-color:rgb(128, 128, 128)">
        <div class="col">
            <h5>Visualization of poll</h5>
        </div>
    </div>
    <div class="row bg-dark text-white" style="background-color:rgb(255, 153, 153)">
        <h3 id="poll_descr" class="p-2"></h3>
    </div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#plots_tab" type="button" role="tab" aria-controls="users2"
                aria-selected="false">Plots</button>
        </li>
        <!--<li class="nav-item" role="presentation">
            <button class="nav-link" id="user-tab" onclick="currentTab=USERS;" data-bs-toggle="tab"
                data-bs-target="#ranking_tab" type="button" role="tab" aria-controls="users2"
                aria-selected="false">Ranking-Based Semantics</button>
        </li>-->
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="plots_tab" role="tabpanel" aria-labelledby="plots-tab">
            <div class="row">
                <div class="col p-3  bg-secondary text-dark">
                    <h6><span id="plot_descr">Poll chart</span><span id='info_txt2'></span></h6>
                </div>
                <div id="chartdiv"></div>
            </div>

        </div>
</body>

</html>