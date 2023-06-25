const EDIT_RECORD = 1;
const NEW_RERORD = 2;
const POLL = 1;
const SURVEY = 2;
const USERS = 3;

const delim = "$-%@$";

const VISITOR = 1;
const USER = 2;
const ADMIN = 3;

const MAX_ANSWERS = 9;
const MIN_ANSWERS = 1;

const RANKING = 1;
const RATING = 2;
const YES_NO = 3;
const APPROVAL = 4;

const INFO_MSG = 1;
const WARNING_MSG = 2;
const ERROR_MSG = 3;

var window_width = 800;
var window_height = 600;
var currentTab = SURVEY;

var map;

var pollId123 = 0;
var surverId = 0;

var pollParams = [];
var pollIds = [];
var pollBgColors = [
  "rgb(254, 249, 231)",
  "rgb(232, 248, 245)",
  "rgb(234, 250, 241)",
  "rgb(249, 235, 234)",
];

var infoModal;
var typeClass;
var userType = ADMIN;

var currentUserId;

var SelectedLanguage = "En";

var genders = ["I prefer not to say", "Female", "Male", "Other"];
var prefLanguages = []; // = ['en', 'gr'];
var translateHashTable = [];

var user_loggedin;
var closed_for_votes;

var rowid_to_delete;
var id_to_delete;

var Ids = 0;
var path_to_root = "http://localhost/cultour";
//var path_to_root = "https://cultour.scienceontheweb.net/";

function getUrlParams() {
  var id = -1;
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  if (urlParams.has("id")) {
    return urlParams.get("id");
  }
  return id;
}

function setClassVisibility(classn, vis) {
  $("." + classn).each(function () {
    if (vis) $(this).show();
    else $(this).hide();
  });
}

function setUILanguage(lang, init = false) {
  let url = window.location.pathname;
  var fname = url.substring(url.lastIndexOf("/") + 1);
  if (fname.toLowerCase().includes("edit")) {
    document.getElementById("lang_btn").innerText = lang;
    return;
  }
  if (init) {
    let flags_url = translateHashTable["langs_url"];
    let ui_langs = translateHashTable["ui_langs"];
    //console.log(flags_url, ui_langs);
    let x = ui_langs.findIndex((l) => {
      return l == lang;
    });
    //console.log(flags_url[x]);
    //aaaaaaa
    if (x >= 0) {
      $("#selectLang").attr("src", flags_url[x]);
    }

    if (translateHashTable["ui_langs"].length > 0) {
      fillSelectFromArray("lang_select", translateHashTable["ui_langs"]);
      fillSelectFromArray("lang_select_ui", translateHashTable["ui_langs"]);
    }
    if (translateHashTable["ui_genders_" + lang].length > 0)
      fillSelectFromArray(
        "gender_select",
        translateHashTable["ui_genders_" + lang]
      );
  }
  $("[data-mltlng]").each((index, elm) => {
    try {
      let txt = translateHashTable[elm.dataset.mltlng][lang];
      $(elm).text(txt);
    } catch (e) {
      console.log(elm);
    }
  });
}

