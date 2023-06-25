"use strict";

var nodeColors = [
    "#68bdf6", // light blue
    "#6dce9e", // green #1
    "#faafc2", // light pink
    "#ff928c", // light red
    "#fcea7e", // light yellow
    "#ffc766", // light orange
    "#405f9e", // navy blue
    "#a5abb6", // dark gray
    "#78cecb", // green #2,
    "#f2baf6", // purple
    "#b88cbb", // dark purple
    "#ced2d9", // light gray
    "#e84646", // dark red
    "#fa5f86", // dark pink
    "#ffab1a", // dark orange
    "#fcda19", // dark yellow
    "#797b80", // black
    "#c9d96f", // pistacchio
    "#47991f", // green #3
    "#70edee", // turquoise
    "#ff75ea", // pink
];

//https://colorbrewer2.org/?type=diverging&scheme=RdYlBu&n=8
var ecmgColors = [
    "#d73027",
    "#f46d43",
    "#fdae61",
    "#fee090",
    "#e0f3f8",
    "#abd9e9",
    "#74add1",
    "#4575b4",
];

var widthLevels = 5;
var maxSimplifiedDogson = 0;
var minSimplifiedDogson = 0;

var maxNodeDiameter = 60;
var minNodeDiameter = 30;
var minETSD = 0.0;
var maxETSD = 0.0;

const VERBOSE = false;

var maxPathLength; //maximum path length in discusion burden semantics

var times = 10; // times to repeat the loop in burder based semantics
var R1Minus = [];
var Cat = [];
var Bur = [];
var Dis = [];

function fn(name) {
    let date = new Date().toJSON().replace(".", "_");
    return name + date;
}

function console_log(obj) {
    if (VERBOSE) console.log(obj);
}

function quantify(a, b, c, d, x) {
    //linear map of [a,b] to [c, d]
    let l = (1.0 * (d - c)) / (b - a);
    let m = (1.0 * (c * b - d * a)) / (b - a);
    let ret_val = Math.floor(l * x + m);
    return ret_val;
}

function quantifiedNodeDiameter(x) {
    return quantify(minETSD, maxETSD, minNodeDiameter, maxNodeDiameter, x);
}

function quantifiedWeight(x) {
    return quantify(minSimplifiedDogson, maxSimplifiedDogson, 1, widthLevels, x);
}

function quantifiedECDMNodeColors(x) {
    return ecmgColors[7 - quantify(minETSD, maxETSD, 0, 7, x)]; //blue at the end of the table and it should be assigned to lower etsd score
}

function quantifiedPrefProfileColor(x, max) {
    return prefProfileColors[
        prefProfileColors.length -
        quantify(1, max, 0, prefProfileColors.length - 1, x)
    ]; //green for low values red for upper invert table colors
}

