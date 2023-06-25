var MarkerColors = ['blue', 'red', 'green', 'darkred', 'purple', 'orange', 'darkgreen', , 'darkpurple', 'cadetblue'];

async function getUsersCount() {
    const result = await axiosGetQueryResultsPromise("Select count(*) as cnt from users");
    console.log()
    if (result.data.statusCode == 200) {
        $('#userCount').html(result.data.records[0]['cnt']);
    } else $('#userCount').html("");
}

function msgDisplay(stl, md) {
    document.getElementById("userMsg" + md).style.display = stl;
}

function displayError(msg, md) {
    document.getElementById("userMsg" + md).classList.remove('bg-success');
    document.getElementById("userMsg" + md).classList.add('bg-danger');
    document.getElementById("textMsg" + md).innerHTML = msg;
    document.getElementById("closeModal" + md).style.display = 'none';
    document.getElementById("closeMsg" + md).style.display = 'block';
    msgDisplay('block', md);
}

function displayInfo(msg, md) {
    document.getElementById("userMsg" + md).classList.remove('bg-danger');
    document.getElementById("userMsg" + md).classList.add('bg-success');
    document.getElementById("textMsg" + md).innerHTML = msg;
    document.getElementById("closeModal" + md).style.display = 'block';
    document.getElementById("closeMsg" + md).style.display = 'none';
    msgDisplay('block', md);
}

function clearSignInModal() {
    document.getElementById("email").value = "";
    document.getElementById("password").value = "";
    document.getElementById("userMsgSi").style.display = "none";
}

function sign_up_error(errCode) {
    console.log(errCode);
    switch (errCode) {
        case 201:
            displayError("Internal error #201. Try again later", 'Su');
            break;
        case 202:
            displayError("This username and/or email is/are already used by another registered member! Try something else.", 'Su');
            break;
        case 203:
            displayError("Internal error #203. Try again later", 'Su');
            break;
        case 204:
            displayError("Internal error #204. Try again later", 'Su');
            break;
        case 205:
            displayError("Internal error #205. Try again later", 'Su');
            break;
        case 206:
            displayError("Internal error #206. Try again later", 'Su');
            break;
        default:
            displayError("Internal error. Try again later", 'Su');
            break;
    }
}

async function registerUser() {
    event.preventDefault(); //do not submit form
    let email = $('#new_email').val();
    let username = $('#new_username').val();
    if (username.length == 0 && email.length == 0) {
        displayError("Please give your username or your email", 'Su');
        return -1;
    }
    let password = $('#new_password').val();
    if (password.length == 0) {
        displayError("empty password", 'Su');
        return -2;
    }
    let user_type = 0; //no admin right
    let country = $('#country_selector_code').val();
    let lang = $("#lang_select option:selected").text();
    let gender = $('#gender_select').val();
    let birthyear = $('#yearpicker').val();
    console.log({ username, email, password, country, lang, gender, birthyear });
    const result = await axiosRegisterUser(username, email, password, country, lang, gender, birthyear);
    console.log(result);
    if (result.data.statusCode == 200) {
        displayInfo('Welcome ' + username, 'Su');
        currentUserId = result.data.id;
        currentUserType = 0;
        $("#loginBtn").hide();
        $("#closeModalSi").focus();
        $("#signUpBtn").hide();
        $("#userBtns").show();
        $("#reg_user_ch").text(username);
        setClassVisibility('plotuser', true);
        setUILanguage((SelectedLanguage = lang));
    } else sign_up_error(result.data.statusCode);
}

function emptySignUpForm() {
    $("#new_email").val("");
    $("#new_username").val("");
    $("#new_password").val("");
}

function logoutf() {
    logoutUser();
    $("#loginBtn").show();
    $("#signUpBtn").show();
    $("#userBtns").hide();
}