async function initLangUi(json_fname) {
  translateHashTable = [];
  await $.getJSON(json_fname)
    .done((multilingual_data) => {
      let langs = JSON.stringify(multilingual_data[0].langs).replace(
        /\"|\[|\]/g,
        ""
      );
      translateHashTable["ui_langs"] = langs.split(",");
      let langs_descr = JSON.stringify(multilingual_data[0].langsDescr)
        .replace(/\"|\[|\]/g, "")
        .split(",");
      let langs_url = JSON.stringify(multilingual_data[0].langsURL)
        .replace(/\"|\[|\]/g, "")
        .split(",");
      translateHashTable["langs_url"] = langs_url;
      language_select_html = "";
      j = 0;
      translateHashTable["ui_langs"].forEach((lang) => {
        let gendrs = JSON.stringify(
          multilingual_data[0]["genders" + lang]
        ).replace(/\"|\[|\]/g, "");
        translateHashTable["ui_genders_" + lang] = gendrs.split(",");
        /*language_select_html += `<li class="dropdown-item" 
                        onclick="setUILanguage(SelectedLanguage = '${lang}');">
                        <img class="langicon" style="cursor:pointer;" 
                        src="${langs_url[j]}" /> 
                        ${langs_descr[j++]}
                        </li>`;*/
        language_select_html += `<li class="dropdown-item" 
                        onclick="setUILanguage(SelectedLanguage = '${lang}');">
                        ${langs_descr[j++]}
                        </li>`;
      });
      let translate_table = multilingual_data[1];
      translate_table.forEach((translation_entry) => {
        let term_translate = new Array();
        Object.keys(translation_entry).forEach(function (key, index) {
          if (key != "id") {
            term_translate[key] = translation_entry[key];
          }
        });
        translateHashTable[translation_entry.id] = term_translate;
      });
      //document.getElementById('select_lng').innerHTML = language_select_html;
      window.localStorage.setItem(
        "LangComboHtml",
        JSON.stringify(language_select_html)
      );
    })
    .fail(function (jqxhr, textStatus, error) {
      var err = textStatus + ", " + error;
      console.log("Request Failed: " + err);
    });
}

function getTimeStamp(dte) {
  let datetime =
    dte.getDate() +
    "/" +
    String(dte.getMonth() + 1).padStart(2, "0") +
    "/" +
    dte.getFullYear() +
    "," +
    String(dte.getHours()).padStart(2, "0") +
    ":" +
    String(dte.getMinutes()).padStart(2, "0");
  return datetime;
}

function getCurrentTimeStamp() {
  return getTimeStamp(new Date());
}

function vote(vid, mode, kind) {
  //if (currentUserId === undefined) {
  //    displayMsgJQ("dialog", "Voting error", "You have to login first for voting", WARNING_MSG);
  //    return;
  //}
  ref_id = parseInt(vid.split("_")[1]);
  if (vid.startsWith("-")) ref_id = -ref_id;
  if (mode == POLL) {
    fname = kind.charAt(0).toUpperCase() + kind.slice(1); //capitalize 1st letter TO SELECT the appropriate html file to call
    if (fname.startsWith("Yes")) fname = "YesNo";
    window_width = 800;
    window_height = 600;
    [window_left, window_top] = calculateNewWindowPosition(
      window_width,
      window_height
    );
    var href = window.location.href;
    newWindowUrl =
      href.substring(0, href.lastIndexOf("/")) +
      "/php/pages/pollVoting" +
      fname +
      ".php?id=" +
      ref_id;
    var myWindow = window.open(
      newWindowUrl,
      "Poll Voting",
      `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`
    );
  } else {
    window_width = 800;
    window_height = 600;
    [window_left, window_top] = calculateNewWindowPosition(
      window_width,
      window_height
    );
    var href = window.location.href;
    newWindowUrl =
      href.substring(0, href.lastIndexOf("/")) +
      "/php/pages/surveySubmission.php?id=" +
      ref_id;
    var myWindow = window.open(
      newWindowUrl,
      "Survey Voting",
      `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`
    );
  }
}

function pollVotes(votes) {
  if (votes >= 0)
    return `<br><span class="votingCount position-relative" data-bs-toggle="tooltip" data-bs-placement="top">               
                <i class="fa-regular fa-clipboard fa-3x"></i>
                <span class="position-absolute top-50 start-50 translate-middle badge rounded-pill bg-danger">
                    ${votes}
                    <span class="visually-hidden">unread messages</span>
                </span>
            </span>`;
  else
    return `<br><span class="votingCount position-relative" data-bs-toggle="tooltip" data-bs-placement="top">               
                <i class="fa-solid fa-sack-xmark fa-2x"></i>
            </span>`;
}

function userCredits(credits) {
  if (credits > 0)
    return `<span class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top">               
                <i class="fa-solid fa-sack-dollar fa-2x"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    ${credits}
                    <span class="visually-hidden">unread messages</span>
                </span>
            </span>`;
  else
    return `<span class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top">               
                <i class="fa-solid fa-sack-xmark fa-2x"></i>
            </span>`;
}

function displayImage(obj) {
  $("#img_modal").attr("src", $(obj).attr("src"));
  $("#img_caption").html($(obj).attr("alt"));
}

function addRow(
  addEditBtn,
  addDelBtn,
  addCsvBtn,
  tableId,
  rowid,
  data_obj,
  mode,
  userType
) {
  // Get a reference to the table's tbody
  let table = document.getElementById(tableId);
  let tbodyRef = table.getElementsByTagName("tbody")[0];
  let headerColumns = table.rows[0].cells;
  let newRow = tbodyRef.insertRow(-1);
  let min_table_body = null;
  let ref_id_type_votes;

  newRow.id = rowid;
  Ids = 0;
  let poll_type = "";
  current_id = rowid;
  let vote_count = 0;
  let open_vote = true;
  let url = null;

  Object.keys(data_obj).forEach(async function (key, index) {
    //key, index, data_obj[key];
    if (key == "url") {
      url = data_obj[key];
    } else if (key.toLowerCase() != "id") {
      let newCell = newRow.insertCell();
      if (key.startsWith("descr")) newCell.style = "vertical-align: middle;";
      else newCell.style = "text-align: center; vertical-align: middle;";
      if (key.toLowerCase() == "kind") {
        poll_type = data_obj[key];
        if (poll_type == "yes_no") poll_type = "Yes/No";
        else poll_type = poll_type.charAt(0).toUpperCase() + poll_type.slice(1);
      }
      let tpe = headerColumns[Ids++].getAttribute("data-celltype");
      if (tpe == "txt") {
        if (key != "kind") newCell.innerHTML = data_obj[key];
        else if (key == "kind" && tableId == "polls") {
          if (userType == ADMIN) {
            newCell.style.textAlign = "center";
            if (newCell.innerHTML == "yes_no") newCell.innerHTML = "Yes/No";
            else {
              let word = newCell.innerHTML;
              newCell.innerHTML = word.charAt(0).toUpperCase() + word.slice(1);
            }

            newCell.innerHTML = "<i>type: </i><b>" + data_obj[key] + "</b>";
          } else {
            if (data_obj["url"])
              newCell.innerHTML = `<img class="featured_img img-fluid"
                                src="${data_obj["url"]}"
                                alt="Poll: ${newRow.cells[0].innerHTML}" 
                                data-bs-toggle="modal" data-bs-target="#imgModal"
                                onclick="displayImage(this)">`;
          }
          votesSql =
            "SELECT count(*) as cnt FROM usercompletedpolls WHERE pollId=" +
            current_id;
          getDbRecord = await axiosGetQueryResultsPromise(votesSql);
          if (getDbRecord.data.statusCode == 200) {
            let cnt = getDbRecord.data.records[0].cnt;
            if (userType == ADMIN) {
              newCell.innerHTML += pollVotes(cnt);
              if (userType == ADMIN)
                setElementTooltip(newCell, "Number of votes");
            } else {
              if (cnt && (el = document.getElementById(`vcnt-${newRow.id}`)))
                el.innerHTML = cnt;
              if (min_table_body)
                document.getElementById(
                  `count_${ref_id_type_votes}`
                ).innerHTML = cnt;
            }
          }
        }
      } else if (tpe == "txt_dbinfo") {
        newCell.innerHTML = data_obj[key];
        getCreditsSql =
          "SELECT sum(p.credits) as userCredits FROM usercompletedpolls as ucp INNER JOIN polls as p on ucp.pollId = p.id WHERE ucp.userId = " +
          current_id;
        getDbRecord = await axiosGetQueryResultsPromise(getCreditsSql);
        if (getDbRecord.data.statusCode == 200) {
          let uc = getDbRecord.data.records[0].userCredits;
          if (!uc) credits = 0;
          else credits = parseInt(uc);
          newCell.innerHTML = credits; //userCredits(uc);
          //setElementTooltip(newCell, "User credits");
        }
      } else if (tpe == "gnd")
        newCell.innerHTML = genders[parseInt(data_obj[key]) - 1];
      else if (tpe == "pref_lang")
        newCell.innerHTML = data_obj[key].toUpperCase();
      //prefLanguages[parseInt(data_obj[key]) - 1];
      else if (tpe == "url") {
        //newCell.style = "text-align: center; vertical-align: middle;"
        //if (data_obj[key]) newCell.innerHTML = `<a href="${data_obj[key]}">link</a>`;
      } else {
        //bool
        open_vote = data_obj[key] == "1";
        if (open_vote) newCell.innerHTML = "Open";
        else {
          newCell.innerHTML = "Closed";
          newRow.classList.add("closed_poll");
        }
      }
    } else {
      current_id = data_obj[key];
      newRow.id = mode + "_" + data_obj[key];
    }
  });

  let newCell = newRow.insertCell();
  newCell.innerHTML = "<center>";
  if (userType == ADMIN) {
    newCell.style.width = "5%";
    if (addCsvBtn && mode == POLL) {
      newCell.innerHTML += `<img class="savecsvbtn img-fluid" onclick="SaveRecordToCsv(${mode}, ${EDIT_RECORD}, '${newRow.id}')">`;
      newCell.innerHTML += `<img class="plotpollbtn img-fluid" onclick="plotPoll(${mode}, ${EDIT_RECORD}, '${newRow.id}')">`;
    }
    if (addEditBtn && mode != USERS) {
      newCell.innerHTML += `<img class="editbtn img-fluid" onclick="EditRecord(${mode}, ${EDIT_RECORD}, '${newRow.id}')">`;
    }
    if (addDelBtn)
      newCell.innerHTML += `<img class="deletebtn img-fluid" onclick="deleteRow(${mode}, ${rowid}, '${newRow.id}')">`;
  } else {
    let btn_class = "";
    newCell.style.width = "5%";
    if (mode == POLL) {
      btn_class = "votepollbtn";
      txt_btn = "Vote";
    } else {
      btn_class = "votesurveybtn";
      txt_btn = "Take the survey";
    }
    newCell.style.textAlign = "center";
    if (open_vote) {
      //aaaaa newCell.innerHTML = `<img disabled class="${btn_class} img-fluid" onclick="vote('${newRow.id}', ${mode}, '${poll_type}');">`;
      newCell.innerHTML = `<button type="button" class="btn bg-dark text-white"  
                    onclick="vote('${newRow.id}', ${mode}, '${poll_type}');">
                    ${txt_btn}
                </button>`;
      newRow.cells[0].innerHTML = `<a href="#" class="descr_link" onclick="vote('${newRow.id}', ${mode}, '${poll_type}');">${newRow.cells[0].innerHTML}</a>`;
      if (mode == POLL)
        newRow.cells[0].innerHTML += `<br>${poll_type} (<span id="vcnt-${newRow.id}"></span> Votes)`;
    } else {
      newCell.innerHTML = "";
      newRow.cells[0].innerHTML = `<a href="#"  class="descr_link" onclick="vote('-${newRow.id}', ${mode}, '${poll_type}');">${newRow.cells[0].innerHTML}</a>`;
      if (mode == POLL)
        newRow.cells[0].innerHTML += `<br>${poll_type} (<span id="vcnt-${newRow.id}"></span> Votes)`;
    }

    if (tableId == "polls")
      //newCell.innerHTML += `<img class="plotpollbtn img-fluid plotuser" style="display:none" onclick="plotPollUser(${mode}, ${EDIT_RECORD}, '${newRow.id}')">`;
      newCell.innerHTML += `<button type="button" class="btn btn-danger ms-2 plotuser"  
                onclick="plotPollUser(${mode}, ${EDIT_RECORD}, '${newRow.id}')">
                Results
                </button>`;
  }
  newCell.innerHTML += "</center>";
  if (userType != ADMIN && (tableId == "polls" || tableId == "surveys")) {
    let min_str = "";
    min_table_body = document
      .getElementById("min" + tableId)
      .getElementsByTagName("tbody")[0];
    //console.log(min_table_body.innerHTML);
    //console.log(data_obj);
    if (tableId == "polls") {
      let dis_vot = "";
      if (!(startdate = data_obj["start_date"])) startdate = "";
      if (!(enddate = data_obj["end_date"])) enddate = "";
      if (data_obj["open"] == "0") {
        pollstatus = "Closed";
        dis_vot = "disabled";
      } else pollstatus = "Open";
      ref_id_type_votes = "min" + tableId + rowid;
      if (data_obj["url"])
        img_str = `<img class="featured_img img-fluid"
                                        src="${data_obj["url"]}"
                                        alt="Poll: ${data_obj["descr"]}" 
                                        data-bs-toggle="modal" data-bs-target="#imgModal"
                                        onclick="displayImage(this)"`;
      else img_str = "";
      min_str += `<tr>
                    <td id="dddd"  class="bg-dark text-white" style='border-bottom:none' name="dddname" colspan="2">
                        <h2 class="p-2">${data_obj["descr"]}</h2>
                    </td>
                </tr>
                <tr><td style='border-top:none;border-right:none; vertical-align:middle;text-align:center;'><h6><span id="type_${ref_id_type_votes}"></span> (<span id="count_${ref_id_type_votes}"></span> votes)</h6></td>
                    <td style='border-left:none;border-top:none; vertical-align:middle;text-align:center;'>${img_str}</td></tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Start date</td>
                    <td width="85%">${startdate}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">End date</td>
                    <td width="85%">${enddate}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Status</td>
                    <td width="85%">${pollstatus}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Language</td>
                    <td width="85%"><span class="text-uppercase">${data_obj["lang"]}</span></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button ${dis_vot} type="button" class="btn bg-dark text-white"  
                            onclick="vote('${newRow.id}', ${mode}, '${poll_type}');">
                            Vote
                        </button>
                        <button type="button" class="btn btn-danger text-white plotuser" 
                            onclick="plotPollUser(${mode}, ${EDIT_RECORD}, '${newRow.id}');">
                            Results
                    </button>
                    </td>
                </tr>`;
    } else {
      let dis_vot = "";
      if (!(startdate = data_obj["end_date"])) startdate = "";
      if (!(enddate = data_obj["end_date"])) enddate = "";
      if (data_obj["open"] == "0") {
        pollstatus = "Closed";
        dis_vot = "disabled";
      } else pollstatus = "Open";
      ref_id_type_votes = "min" + tableId + rowid;
      min_str += `<tr>
                    <td id="dddd" name="dddname" colspan="2">
                        <h2 class="p-2 bg-dark text-white" style="background-color:rgb(106, 106, 247);">${data_obj["description"]}</h2>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Start date</td>
                    <td width="85%">${startdate}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">End date</td>
                    <td width="85%">${enddate}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Status</td>
                    <td width="85%">${pollstatus}</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap;width:1%">Language</td>
                    <td width="85%"><span class="text-uppercase">${data_obj["lang"]}</span></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button ${dis_vot} type="button" class="btn bg-dark text-white" 
                        onclick="vote('${newRow.id}', ${mode}, '${poll_type}');">
                        Take the survey
                    </button>
                    </td>
                </tr>`;
    }
    min_table_body.innerHTML += min_str;

    if (min_table_body && tableId == "polls") {
      document.getElementById(`type_${ref_id_type_votes}`).innerHTML =
        poll_type;
    }
  }
}

function deleteRow(mode, rowId, deleteId) {
  rowid_to_delete = rowId;
  if (mode == POLL)
    content =
      "Are you sure? This will also delete possible users' answers to this poll..";
  else if (mode == SURVEY)
    content =
      "Are you sure? This will also delete possible users' answers to this survey..";
  else content = "Are you sure? This will also delete possible users' answers";
  displayMsgJQ(
    "dialog",
    "Confirm deletion",
    content,
    WARNING_MSG,
    true,
    "OK",
    confirmedDelete,
    deleteId
  );
}

async function confirmedDelete(FormDelId) {
  let el = document.getElementById(`${FormDelId}`);
  let table = el.parentNode.parentNode;
  let dbDelId = parseInt(FormDelId.split("_")[1]);

  if (table.id == "surveys") {
    delSurveyResult = await axiosDeleteSurvey(dbDelId);
    if (delSurveyResult.data.statusCode == 200) {
      table.deleteRow(rowid_to_delete + 1); //row[0]:table header
      displayMsgJQ(
        "dialog",
        "Delete Survey",
        "Survey deleted successfully",
        INFO_MSG,
        false,
        "OK"
      );
    } else {
      console.log(delSurveyResult.data);
      displayMsgJQ(
        "dialog",
        "Delete Survey",
        "Internal Error! Try again later",
        ERROR_MSG,
        false,
        "OK"
      );
    }
  } else if (table.id == "polls") {
    delPollResult = await axiosDeletePoll(dbDelId);
    if (delPollResult.data.statusCode == 200) {
      table.deleteRow(rowid_to_delete + 1); //row[0]:table header
      displayMsgJQ(
        "dialog",
        "Delete poll",
        "poll deleted successfully",
        INFO_MSG,
        false,
        "OK"
      );
    } else {
      console.log(delPollResult.data);
      displayMsgJQ(
        "dialog",
        "Delete Poll",
        "Internal Error! Try again later",
        ERROR_MSG,
        false,
        "OK"
      );
    }
  } else {
    delUserResult = await axiosDeleteUser(dbDelId);
    if (delUserResult.data.statusCode == 200) {
      table.deleteRow(rowid_to_delete + 1); //row[0]:table header
      displayMsgJQ(
        "dialog",
        "Delete user",
        "User deleted successfully",
        INFO_MSG,
        false,
        "OK"
      );
    } else {
      console.log(delUserResult.data);
      displayMsgJQ(
        "dialog",
        "Delete User",
        "Internal Error! Try again later",
        ERROR_MSG,
        false,
        "OK"
      );
    }
  }
}

function fillSelectFromArray(select_id, array) {
  $(`#${select_id}`).empty();
  let sel = document.getElementById(select_id);
  for (j1 = 0; j1 < array.length; j1++) {
    var option = document.createElement("option");
    option.text = array[j1];
    option.value = j1 + 1;
    sel.add(option);
  }
}

function moveHeadersRight(headerRow, shift) {
  for (j = headerRow.length - 1; j >= shift; j--)
    headerRow[j].innerHTML = headerRow[j - shift].innerHTML;
  for (j = 0; j < shift; j++) headerRow[j].innerHTML = "";
}

async function populateTable(data, targetTableId, mode, userType) {
  let addEditBtn = true;
  let addDelBtn = true;
  let addCsvBtn = true;

  let table = document.getElementById(targetTableId);
  tBody = table.getElementsByTagName("tbody")[0];
  tBody.innerHTML = "";
  var tr = table.tHead.children[0],
    th = document.createElement("th");
  if (userType == VISITOR)
    if (mode == POLL) th.innerHTML = "Vote";
    else th.innerHTML = "Take the survey";
  th.classList.add("col-sm-3");
  tr.appendChild(th);
  let records = data.records;
  for (k = 0; k < records.length; k++)
    addRow(
      addEditBtn,
      addDelBtn,
      addCsvBtn,
      targetTableId,
      k,
      records[k],
      mode,
      userType
    );
}

function convertArrayToDelimitedString(arr) {
  if (arr.length == 0) return "";
  let str = arr[0];
  for (j = 1; j < arr.length; j++) str += delim + arr[j];
  return str;
}

function convertDelimitedStringToArray(delim_str) {
  return delim_str.split(delim_str);
}

function calculateNewWindowPosition(window_width, window_height) {
  let window_left = screen.width / 2 - window_width / 2;
  let window_right = screen.height / 2 - window_height / 2;
  return [window_left, window_right];
}

function populateComboBox(data, comboBoxId) {
  let cbox = document.getElementById(comboBoxId);
  for (k = 0; k < data.length; k++)
    cbox.add(new Option(data[k].description, data[k].id));
}

function handleHttpRequestErrors(result) {
  console.log(result.data);
  switch (result.data.statusCode) {
    case 201:
      displayMsgJQ(
        "dialog",
        "DB Error",
        "Error connecting to a valid db.Try again later!",
        ERROR_MSG
      );
      break;
    case 202:
      displayMsgJQ(
        "dialog",
        "Error",
        "Please fill all the asked details",
        ERROR_MSG
      );
      break;
    case 203:
      displayMsgJQ(
        "dialog",
        "Login error",
        "Incorrect username and/or password.Try again!",
        ERROR_MSG
      );
      break;
    case 204:
      displayMsgJQ(
        "dialog",
        "Login error",
        "Incorrect username and/or password.Try again!",
        ERROR_MSG
      );
      break;
    default:
      if (result.data.includes("Duplicate entry"))
        displayMsgJQ(
          "dialog",
          "App Notification",
          "Your vote is already submitted for this poll!",
          WARNING_MSG
        );
      else {
        displayMsgJQ(
          "dialog",
          "App Error",
          "Unknown error. Try later!",
          ERROR_MSG
        );
        console.log(result);
      }
      break;
  }
}

function increment(ctrl) {
  let cur_val = parseInt(document.getElementById(ctrl).value);
  if (cur_val == MAX_ANSWERS) return;
  document.getElementById(ctrl).value = cur_val + 1;
  if (ctrl == "answers_no") document.getElementById(ctrl).onchange();
}

function decrement(ctrl) {
  let cur_val = parseInt(document.getElementById(ctrl).value);
  if (cur_val == MIN_ANSWERS) document.getElementById(ctrl).value = cur_val - 1;
  if (ctrl == "answers_no") document.getElementById(ctrl).onchange();
}

async function fillTable(tableId, sqlQuery, mode, userType) {
  const result = await axiosGetQueryResultsPromise(sqlQuery);
  if (result.data.statusCode == 200) {
    populateTable(result.data, tableId, mode, userType);
  } else {
    handleHttpRequestErrors(result);
  }
}

function uiPollcontents(pollkind, questions, option, cnt) {
  let ui_contents = "";
  switch (pollkind) {
    case RATING:
      for (j = 0; j < questions.length; j++) {
        ui_contents += `<div class='d-flex justify-content-between align-items-center'>
                                <h5 class='review-stat'>${questions[j]}</h5><div class='small-ratings p-3'>`;
        for (m = 0; m < option; m++)
          ui_contents += `<i id=${cnt},${j},${m} class='fa fa-star' aria-hidden="true" onclick='userChoice(${cnt}, this.id);'></i> `;
        ui_contents += " </div></div>";
      }
      break;
    case RANKING:
      for (j = 1; j <= questions.length; j++)
        ui_contents += `<button id="btn${cnt}_${j}" type="button" class="btn btn-light m-2 btn-outline-dark draggable" style="width:40px;" draggable="true">${j}</button>`;
      document.getElementById(`rating_numbers${cnt}`).innerHTML = ui_contents;
      //define questions/answers section
      ui_contents = "";
      for (j = 0; j < questions.length; j++)
        ui_contents += `<tr><td class="dropzone" style="width:50px" id="dz${cnt}_${
          j + 1
        }"></td><td>${questions[j]}</td></tr>`;
      break;
    case APPROVAL:
      for (j = 0; j < questions.length; j++)
        ui_contents += `<div class="form-check">
                        <label class="form-check-label" for="chk${cnt}_${j}">${questions[j]}</label>
                        <input id="chk${cnt}_${j}" class="form-check-input" type="checkbox" value="">
                    </div>`;
      break;
    case YES_NO:
      //define questions/answers section
      ui_contents = `<div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="inlineRadioOptions${cnt}" id="Yes${cnt}" value="1" checked>
                              <label class="form-check-label" for="Yes${cnt}">Yes</label>
                      </div>
                      <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="inlineRadioOptions${cnt}" id="No${cnt}" value="0">
                          <label class="form-check-label" for="No${cnt}">No</label>
                      </div>`;
      break;
  }
  return ui_contents;
}

function uiPoll(pollkind, targetDivId, questions, option, cnt) {
  let ui = "";
  switch (pollkind) {
    case RATING:
      ui = `<div class="row"><div class="d-flex p-3 m-2" style="background-color:${
        pollBgColors[pollkind - 1]
      }"><div id="questions${cnt}"></div></div></div>`;
      break;
    case RANKING:
      ui = `<div class='row m-2'>
                    <div id="rating_numbers${cnt}" class="dropzone" style="min-height:40px;"></div>
                    <div class="row justify-content-center">
                        <table class="table table-responsive table-striped p-2 w-auto justify-content-center stables">
                            <tbody id="questions${cnt}"></tbody>
                        </table>
                    </div>
                </div>`;
      break;
    case APPROVAL:
      ui = `<div id="questions${cnt}" class="row p-2 m-2"></div>`;
      break;
    case YES_NO:
      ui = `<div id="questions${cnt}" class="row p-2 m-2"></div>`;
      break;
  }
  document.getElementById(targetDivId).innerHTML = ui;
  document.getElementById(`questions${cnt}`).innerHTML = uiPollcontents(
    pollkind,
    questions,
    option,
    cnt
  );
  if (pollkind == RANKING) enableDragging();
}

function closeFrm() {
  close();
}

function ui(pollkind, polltitle, cnt, btns = true) {
  if (pollkind != YES_NO)
    reset_btn_code = `<button type="button" class="btn btn-success" onclick="resetPoll(${cnt});">Reset</button>`;
  else reset_btn_code = "";
  if (btns == false) {
    reset_btn_code = "";
    submit_btn_code = "";
    close_btn_code = "";
  } else {
    if (!closed_for_votes) {
      reset_btn_code = `<button type="button" class="btn btn-success" onclick="resetPoll(${cnt});">Reset</button>`;
      submit_btn_code = `<button type="button" class="m-4 btn btn-primary" onclick="Exit(true, ${pollkind}, ${cnt});">Vote</button>`;
    } else {
      reset_btn_code = "";
      submit_btn_code = "";
      document.title += "-closed ";
    }
    close_btn_code = `<button type="button" class="btn btn-secondary" onclick="Exit(false);">Close</button>`;
  }
  var str = `
        <div style="background-color:${
          pollBgColors[pollkind - 1]
        }" id="poll${cnt}">
            <h2 id="description${cnt}" class="bg-primary p-2 text-white"></h2>
            <h6 id="poll__${cnt}" class="p-2">(${polltitle})</h6>
            <form>
                <div id="rec${cnt}" class="form-row m-2">
                    <div id="url_row${cnt}" class="row">
                        <div class="form-group mb-3 m-2">
                            <img id="im${cnt}" class="img-fluid">
                        </div>
                    </div>
                    <div id="presentPoll${cnt}" class="container justify-content-center align-items-center mb-5" style="background-color:${
    pollBgColors[pollkind - 1]
  }"></div>
                </div>
                <div>
                    ${submit_btn_code}
                    ${reset_btn_code}
                    ${close_btn_code}
                </div>
            </form>
            <a id="gotopoll__${cnt}" href="#poll__${cnt}" ></a>
        </div>`;
  return str;
}

async function FillForm(pid, pollkind, cnt) {
  const result = await axiosGetQueryResultsPromise(
    "SELECT description, url, questions, defVariable, open FROM polls where id=" +
      pid
  );
  if (result.data.statusCode == 200) {
    const rec_data = result.data.records[0];
    /*
            defVariable: "5"
            description: "Rate the following:"
            open: "1"
            questions: "Option 1$-%@$Option2$-%@$Option3$-%@$Option4$-%@$Option5"
            url: null
        */
    if (pollkind == APPROVAL)
      document.getElementById(`description${cnt}`).innerHTML =
        rec_data["description"] +
        (rec_data["defVariable"] == "0"
          ? ""
          : `  (Select ${rec_data["defVariable"]} from the following)`);
    else
      document.getElementById(`description${cnt}`).innerHTML =
        rec_data["description"];

    document.getElementById(`description${cnt}`).name = pid;
    if (rec_data["questions"]) questions = rec_data["questions"].split(delim);
    else questions = null;
    if (rec_data["url"]) {
      //document.getElementById(`url${cnt}`).value = rec_data['url'];
      //if (rec_data['url'].startsWith("/"))
      //    $(`#im${cnt}`).attr("src", path_to_root + rec_data['url']);
      //else $(`#im${cnt}`).attr("src", rec_data['url']);
    } else {
      document.getElementById(`im${cnt}`).style.display = "none";
    }
    document.getElementById(`url_row${cnt}`).name = rec_data["defVariable"];
    uiPoll(
      pollkind,
      `presentPoll${cnt}`,
      questions,
      rec_data["defVariable"],
      cnt
    );
  }
}

function allowDrop(ev) {
  if (fnBrowserDetect() == "firefox") targetElId = ev.target.id;
  else targetElId = ev.toElement.id;
  document.getElementById(targetElId).style.border = "1px solid black";
  ev.preventDefault();
}

function drag(ev) {
  if (fnBrowserDetect() == "firefox")
    ev.originalEvent.dataTransfer.setData("text", "anything");
  else ev.dataTransfer.setData("text", ev.target.id);
}

function drop(ev, pollCounter) {
  ev.preventDefault();
  var data = ev.dataTransfer.getData("text");
  if (fnBrowserDetect() == "firefox") targetElId = ev.target.id;
  else targetElId = ev.toElement.id;
  let targerEl = document.getElementById(targetElId);
  targerEl.classList.remove("selectedtd");
  let list = "";
  if (targetElId.startsWith("btn")) list = targerEl.parentNode;
  else list = targerEl;
  if (list.hasChildNodes()) {
    list.removeChild((old = list.children[0]));
    document.getElementById("rating_numbers" + pollCounter).appendChild(old);
  }
  list.appendChild(document.getElementById(data));
}

function dragenter(ev) {
  if (fnBrowserDetect() == "firefox") targetElId = ev.target.id;
  else targetElId = ev.toElement.id;
  targerEl = document.getElementById(targetElId);
  if (targetElId.startsWith("btn")) highlight = targerEl.parentNode;
  else highlight = targerEl;
  highlight.style.border = "1px dotted #0000FF";
  //ev.preventDefault();
}

function dragleave(ev) {
  if (fnBrowserDetect() == "firefox") targetElId = ev.target.id;
  else targetElId = ev.toElement.id;
  document.getElementById(targetElId).style.border = "1px solid black";
}

function validatePoll(pollkind, cnt) {
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
      else
        displayMsgJQ(
          "dialog",
          "Survey answers' submission",
          "You have to rate all",
          ERROR_MSG,
          closeFrm
        );
      break;
    case RANKING:
      if (!document.getElementById("rating_numbers" + cnt).hasChildNodes()) {
        answer = "";
        let q = document.getElementById("questions" + cnt).children;
        for (j = 0; j < q.length; j++) {
          btn_name = q[j].children[0].children[0].id;
          answer += "," + btn_name.substring(btn_name.lastIndexOf("_") + 1);
        }
        answer = answer.substring(1);
        sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
      } else {
        displayMsgJQ(
          "dialog",
          "Error saving survey",
          "You have to use all available ranking numbers",
          ERROR_MSG
        );
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
      if (needed_answers == 0 || needed_answers == given_answers)
        sql_query = `(${currentUserId}, ${pid}, '${answer}', DEFAULT)`;
      else if (needed_answers > given_answers)
        displayMsgJQ(
          "dialog",
          "Error saving survey",
          `You have to fill ${needed_answers - given_answers} more options`,
          ERROR_MSG
        );
      else
        displayMsgJQ(
          "dialog",
          "Error saving survey",
          `You have to fill ${needed_answers} at most (${cnt})`,
          ERROR_MSG
        );
      break;
  }
  return sql_query;
}

async function Exit(saveOnExit, pollkind, cnt) {
  if (!user_loggedin && saveOnExit) {
    displayMsgJQ(
      "dialog",
      "Error",
      `Voting is allowed only for registered users.`,
      ERROR_MSG
    );
    return;
  }
  if (!saveOnExit) close();
  sql_query = validatePoll(pollkind, cnt);
  if (sql_query) {
    sql_str = `INSERT INTO usercompletedpolls VALUES${sql_query}`;
    const result = await axiosExecQueryPromise(sql_str);
    if (result.data.statusCode == 200) {
      displayMsgJQ(
        "dialog",
        "Answer submitting",
        "Thank you for your valuable contribution!",
        INFO_MSG,
        false,
        "OK",
        closeFrm
      );
    } else handleHttpRequestErrors(result);
  }
}

function initForm(targetel, pollkind, title, cnt) {
  pid = getUrlParams();
  if (pid.startsWith("-")) {
    closed_for_votes = true;
    pid = pid.substring(1);
  } else closed_for_votes = false;
  pollIds[`${cnt}`] = pid;
  currentUserId = window.opener.currentUserId;
  initPoll(targetel, pid, pollkind, title, cnt);
}

function initPoll(targetel, pid, pollkind, title, cnt, btns = true) {
  pollParams[`${cnt}`] = { targetel, pollkind, title, pid };
  targetel.innerHTML = ui(pollkind, title, cnt, btns);
  targetel.style.background = pollBgColors[pollkind - 1];
  if (pid > -1) {
    FillForm(pid, pollkind, cnt);
  }
}

function call_parent_test() {
  window.opener.test(123);
  close();
}

function userChoice(pollCounter, id) {
  //turn on/off ratings star in the same row
  let arr = id.split(",");
  row = parseInt(arr[1]);
  col = parseInt(arr[2]);
  first_star = document.getElementById(pollCounter + "," + row + "," + 0);
  max_stars = first_star.parentNode.children.length;
  for (m = 0; m <= col; m++)
    document
      .getElementById(pollCounter + "," + row + "," + m)
      .classList.add("rating-color");
  for (m = col + 1; m < max_stars; m++) {
    document
      .getElementById(pollCounter + "," + row + "," + m)
      .classList.remove("rating-color");
  }
}

function resetPoll(cnt) {
  let call_obj = pollParams[`${cnt}`];
  if (call_obj.pollkind != YES_NO)
    initPoll(
      call_obj.targetel,
      call_obj.pid,
      call_obj.pollkind,
      call_obj.title,
      cnt
    );
}

function isEmpty(el) {
  if (el == null) return true;
  try {
    if (el.value.length == 0) return true;
  } catch (e) {
    console.log(e);
  }
  return false;
}

function sql(str) {
  if (str == null) return "NULL";
  else return `"${str}"`;
}

function displayMsgJQ(
  dlg_id,
  title,
  msg,
  type,
  cancelbtn = false,
  yesbtntxt = null,
  yesbtncallback = null,
  arg = null
) {
  let dlg = $(`#${dlg_id}`);
  dlg.dialog("option", "title", title);
  dlg.html(msg);

  if (yesbtntxt) {
    let btns = [];
    btns.push({
      id: "btn1",
      text: yesbtntxt,
      click: function () {
        if (yesbtncallback)
          if (arg) yesbtncallback(arg);
          else yesbtncallback();
        $(this).dialog("close");
      },
    });
    if (cancelbtn)
      btns.push({
        id: "Cancel",
        text: "Cancel",
        click: function () {
          $(this).dialog("close");
        },
      });

    dlg.dialog("option", "buttons", btns);
    switch (type) {
      case INFO_MSG:
        $(".ui-dialog:first")
          .find(".ui-widget-header")
          .css("background", "green");
        break;
      case WARNING_MSG:
        $(".ui-dialog:first")
          .find(".ui-widget-header")
          .css("background", "orange");
        break;
      case ERROR_MSG:
        $(".ui-dialog:first")
          .find(".ui-widget-header")
          .css("background", "red");
        break;
    }
  }
  dlg.css("zIndex", 9999);
  dlg.dialog("open");
}

function initDialog(dlg_id) {
  $("#" + dlg_id).dialog({
    closeOnEscape: false,
    autoOpen: false,
    modal: true,
    width: 400,
    height: 200,
    buttons: [
      {
        id: "OK",
        text: "OK",
        click: function () {
          $(this).dialog("close");
        },
      },
    ],
  });
}

function logoutUser() {
  setClassVisibility("plotuser", false);
  axiosLogoutUser();
}

function setElementTooltip(el, tooltip_text) {
  //console.log(el);
  $(el).attr("data-bs-toggle", "tooltip");
  $(el).attr("data-bs-placement", "top");
  $(el).attr("data-color", "0xaabbcc");
  $(el).attr("title", tooltip_text);
  new bootstrap.Tooltip($(el), {
    boundary: document.body,
  });
}

function SetClassTooltip(classnam, tooltip_text) {
  var tooltipsList = document.getElementsByClassName(classnam);
  //console.log(classnam);
  //console.log(tooltipsList);
  for (let element of tooltipsList) setElementTooltip($(element), tooltip_text);
}

function print2D(table) {
  var n = table.length;
  for (var i = 0; i < n; i++) {
    str = "";
    for (var j = 0; j < m; j++) str += "," + table[i][j];
    console.log(str.substring(1));
  }
}

function TongleClass(classn, visiblen) {
  $("." + classn).each(function (ind, elem) {
    if (visiblen) $(elem).show();
    else $(elem).hide();
  });
}

function adjustView(viewport_width) {
  if (viewport_width < 900) {
    if ($("#polls").is(":visible")) {
      $("#polls").hide();
      $("#surveys").hide();
      $("#minpolls").show();
      $("#minsurveys").show();
      $("#polls_checkbox").hide();
      $("#surveys_checkbox").hide();
    }
  } else {
    if ($("#minpolls").is(":visible")) {
      $("#polls").show();
      $("#surveys").show();
      $("#minpolls").hide();
      $("#minsurveys").hide();
      $("#polls_checkbox").show();
      $("#surveys_checkbox").show();
    }
  }
}

function plotPollUser(type, mode, record_id) {
  window_width = 800;
  window_height = 600;
  ref_id = parseInt(record_id.split("_")[1]);
  [window_left, window_top] = calculateNewWindowPosition(
    window_width,
    window_height
  );
  var href = window.location.href;
  newWindowUrl =
    href.substring(0, href.lastIndexOf("/")) +
    "/php/pages/AnalyzePlotUser.php?id=" +
    ref_id;
  var myWindow = window.open(
    newWindowUrl,
    "Poll Voting",
    `width=800,height=600, left=${window_left}, top=${window_top},scrollbars=yes`
  );
}

//taken from https://codepedia.info/detect-browser-in-javascript
function fnBrowserDetect() {
  let userAgent = navigator.userAgent;
  let browserName;

  if (userAgent.match(/chrome|chromium|crios/i)) {
    browserName = "chrome";
  } else if (userAgent.match(/firefox|fxios/i)) {
    browserName = "firefox";
  } else if (userAgent.match(/safari/i)) {
    browserName = "safari";
  } else if (userAgent.match(/opr\//i)) {
    browserName = "opera";
  } else if (userAgent.match(/edg/i)) {
    browserName = "edge";
  } else {
    browserName = "No browser detection";
  }
  return browserName;
}