function createMGGraph(container_div, g_width, g_height, initData) {
    var container = document.getElementById(container_div);
    var graph = new G6.Graph({
        container,
        width: g_width, // Number, required, the width of the graph
        height: g_height, // Number, required, the height of the graph
        //fitView: true,
        animate: true,
        //fitViewPadding: [20, 40, 50, 20],
        groupByTypes: false,
        modes: {
            default: [{
                    type: "tooltip", // Tooltip
                    formatText(model) {
                        // The content of tooltip
                        const text = "label: " + model.label + "<br/>" + model.info;
                        return text;
                    },
                    offset: 10,
                },
                {
                    type: "edge-tooltip", // Edge tooltip
                    formatText(model) {
                        // The content of the edge tooltip
                        const text =
                            "source: " +
                            model.source +
                            "<br/> target: " +
                            model.target +
                            "<br/> weight: " +
                            model.weight;
                        return text;
                    },
                    offset: -20,
                },
                "drag-canvas",
                "drag-combo",
                "zoom-canvas",
                {
                    //type: 'activate-relations',
                    resetSelected: true,
                },
                "drag-node",
            ], // Allow users to drag canvas, zoom canvas, and drag nodes
        },
        defaultNode: {
            size: 35,
            labelCfg: {
                positions: "center",
                style: {
                    fontSize: 10,
                    fill: "#fff",
                },
            },
        },
        defaultEdge: {
            labelCfg: {
                autoRotate: true,
                type: "quadratic",
                style: {
                    fontSize: 10,
                    fill: "#fff",
                },
            },
        },

        // The node styles in different states
        nodeStateStyles: {
            // The node style when the state 'hover' is true
            hover: {
                fill: "lightsteelblue",
            },
            // The node style when the state 'click' is true
            click: {
                stroke: "#000",
                lineWidth: 1,
            },
        },
        style: {
            endArrow: {
                path: G6.Arrow.triangle(10, 20, 25), // Using the built-in edges for the path, parameters are the width, length, offset (0 by default, corresponds to d), respectively
                d: 25,
            },
            startArrow: {
                path: G6.Arrow.vee(15, 20, 15), // Using the built-in edges for the path, parameters are the width, length, offset (0 by default, corresponds to d), respectively
                d: 15,
            },
        },
        layout: {
            type: "force",
            preventOverlap: true,
            linkDistance: 100, // The link distance is 100
        },
    });
    let col = 0;
    initData.nodes.forEach((node) => {
        if (!node.style) {
            node.style = {};
        }
        node.style.lineWidth = 1;
        node.style.stroke = "#666";
        node.style.fill = nodeColors[col++];
        switch (node.class) {
            case "c0":
                {
                    node.type = "circle";
                    break;
                }
            case "c1":
                {
                    node.type = "rect";
                    node.size = [35, 20];
                    break;
                }
            case "c2":
                {
                    node.type = "ellipse";
                    node.size = [35, 20];
                    break;
                }
        }
    });

    initData.edges.forEach((edge) => {
        if (!edge.style) {
            edge.style = {};
        }
        edge.style.lineWidth = edge.weight;
        edge.style.opacity = 0.6;
        edge.style.stroke = "grey";
    });

    graph.on("node:mouseenter", (e) => {
        const nodeItem = e.item; // Get the target item
        document.getElementById("info_txt1").innerHTML =
            "   (" + nodeItem._cfg.model.info + ")";
        document.getElementById("info_txt2").innerHTML =
            "   (" + nodeItem._cfg.model.info + ")";
        graph.setItemState(nodeItem, "hover", true); // Set the state 'hover' of the item to be true
    });
    graph.on("node:mouseleave", (e) => {
        const nodeItem = e.item; // Get the target item
        document.getElementById("info_txt1").innerHTML = "";
        document.getElementById("info_txt2").innerHTML = "";
        graph.setItemState(nodeItem, "hover", false); // Set the state 'hover' of the item to be false
    });

    // Click a node
    graph.on("node:click", (e) => {
        // Swich the 'click' state of the node to be false
        const clickNodes = graph.findAllByState("node", "click");
        clickNodes.forEach((cn) => {
            graph.setItemState(cn, "click", false);
        });
        const nodeItem = e.item; // et the clicked item
        graph.setItemState(nodeItem, "click", true); // Set the state 'click' of the item to be true
    });

    // Click an edge
    graph.on("edge:click", (e) => {
        // Swich the 'click' state of the edge to be false
        const clickEdges = graph.findAllByState("edge", "click");
        clickEdges.forEach((ce) => {
            graph.setItemState(ce, "click", false);
        });
        const edgeItem = e.item; // Get the clicked item
        graph.setItemState(edgeItem, "click", true); // Set the state 'click' of the item to be true
    });

    graph.on("wheelzoom", (e) => {
        e.stopPropagation();
        // className g6-component-tooltip by default
        const tooltips = Array.from(
            document.getElementsByClassName("g6-component-tooltip")
        );
        tooltips.forEach((tooltip) => {
            if (tooltip && tooltip.style) {
                tooltip.style.transform = `scale(${graph.getZoom()})`;
            }
        });
    });

    /*
      graph.on('canvas:click', (evt) => {
          graph.getCombos().forEach((combo) => {
              graph.clearItemStates(combo);
              console_log(combo);
          });
      });*/

    graph.on("afterlayout", () => {
        graph.fitView();
    });

    if (typeof window !== "undefined") {
        window.onresize = () => {
            if (!graph || graph.get("destroyed")) return;
            container = document.getElementById(container_div);
            if (!container || !container.scrollWidth || !container.scrollHeight)
                return;
            graph.changeSize(container.scrollWidth, container.scrollHeight);
        };
    }

    graph.data(initData); // Load the data defined in Step 2
    graph.render(); // Render the graph
    return graph;
}

