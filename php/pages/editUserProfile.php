<?php
// We need to use sessions, so you should always start sessions using the below code.
session_start();
// If the user is not logged as an admin redirect to the login page.
if (!isset($_SESSION['loggedin0'])) {
	header('Location: ../../index.html');
	exit;
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit user profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.2/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">

    <link rel="stylesheet" href="../../css/leaflet.awesome-markers.css">
    <link rel="stylesheet" href="../../css/country_select/countrySelect.min.css">
    <link rel="stylesheet" href="../../css/country_select/demo.css">

    <link rel='stylesheet' href='../../css/yearpicker.css' />
    <script src='../../js/yearpicker.js'></script>

    <script src="../../js/utils.js"></script>
    <script src="../../js/axios.js"></script>

    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/rating.css">
    <script>
    var currentUserId;
    async function init() {
        const regionNames = new Intl.DisplayNames(
            ['en'], {
                type: 'region'
            }
        );
        initDialog('dialog');
        currentUserId = getUrlParams();
        $('.yearpicker').yearpicker({
            // Auto Hide
            autoHide: true,
            // Initial Year
            year: new Date().getFullYear(),
            // Start Year
            startYear: 1940,
            // End Year
            endYear: new Date().getFullYear(),
        });
        $("#toggle-password").click(function() {
            $(this).toggleClass("fa-eye fa-eye-slash");
            if ($('#password').attr("type") == "password") {
                $('#password').attr("type", "text");
            } else {
                $('#password').attr("type", "password");
            }
        });

        $("#submitfrm").submit(function(event) {
            event.preventDefault();
            ValidateForm();
        });

        //fillSelectFromArray('gender_select', genders);
        //fillSelectFromArray('lang_select', prefLanguages);
        let rf = window.opener;
        console.log(SelectedLanguage);
        if (rf.translateHashTable['ui_langs'].length > 0) fillSelectFromArray('lang_select', rf.translateHashTable[
            'ui_langs']);
        if (rf.translateHashTable['ui_genders_' + SelectedLanguage].length > 0) fillSelectFromArray('gender_select',
            rf.translateHashTable['ui_genders_' + SelectedLanguage]);


        getDbRecord = await axiosGetQueryResultsPromise("SELECT * FROM users WHERE id=" + currentUserId);
        if (getDbRecord.data.statusCode == 200) {
            let rec = getDbRecord.data.records[0];
            /*console.log(rec);
                birthyear:"2022"
                comments: null
                country: "Gr"
                gender: "1"
                id: "6"
                lang: "1"
                password: "a"
                user_type: "0"
                username: "abc@a.com"*/
            $('#email').val(rec.email);
            $('#password').val(rec.password);
            $('#username').val(rec.username);

            $('#country_selector').countrySelect("setCountry", regionNames.of(rec.country.toUpperCase()));
            SelectedLanguage = rec.lang;
            if (rf.translateHashTable['ui_genders_' + SelectedLanguage].length > 0)
                fillSelectFromArray('gender_select', rf.translateHashTable['ui_genders_' + SelectedLanguage]);

            $(`#gender_select option[value="${rec.gender}"]`).prop("selected", true);
            $(`#lang_select option[value="${rec.lang}"]`).prop("selected", true);
            //$('#lang_select').text(rec.lang);
            // $("#lang_select option").filter(function() {
            //     return $(this).text() == rec.lang;
            // }).prop('selected', true);
            $('#yearpicker').val(rec.birthyear);
            getCreditsSql =
                "SELECT sum(p.credits) as userCredits FROM usercompletedpolls as ucp INNER JOIN polls as p on ucp.pollId = p.id WHERE ucp.userId = " +
                currentUserId;
            getDBCredits = await axiosGetQueryResultsPromise(getCreditsSql);
            let uc = 0;
            if (getDBCredits.data.statusCode == 200)
                uc = getDBCredits.data.records[0].userCredits;
            $('#credits').val(uc);
        } else {
            displayMsgJQ("dialog", "User edit", "Error Loading user info", ERROR_MSG);
        }
        //currentUserId = window.opener.currentUserId;
    }

    function setUILanguageUP(obj) {
        if (obj) SelectedLanguage = $("#lang_select option:selected").text()
        let rf = window.opener;
        if (rf.translateHashTable['ui_genders_' + SelectedLanguage].length > 0) fillSelectFromArray('gender_select', rf
            .translateHashTable['ui_genders_' + SelectedLanguage]);
    }

    async function ValidateForm() {
        let password = $('#password').val();
        if (password.length == 0) {
            displayError("empty password", 'Su');
            return -2;
        }
        let user_type = 0; //no admin right
        let country = $('#country_selector_code').val();
        let lang = $("#lang_select option:selected").text();
        let gender = $('#gender_select').val();
        let birthyear = $('#yearpicker').val();
        let username = $('#username').val();
        let email = $('#email').val();
        const result = await axiosUpdateUser(currentUserId, password, country, lang, gender, birthyear, username,
            email);
        if (result.data.statusCode == 200) {
            displayMsgJQ("dialog", "User edit", "User info submitted succesfully", INFO_MSG);
        } else if (result.data.statusCode == 205) {
            displayMsgJQ("dialog", "User edit",
                "There is another registered user with the same username or email.", INFO_MSG);
        } else {
            displayMsgJQ("dialog", "User edit", "Error updating user info", ERROR_MSG);
        }
    }
    </script>
</head>

<body onload="init()">
    <div id="dialog" title=""></div>
    <form id="submitfrm">
        <div class=" input-group mb-3 ">
            <div class="col-2 d-none d-lg-block d-md-block d-sm-block">
                <label for="username">Username</label>
            </div>
            <div class="input-group-prepend">
                <span class="input-group-text ms-4" id="basic-addon1"><i class="fas fa-user"></i></span>
            </div>
            <div class="col-6">
                <input type="text" class="input form-control" id="username" aria-describedby="emailHelp"
                    placeholder="Enter username">
            </div>
        </div>
        <div class=" input-group mb-3 ">
            <div class="col-2 d-none d-lg-block d-md-block d-sm-block">
                <label for="email">Email</label>
            </div>
            <div class="input-group-prepend">
                <span class="input-group-text ms-4" id="basic-addon1"><i class="fas fa-user"></i></span>
            </div>
            <div class="col-6">
                <input type="email" class="input form-control" id="email" aria-describedby="emailHelp"
                    placeholder="Enter email">
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="col-2 d-none d-lg-block d-md-block d-sm-block">
                <label for="password">Password</label>
            </div>
            <div class="input-group-prepend">
                <span class="input-group-text ms-4" id="basic-addon1"><i class="fas fa-lock"></i></span>
            </div>
            <div class="col-6">
                <input type="password" class="input form-control" name="password" id="password"
                    placeholder="Password" />
            </div>
            <span><i class="fa fa-eye-slash" aria-hidden="true" id="toggle-password"></i></span>
        </div>
        <div class="input-group mb-3">
            <div class="form-item">
                <span>Country of origin: </span>
                <input id="country_selector" type="text">
                <label for="country_selector" style="display:none;">Select a country here...</label>
            </div>
            <div class="form-item" style="display:none;">
                <input type="text" id="country_selector_code" name="country_selector_code" data-countrycodeinput="1"
                    readonly="readonly" placeholder="Selected country code will appear here" />
                <label id="ab12" for="country_selector_code"> and the selected country code will be updated
                    here</label>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="form-item">
                <label for="gender_select">Gender</label>
                <select id="gender_select" class="form-select form-select-sm"
                    aria-label=".form-select-sm example"></select>
            </div>
            <div class="form-item">
                <label for="lang_select">Preferred language</label>
                <select onchange="setUILanguageUP(this);" id="lang_select" class="form-select form-select-sm"
                    aria-label=".form-select-sm example"></select>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="form-item">
                <label for="password">Credits</label>
                <div class="col-6">
                    <input type="number" class="input form-control" name="password" id="credits" disabled />
                </div>
            </div>
            <div class="form-item">
                <label for="yearpicker">Year of birth</label>
                <div class="col-6">
                    <input id="yearpicker" type="text" class="yearpicker input form-control" value="">
                </div>
            </div>

        </div>


        <button type="submit" class="btn btn-primary" onclick="ValidateForm()">Submit</button>
        <button type="button" class="btn btn-secondary" onclick="closeFrm();">Cancel</button>
        <div id="userMsgSu" class="m-2 row form-item bg-danger p-2" style="display:none">
            <label id="textMsgSu" class="p-2 text-white">Year of birth</label>
            <button id="closeMsgSu" type="button" class="btn btn-secondary"
                onclick="msgDisplay('none', 'Su');">Ok</button>
            <button id="closeModalSu" type="button" class="btn btn-secondary"
                onclick="msgDisplay('none', 'Su');$('#signUpModal').modal('hide');">Ok</button>
        </div>
    </form>
    <script src="../../js/country_select/countrySelect.min.js"></script>
    <script>
    $("#country_selector").countrySelect({
        defaultCountry: "gr",
        // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
        //responsiveDropdown: true,
        //preferredCountries: ['ca', 'gb', 'us']
    });
    </script>
</body>

</html>