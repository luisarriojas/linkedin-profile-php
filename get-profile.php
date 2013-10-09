<?php
/*
LinkedIn Profile PHP
Copyright (c) 2013 Luis Enrique Arriojas Catalini
http://opensource.org/licenses/MIT
*/

//setup
$api_key = "";
$secret_key = "";
$scope = "r_fullprofile";
$redirect_uri = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
$search = "/people/~:(picture-url,first-name,last-name,headline,location:(name),industry,public-profile-url,summary,positions:(title,company:(name,industry),start-date,end-date,is-current,summary),skills:(skill:(name)),projects:(name,url,description),educations:(school-name,degree,field-of-study,start-date,end-date,notes),languages:(language:(name),proficiency:(name)),volunteer:(volunteer-experiences:(role,organization:(name),description)))";

session_start();
if (!(isset($_GET['code'])) && !(isset($_GET['error']))) {
    //Generate Authorization Code by redirecting user to LinkedIn's authorization dialog
    $_SESSION['state'] = md5(time());
    header("location: https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=" . $api_key . "&scope=" . $scope . "&state=" . $_SESSION['state'] . "&redirect_uri=" . $redirect_uri);
    exit;
} else {
    if ($_SESSION['state'] == $_GET['state']) {
        if (isset($_GET['code'])) {
            //Request Access Token by exchanging the authorization_code for it
            $data = array(
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'redirect_uri' => $redirect_uri,
                'client_id' => $api_key,
                'client_secret' => $secret_key
            );
            $options = array(
                'http' => array(
                    'method' => 'POST'
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents("https://www.linkedin.com/uas/oauth2/accessToken?" . http_build_query($data), false, $context);
            $result = json_decode($result, true);

            //Make the API call
            $data = array(
                'oauth2_access_token' => $result['access_token'],
                'format' => 'json'
            );
            $options = array(
                'http' => array(
                    'method' => 'GET'
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents("https://api.linkedin.com/v1" . $search . "?" . http_build_query($data), false, $context);

            $file = fopen("profile.json", "w");
            fwrite($file, $result);
            fclose($file);

            echo "Update successful !!<br>";
            echo '<a href="http://' . $_SERVER['SERVER_NAME'] . '">Check it</a>';


        } else if (isset($_GET['error'])) {
            echo $_GET['error'] . " - " . $_GET['error_description'];
        } else {
            header("location: " . $_SERVER['SERVER_NAME']);
        }
    } else {
        echo "The received STATE value is different from created.";
    }
    unset($_SESSION['state']);
}
?>