async function graph_wmg() {
    const initData = {
        nodes: [], // The array of nodes
        edges: [], // The array of edges
    };

    R1Minus = [];
    Cat = [];
    Bur = [];
    for (i = 0; i < Candidates; i++) {
        R1Minus[nodesInfo[i].label] = [];
        Cat[nodesInfo[i].label] = -1;
        Bur[nodesInfo[i].label] = [];
    }

    for (i = 0; i < Candidates; i++) {
        var new_node = {
            id: nodesInfo[i].label,
            label: nodesInfo[i].label,
            class: "c3",
            info: nodesInfo[i].info,
        };

        initData.nodes.push(new_node);
        for (j = 0; j < Candidates; j++)
            if (
                i != j &&
                majorityMargin[nodesInfo[i].index][nodesInfo[j].index] >= 0
            ) {
                var new_edge = {
                    source: nodesInfo[i].label, // String, required, the id of the source node
                    target: nodesInfo[j].label, // String, required, the id of the target node
                    label: "", // The label of the edge
                    type: "quadratic",
                    weight: 1,
                    style: {
                        endArrow: true,
                        startArrow: false,
                    },
                };
                initData.edges.push(new_edge);
                R1Minus[new_edge.target].push(new_edge.source);
            }
    }

    mgGraph = createMGGraph("mg", 800, 600, initData);

    //Weigthed Majority Graph
    initData.edges = [];
    initData.nodes = [];

    simplifiedDogsonScore();

    maxSimplifiedDogson = nodesInfo[nodesInfo.length - 1].simplifiedDogson;
    minSimplifiedDogson = nodesInfo[0].simplifiedDogson;

    let init_x = 100;
    let init_y = 40;
    let dx = 80;
    let dy = 80;

    let x = init_x;
    let y = init_y;
    let prev_val = nodesInfo[0].simplifiedDogson;
    for (i = 0; i < Candidates; i++) {
        let val = nodesInfo[i].simplifiedDogson;
        if (val == prev_val) x += dx;
        else {
            x = init_x;
            y += dy;
        }
        var new_node = {
            id: nodesInfo[i].label,
            x: x,
            y: y,
            label: nodesInfo[i].label,
            class: "c3",
            info: nodesInfo[i].info,
        };
        initData.nodes.push(new_node);
        for (j = 0; j < Candidates; j++)
            if (
                i != j &&
                majorityMargin[nodesInfo[i].index][nodesInfo[j].index] >= 0
            ) {
                var new_edge = {
                    source: nodesInfo[i].label, // String, required, the id of the source node
                    target: nodesInfo[j].label, // String, required, the id of the target node
                    label: "", // The label of the edge
                    type: "quadratic",
                    weight: quantifiedWeight(nodesInfo[i].simplifiedDogson),
                    style: {
                        endArrow: true,
                        startArrow: false,
                    },
                };
                initData.edges.push(new_edge);
            }
    }
    wmgGraph = createMGGraph("wmg", 800, 600, initData);
}

function FindMaximalCandidates() {
    relation = [...Array(Candidates)].map((_) => Array(Candidates).fill(0));
    for (i = 0; i < Candidates; i++) {
        let ind_i = nodesInfo[i].index;
        for (j = 0; j < Candidates; j++) {
            let ind_j = nodesInfo[j].index;
            if (ind_i != ind_j) {
                if (majorityMargin[ind_i][ind_j] > 0) relation[ind_i][ind_j] = 2;
                else if (majorityMargin[ind_i][ind_j] == 0) relation[ind_i][ind_j] = 1;
                else relation[ind_i][ind_j] = 0;
                nodesInfo[i].copeland += relation[ind_i][ind_j];
            }
        }
    }
}

