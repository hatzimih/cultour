function authenticateUser(event, username, password) {
    event.preventDefault(); //do not submit form
    path_to_php = path_to_root + "/php/services/authenticateUser.php";
    if (username != "" && password != "") {
        axios({
                method: "post",
                url: path_to_php,
                data: {
                    username: username,
                    password: password,
                },
            })
            .then(function(response) {
                let result = response.data;
                console.log(result);
                switch (result.statusCode) {
                    case 200:
                        //succesfull login, redirect to the proper home page (admin's or user's)
                        currentUserId = result.user_id;
                        currentUserType = result.user_type;
                        displayInfo("Welcome back " + username + "!", "Si");
                        if (result.user_type == 1)
                            window.location.replace("php/pages/admin_home.php");
                        else {
                            $("#loginBtn").hide();
                            $("#closeModalSi").focus();
                            $("#signUpBtn").hide();
                            $("#userBtns").show();
                            $("#reg_user_ch").text(username);
                            setClassVisibility('plotuser', true);
                            setUILanguage((SelectedLanguage = result.user_lang));
                        }
                        //else window.location.replace('php/pages/user_home.php');
                        break;
                    case 201:
                        displayInfo(
                            "Error connecting to a valid db. Try again later!",
                            "Si"
                        );
                        break;
                    case 202:
                        displayInfo(
                            "Please fill both the username and password fields!",
                            "Si"
                        );
                        break;
                    case 203:
                        displayInfo(
                            "Incorrect username and/or password. Try again!",
                            "Si"
                        );
                        break;
                    case 204:
                        displayInfo(
                            "Incorrect username and/or password. Try again!",
                            "Si"
                        );
                        break;
                }
            })
            .catch(function(error) {
                console.log(error);
            });
    } else {
        displayError("Please fill all the fields!", "Si");
    }
}

function axiosGetQueryCount(query) {
    path_to_php = path_to_root + "/php/services/getQueryCount.php";
    axios({
            method: "post",
            url: path_to_php,
            data: {
                query: query,
            },
        })
        .then(function(response) {
            let result = response.data;
            console.log(result.count);
            $("#userCount").html(result.count);
        })
        .catch(function(error) {
            console.log(error);
            return -1;
        });
}

async function axiosGetQueryResultsPromise(query) {
    path_to_php = path_to_root + "/php/services/getMySqlRecordsPromise.php";
    try {
        const result = await axios.get(path_to_php, {
            params: {
                sql_query: query,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosExecQueryPromise(query) {
    path_to_php = path_to_root + "/php/services/ExecMySqlQueryPromise.php";
    try {
        const result = await axios.get(path_to_php, {
            params: {
                sql_query: query,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosRegisterUser(
    username,
    email,
    password,
    country,
    lang,
    gender,
    birthyear
) {
    path_to_php = path_to_root + "/php/services/RegisterUser.php";
    var form = new FormData();
    form.append("username", username);
    form.append("email", email);
    form.append("password", password);
    form.append("country", country);
    form.append("lang", lang);
    form.append("gender", gender);
    form.append("birthyear", birthyear);
    try {
        const result = await axios.post(path_to_php, form, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosUpdateUser(id, password, country, lang, gender, birthyear, username, email) {
    path_to_php = path_to_root + "/php/services/UpdateUser.php";
    var form = new FormData();
    form.append("id", id);
    form.append("password", password);
    form.append("country", country);
    form.append("lang", lang);
    form.append("gender", gender);
    form.append("birthyear", birthyear);
    form.append("username", username);
    form.append("email", email);
    try {
        const result = await axios.post(path_to_php, form, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosExecQueriesPromise(queries) {
    path_to_php = path_to_root + "/php/services/ExecMySqlQueriesPromise.php";
    try {
        const result = await axios.get(path_to_php, {
            params: {
                sql_queries: queries,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosDeletePoll(poll_id) {
    path_to_php = path_to_root + "/php/services/ExecMySqlDeletePoll.php";
    try {
        const result = await axios.get(path_to_php, {
            params: {
                poll_id: poll_id,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosDeleteSurvey(survey_id) {
    path_to_php = path_to_root + "/php/services/ExecMySqlDeleteSurvey.php";
    try {
        const result = await axios.get(path_to_php, {
            params: {
                survey_id: survey_id,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosDeleteUser(user_id) {
    path_to_php = path_to_root + "/php/services/ExecMySqlDeleteUser.php";

    try {
        const result = await axios.get(path_to_php, {
            params: {
                user_id: user_id,
            },
        });
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosLogoutUser() {
    path_to_php = path_to_root + "/php/services/logout.php";
    try {
        const result = await axios.get(path_to_php, {});
        window.location.href = path_to_root + "/index.html";
        return result;
    } catch (error) {
        console.log(error);
    }
}

async function axiosGetMathematicaResult(str) {
    url = `https://dplj2022.glitch.me/?mathstr=${str}`;
    let config = {
        url,
        method: "get",
        //headers: {
        //    'Access-Control-Allow-Origin': '*',
        //    'Access-Control-Allow-Headers': '*',
        //    'Access-Control-Allow-Credentials': 'true'
        //}
    };
    try {
        const result = await axios.request(config);
        return result;
    } catch (error) {
        console.log(error);
    }
}