function computeSmithSets() {
    //let m = nodesInfo.length;
    let current_cluster = 1;
    let col_start = 0,
        ind = 0,
        init_col = 0,
        current_node;
    let add_nodes = false,
        nodesNotInACluster = true;
    let c;
    while (nodesNotInACluster) {
        //search for the first non-clustered node to compute its smith set
        ind = nodesInfo.findIndex((element) => element.cluster === "");
        //console_log("----0>" + ind);
        if (ind > -1) {
            //found some
            add_nodes = true;
            console_log(
                "find an non-clustered init node : " +
                nodesInfo[ind].label +
                " --> cluster = " +
                current_cluster
            );
            while (add_nodes) {
                //while add new undefeated nodes to the same smith se
                nodesInfo[ind].cluster = current_cluster;
                init_col = ind; //arranging column start
                current_node = ind + 1;
                //phase A : add all nodes with the same copeland score
                current_node = -1;
                nodesInfo
                    .filter(function(node) {
                        return (
                            node.copeland === nodesInfo[ind].copeland && node.cluster === ""
                        );
                    })
                    .map((node, index) => {
                        console_log(
                            "\tfind an non-clustered node : " +
                            node.label +
                            " --> cluster = " +
                            current_cluster
                        );
                        node.cluster = current_cluster;
                        current_node = index;
                    });
                //phase B: search for undefeated nodes in 'relation' table below those already added in phase A
                add_nodes = false;
                //console_log("A1");
                if (current_node < 0) break;
                current_node++;
                //console_log("A2")
                //console_log(current_node);
                for (let row = Candidates - 1; row >= current_node; row--) {
                    let ind_i = nodesInfo[row].index;
                    add_nodes = false;
                    for (let col = col_start; col < current_node - 1; col++) {
                        let ind_j = nodesInfo[col].index;
                        if (relation[ind_i][ind_j] > 0) {
                            add_nodes = true;
                            break;
                        }
                    }
                    if (add_nodes) {
                        //node 'row' is undefeated by some nodes already in smith set.
                        for (let k = row - 1; k >= current_node; k--) {
                            //add nodes from row-1 down to current_node down to row to the current smith set
                            //console_log('\t(Phase B)find an non-clustered node : ' + nodesInfo[k].label + " --> cluster = " + current_cluster);
                            nodesInfo[k].cluster = current_cluster;
                        }
                        ind = row; //to add nodes below 'row' having the same score with  'row' node
                        break;
                    }
                    col_start = current_node;
                }
                //console_log("1---->" + current_cluster + "," + add_nodes);
            }
            current_cluster++;
            //console_log("2---->" + current_cluster);
        } else nodesNotInACluster = false;
    }
    return current_cluster - 1;
}

function MathCode(id, latex_str, info = null) {
    let equals = false;
    if (info) {
        latex_str = "\\[" + candidateLabel[0];
        for (let k = 1; k < candidateLabel.length; k++) {
            equals = false;
            if (info == "Bbs") {
                equals = lex(Bur[candidateLabel[k - 1]], Bur[candidateLabel[k]]) == 0;
            } else if (info == "Cat") {
                equals = Cat[candidateLabel[k - 1]] == Cat[candidateLabel[k]];
            } else {
                //Dbs
                equals = lex(Dis[candidateLabel[k - 1]], Dis[candidateLabel[k]]) == 0;
            }
            if (equals) latex_str += `\\succeq^{${info}}_{AF} ${candidateLabel[k]}`;
            else latex_str += `\\succ^{${info}}_{AF} ${candidateLabel[k]}`;
        }
        latex_str += "\\]";
    }
    const node = document.getElementById(id);
    MathJax.typesetClear([node]);
    node.innerHTML = latex_str;
    MathJax.typesetPromise([node]);
}

function simplifiedDogsonScore() {
    nodesInfo.sort((a, b) => a.simplifiedDogson - b.simplifiedDogson); //ascending sorting  according to simplifiedDogson property;
    let prev = nodesInfo[0].simplifiedDogson;
    let latex_str = "\\[" + nodesInfo[0].label;
    for (k = 1; k < nodesInfo.length; k++) {
        if (prev < nodesInfo[k].simplifiedDogson)
        //\\succ
            latex_str += `>^{sdg}_{AF} ${nodesInfo[k].label}`;
        else latex_str += `=^{sdg}_{AF} ${nodesInfo[k].label}`;
        prev = nodesInfo[k].simplifiedDogson;
    }
    latex_str += "\\]";
    MathCode("sdogson_score", latex_str);
}

function printRelationMatrix() {
    console_log("------------------- Relation Matrix ------------------------");
    let nd = "",
        str = "";
    let nd_score = 0,
        ind_i = 0,
        ind_j = 0;
    for (let i = 0; i < Candidates; i++) {
        nodesInfo[i].cluster = ""; //adding cluster field for ecmg allocation of a node in a cluster;
        nd = nodesInfo[i].label + "|";
        nd_score = "|" + nodesInfo[i].copeland;
        ind_i = nodesInfo[i].index;
        str = "";
        for (let j = 0; j < Candidates; j++) {
            ind_j = nodesInfo[j].index;
            str += "," + relation[ind_i][ind_j];
        }
        console_log(nd + str.substring(1) + nd_score);
    }
}

function graph_ecmg() {
    FindMaximalCandidates();
    copelandScore();
    printRelationMatrix();

    let no_clusters = computeSmithSets();
    //console_log(no_clusters);

    const initData = {
        nodes: [], // The array of nodes
        edges: [], // The array of edges
        combos: [], //groups of nodes
    };

    for (j = 1; j <= no_clusters; j++) {
        var new_combo = {
            id: "Cluster" + j,
            label: "Cluster" + j,
            nodes_no: 0,
            afterUpdate: function afterUpdate(cfg, combo) {
                console_log(combo);
                const group = combo.get("group");
                // Find the circle shape in the graphics group of the Combo by name
                const circle = group.find(
                    (ele) => ele.get("name") === "combo-circle-shape"
                );
                // Update the position of the right circle
                circle.attr({
                    // cfg.style.width and cfg.style.heigth correspond to the innerWidth and innerHeight in the figure of Illustration of Built-in Rect Combo
                    x: cfg.style.width / 2 + cfg.padding[1],
                    y: (cfg.padding[2] - cfg.padding[0]) / 2,
                });
            },
        };
        initData.combos.push(new_combo);
    }

    let minETSD = nodesInfo[0].etsd;
    let maxETSD = nodesInfo[0].etsd;
    let cluster_xoffset = [...Array(no_clusters)].fill(50);
    for (j = 1; j < Candidates; j++) {
        if (nodesInfo[j].etsd < minETSD) minETSD = nodesInfo[j].etsd;
        if (nodesInfo[j].etsd > minETSD) maxETSD = nodesInfo[j].etsd;
    }
    for (i = 0; i < Candidates; i++) {
        var new_node = {
            id: nodesInfo[i].label,
            style: {
                fill: quantifiedECDMNodeColors(nodesInfo[i].etsd),
            },
            x: cluster_xoffset[nodesInfo[i].cluster - 1],
            y: 150 * nodesInfo[i].cluster,
            label: nodesInfo[i].label,
            size: quantifiedNodeDiameter(nodesInfo[i].etsd), //etsd
            class: "c3",
            info: nodesInfo[i].info,
            comboId: "Cluster" + nodesInfo[i].cluster,
        };
        cluster_xoffset[nodesInfo[i].cluster - 1] += 50;
        initData.combos.find((combo) => combo.id == new_node.comboId).nodes_no++;
        initData.nodes.push(new_node);
        for (j = 0; j < Candidates; j++)
            if (
                i != j &&
                majorityMargin[nodesInfo[i].index][nodesInfo[j].index] >= 0
            ) {
                if (nodesInfo[i].cluster == nodesInfo[j].cluster) {
                    var new_edge = {
                        source: nodesInfo[i].label, // String, required, the id of the source node
                        target: nodesInfo[j].label, // String, required, the id of the target node
                        label: "", // The label of the edge
                        type: "quadratic",
                        weight: 1,
                        style: {
                            endArrow: true,
                            startArrow: false,
                        },
                    };
                    initData.edges.push(new_edge);
                }
            }
    }
    initData.combos = initData.combos.filter((combo) => combo.nodes_no > 1); //remove combos with just one node
    //console_log(cluster_xoffset);
    //console_log(nodesInfo);
    //console_log(initData);
    etsdScore();
    ecmgGraph = createMGGraph("ecmg", 800, 600, initData);
}

function copelandScore() {
    nodesInfo.sort((a, b) => b.copeland - a.copeland); //descending sorting  according to copeland property;
    let prev = nodesInfo[0].copeland;
    let latex_str = "\\[" + nodesInfo[0].label;
    for (k = 1; k < nodesInfo.length; k++) {
        if (prev > nodesInfo[k].copeland)
            latex_str += `>^{cpld}_{AF} ${nodesInfo[k].label}`;
        else latex_str += `=^{cpld}_{AF} ${nodesInfo[k].label}`;
        prev = nodesInfo[k].copeland;
    }
    latex_str += "\\]"; //"$";
    MathCode("copeland_score", latex_str);
}

function etsdScore() {
    nodesInfo.sort((a, b) => a.etsd - b.etsd); //ascending sorting  according to etsd property;
    let prev = nodesInfo[0].etsd;
    let latex_str = "\\[" + nodesInfo[0].label;
    for (k = 1; k < nodesInfo.length; k++) {
        if (prev < nodesInfo[k].etsd)
            latex_str += `>^{etsd}_{AF} ${nodesInfo[k].label}`;
        else latex_str += `=^{etsd}_{AF} ${nodesInfo[k].label}`;
        prev = nodesInfo[k].etsd;
    }
    latex_str += "\\]";
    MathCode("etsd_score", latex_str);
}

function lex(a, b) {
    for (let i = 0; i < a.length; i++) {
        if (a[i] > b[i]) return 1;
        if (a[i] < b[i]) return -1;
    }
    return 0;
}

async function computeRankingBasedSemantics() {
    console_log("-----------Direct attackers---------------------");
    //console_log(R1Minus);
    print2D(R1Minus);
    BurdenSemantics();
    DiscussionSemantics();
    await categorizerSemantics();
}

function BurdenSemantics() {
    let candidates = candidateLabel.length;
    let char, sum, direct_attacker;
    Bur = [];
    for (k = 0; k < candidates; k++)
        Bur[candidateLabel[k]] = Array(times).fill(1);

    for (let i = 1; i < times; i++) {
        for (let k = 0; k < candidates; k++) {
            char = candidateLabel[k];
            sum = 0.0;
            for (let n = 0; n < R1Minus[char].length; n++) {
                direct_attacker = R1Minus[char][n];
                sum += 1.0 / Bur[direct_attacker][i - 1];
            }
            Bur[char][i] = 1 + truncate(sum, 2);
        }
    }
    candidateLabel.sort(function(a, b) {
        return lex(Bur[b], Bur[a]);
    });
    console_log("-----------BBs score---------------------");
    display(Bur, "Bur_a--->>:", true);
    candidateLabel.sort(function(a, b) {
        return lex(Bur[a], Bur[b]);
    });
    display(Bur, "Bur_b--->>:", true);
    MathCode("bbs_score", null, "Bbs");
    //console_log(Bur);
    print2D(Bur, true);
}

async function categorizerSemantics() {
    let candidates = candidateLabel.length;
    let char = "",
        str,
        sum,
        computed;
    let n, j;
    for (j = 0; j < candidates; j++) {
        char = candidateLabel[j];
        if (R1Minus[char].length == 0) Cat[char] = 1;
    }
    for (j = 0; j < candidates; j++) {
        char = candidateLabel[j];
        if (Cat[char] == -1) {
            sum = 0;
            computed = 0;
            for (n = 0; n < R1Minus[char].length; n++)
                if (Cat[R1Minus[char][n]] > 0) {
                    sum += Cat[R1Minus[char][n]];
                    computed++;
                }
            if (computed == R1Minus[char].length) Cat[char] = 1.0 / (1.0 + sum);
        }
    }
    let equations = "";
    for (j = 0; j < candidates; j++) {
        char = candidateLabel[j];
        //console_log("1---->" + char);
        if (Cat[char] == -1) {
            str = "";
            //console_log("2---->" + char);
            for (n = 0; n < R1Minus[char].length; n++)
                if (Cat[R1Minus[char][n]] > 0) str += "+" + Cat[R1Minus[char][n]];
                else str += "+" + R1Minus[char][n];
            str = `${char}=1/(1${str}),${char}>0`;
            equations += "," + str;
        }
    }
    equations = equations.substring(1);
    console_log("-----------Cat unresolved equations--------------------");
    console_log(equations);
    const eqns_str = equations.replaceAll("+", "%2B");
    let results = await Solve(eqns_str);
    let eq, varble, value;
    for (j = 0; j < results.length; j++) {
        eq = results[j].split("≈");
        varble = eq[0].trim();
        value = truncate(parseFloat(eq[1]), 3);
        // console_log(varble, value);
        Cat[varble] = value;
    }

    console_log("--------------Cat Score-----------------------");
    display(Cat, "Cat_a11--->>:");
    candidateLabel.sort(function(a, b) {
        return Cat[b] - Cat[a];
    });
    display(Cat, "Cat_b--->>:", true);
    MathCode("cat_score", null, "Cat");
}

function display(tab, msg, display_value = false) {
    if (!VERBOSE) return;
    for (let k = 0; k < candidateLabel.length; k++)
        console.log(
            msg +
            candidateLabel[k] +
            (display_value ? "," + tab[candidateLabel[k]] : "")
        );
}

async function Solve(equations_strings) {
    let tms = 1;
    let tm = setInterval(() => {
        document.getElementById("cat_score").innerHTML =
            "Calculating" + ".".repeat(tms++);
    }, 1000);
    let MathematicaResponse = await axiosGetMathematicaResult(equations_strings);
    clearTimeout(tm);
    //console_log(MathematicaResponse.data, MathematicaResponse.status);
    //<plaintext>A≈0.618034, B≈0.315497, C≈0.315497, D≈0.315497, E≈0.618034, F≈0.618034</plaintext>
    let regex = /<plaintext>.+<\/plaintext>/g;
    var results = MathematicaResponse.data
        .match(regex)[1]
        .replace("<plaintext>", "")
        .replace("</plaintext>", "")
        .split(",");
    return results;
}

function computeDiscusionCount(init_candidate, running_candidate, path_len) {
    if (
        (init_candidate == running_candidate && path_len != 0) ||
        path_len == maxPathLength
    )
        return;
    else {
        let pr = path_len % 2 == 1 ? -1 : 1;
        Dis[init_candidate][path_len] = pr * R1Minus[running_candidate].length;
        for (let n = 0; n < R1Minus[running_candidate].length; n++)
            computeDiscusionCount(
                init_candidate,
                R1Minus[running_candidate][n],
                path_len + 1
            );
    }
}

function DiscussionSemantics() {
    let init_path_len = 0;
    maxPathLength = candidateLabel.length;
    Dis = [];
    candidateLabel.forEach((candidate) => {
        Dis[candidate] = [...Array(maxPathLength)].fill(-maxPathLength);
        computeDiscusionCount(candidate, candidate, init_path_len);
    });
    console_log("-----------DBs score---------------------");
    display(Dis, "Dis_a--->>:");
    candidateLabel.sort(function(a, b) {
        return lex(Dis[a], Dis[b]);
    });
    display(Dis, "Dis_b--->>:", true);
    MathCode("dbs_score", null, "Dbs");
    print2D(Dis, true);
}

async function testgraph() {
    const initData = {
        // The array of nodes
        nodes: [],
        // The array of edges
        edges: [],
    };

    R1Minus = [];
    Cat = [];
    Bur = [];
    for (let i = 0; i < 5; i++) {
        R1Minus[String.fromCharCode(65 + i)] = [];
        Cat[String.fromCharCode(65 + i)] = -1;
        Bur[String.fromCharCode(65 + i)] = [];
        var new_node = {
            id: String.fromCharCode(65 + i),
            //x: x,
            //y: 200,
            label: String.fromCharCode(65 + i),
        };
        initData.nodes.push(new_node);
    }

    var new_edge = {
        source: "A",
        target: "E",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    R1Minus[new_edge.target].push(new_edge.source);
    initData.edges.push(new_edge);
    var new_edge = {
        source: "B",
        target: "C",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    R1Minus[new_edge.target].push(new_edge.source);
    initData.edges.push(new_edge);
    var new_edge = {
        source: "B",
        target: "A",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    R1Minus[new_edge.target].push(new_edge.source);
    initData.edges.push(new_edge);
    var new_edge = {
        source: "C",
        target: "E",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    initData.edges.push(new_edge);
    R1Minus[new_edge.target].push(new_edge.source);
    var new_edge = {
        source: "D",
        target: "A",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    initData.edges.push(new_edge);
    R1Minus[new_edge.target].push(new_edge.source);
    var new_edge = {
        source: "E",
        target: "D",
        label: "", // The label of the edge
        type: "quadratic",
        weight: 1,
        style: {
            endArrow: true,
            startArrow: false,
        },
    };
    initData.edges.push(new_edge);
    R1Minus[new_edge.target].push(new_edge.source);

    //createMGGraph("testmg", 800, 600, initData);
}

function print2D(table, trunc = false) {
    //console_log(table);
    let keys = Object.keys(table).sort();
    let el = 1,
        str = "";
    for (let i = 0; i < keys.length; i++) {
        str = "";
        for (let k = 0; k < table[keys[i]].length; k++) {
            el = table[keys[i]][k];
            str += "," + (trunc ? truncate(el, 2) : el);
        }
        console_log(keys[i] + ": " + str.substring(1));
    }
}