<?php

//connect to database
$link = mysql_connect('localhost', 'root', '') or die("notconnect");
$link2 = mysql_select_db('task', $link) or die("not conntected2");

// Change these 
define('API_KEY', 'BEia8cD26w52ADSY7mf8rW7r3L96aq1F');
define('API_SECRET', '5j81zrl98ym0PM94mVdL5zL4TlkDlLKg083Qi6L6my08fNo6AcgNvp10rbWCf7K2');
define('REDIRECT_URI', 'http://task.dev/task/');
define('SCOPE', 'default activity location');

// You'll probably use a database
session_name('moves');
session_start();

// OAuth 2 Control Flow
if (isset($_GET['error'])) {
    // moves returned an error
    print $_GET['error'] . ': ' . $_GET['error_description'];
    exit;
} elseif (isset($_GET['code'])) {
    // User authorized your application
    if ($_SESSION['state'] == $_GET['state']) {
        // Get token so you can make API calls
        getAccessToken();
    } else {
        // CSRF attack? Or did you mix up your states?
        exit;
    }
} else {
    if ((empty($_SESSION['expires_at'])) || (time() > $_SESSION['expires_at'])) {
        // Token has expired, clear the state
        $_SESSION = array();
    }
    if (empty($_SESSION['access_token'])) {
        // Start authorization process
        getAuthorizationCode();
    }
}

// Congratulations! You have a valid token. Now fetch your profile 
$interval = 31;
$userprofile = fetch('GET', '/user/profile');
$startdate = $userprofile->profile->firstDate;
$startdate2 = DateTime::createFromFormat('Ymd', $startdate);
$startdate2 = $startdate2->format('d/m/Y');
$enddate = date("Ymd");
$enddate2 = DateTime::createFromFormat('Ymd', $enddate);
$enddate2 = $enddate2->format('d/m/Y');
$startdate3 = strtotime($startdate);
$enddate3 = strtotime($enddate);
$datediff = $enddate3 - $startdate3;
$dayscount = floor($datediff / (60 * 60 * 24));

$fromdate = $startdate;
$todate = $enddate;

$current = 0;
for ($x = 0; $x < $dayscount; $x++) {
    if ($dayscount - $current > $interval) {
        $todate = date('Ymd', strtotime($fromdate . ' + 31 days'));
        //print_r($fromdate);


        $current = $current + $interval;

        $fromdate = date('Ymd', strtotime($fromdate . ' + 31 days'));
        fetchdata($fromdate, $todate);
    } else {
        $x = $dayscount - $current;
        $todate = date('Ymd', strtotime($fromdate . ' + ' . $x . ' days'));
        $current = $dayscount;
 
        fetchdata($fromdate, $todate);
        break;
    }
}

// fetch data from moves api
function fetchdata($fromdate, $todate) {
    $user = fetch('GET', '/user/profile');
    $user2 = fetch('GET', '/user/summary/daily?from=' . $fromdate . '&to=' . $todate . '');
    print_r($user2);
    $userid = $user->userId;
    insertindatabase($user2, $userid);
}

//get data from api before insert it
function insertindatabase($user, $userid) {
    for ($i = 0; $i < count($user); $i++) {
        $userDate = $user[$i]->date;
        $LastUpdate = $user[$i]->lastUpdate;
        for ($j = 0; $j < count($user[$i]->summary); $j++) {
            $group = mysql_real_escape_string($user[$i]->summary[$j]->group);
            if ($group == "walking") {
                $steps = mysql_real_escape_string($user[$i]->summary[$j]->steps);
            } else {
                $steps = "";
            }
            $duration = mysql_real_escape_string($user[$i]->summary[$j]->duration);
            $distance = mysql_real_escape_string($user[$i]->summary[$j]->distance);
            InsertActivity($userid, $userDate, $group, $steps, $duration, $distance, $LastUpdate);
        }
    }
}

//insert data to database
function InsertActivity($userid, $userDate, $group, $steps, $duration, $distance, $LastUpdate) {
    $select = mysql_query("SELECT `userid` FROM `activities` WHERE `userid` = '$userid'") or exit(mysql_error());
    if (mysql_num_rows($select)) {
        $selectdata = mysql_query("SELECT * FROM `activities` WHERE `userid` = '$userid' AND `groupp`='$group' AND `datexx`='$userDate' AND `lastupdate`='$LastUpdate' ") or exit(mysql_error());
        if (mysql_num_rows($selectdata)) {
            // $sql2 = mysql_query("DELETE FROM activities WHERE userid='$userid' AND datexx='$userDate' AND groupp='$group' AND duration='$duration' AND distance='$distance' AND stepcount='$steps' AND lastupdate='$LastUpdate'");
            // $sql = mysql_query("INSERT INTO activities (userid,datexx,groupp,duration,distance,stepcount,lastupdate)
            //VALUES ('$userid','$userDate','$group','$duration','$distance','$steps','$LastUpdate')");
        } else {
            $sql = mysql_query("INSERT INTO activities (userid,datexx,groupp,duration,distance,stepcount,lastupdate)
  VALUES ('$userid','$userDate','$group','$duration','$distance','$steps','$LastUpdate')");
        }
    } else {
        $sql2 = mysql_query("DELETE FROM activities WHERE userid='$userid' AND datexx='$userDate' AND groupp='$group' AND duration='$duration' AND distance='$distance' AND stepcount='$steps' AND lastupdate='$LastUpdate'");
        $sql = mysql_query("INSERT INTO activities (userid,datexx,groupp,duration,distance,stepcount,lastupdate)
  VALUES ('$userid','$userDate','$group','$duration','$distance','$steps','$LastUpdate')");
    }
}

function getAuthorizationCode() {
    $params = array(
        'response_type' => 'code',
        'client_id' => API_KEY,
        'scope' => SCOPE,
        'state' => uniqid('', true), // unique long string
        'redirect_uri' => REDIRECT_URI,
    );

    // Authentication request
    $url = 'https://api.moves-app.com/oauth/v1/authorize?' . http_build_query($params);

    // Needed to identify request when it returns to us
    $_SESSION['state'] = $params['state'];

    // Redirect user to authenticate
    header("Location: $url");
    exit;
}

function getAccessToken() {
    $params = array(
        'grant_type' => 'authorization_code',
        'client_id' => API_KEY,
        'client_secret' => API_SECRET,
        'code' => $_GET['code'],
        'redirect_uri' => REDIRECT_URI,
    );

    // Access Token request
    $url = 'https://api.moves-app.com/oauth/v1/access_token?' . http_build_query($params);

    // Tell streams to make a POST request
    $context = stream_context_create(
            array('http' =>
                array('method' => 'POST',
                )
            )
    );

    // Retrieve access token information
    $response = file_get_contents($url, false, $context);

    // Native PHP object, please
    $token = json_decode($response);

    // Store access token and expiration time
    $_SESSION['access_token'] = $token->access_token; // guard this! 
    $_SESSION['expires_in'] = $token->expires_in; // relative time (in seconds)
    $_SESSION['expires_at'] = time() + $_SESSION['expires_in']; // absolute time

    return true;
}

function fetch($method, $resource, $body = '') {
    //print $_SESSION['access_token'];

    $opts = array(
        'http' => array(
            'method' => $method,
            'header' => "Authorization: Bearer " . $_SESSION['access_token'] . "\r\n" . "x-li-format: json\r\n"
        )
    );

    // Need to use HTTPS
    $url = 'https://api.moves-app.com/api/1.1' . $resource;

    // Append query parameters (if there are any)
    /* if (count($params)) {
      $url .= '?' . http_build_query($params);
      } */

    // Tell streams to make a (GET, POST, PUT, or DELETE) request
    // And use OAuth 2 access token as Authorization
    $context = stream_context_create($opts);

    // Hocus Pocus
    $response = file_get_contents($url, false, $context);

    // Native PHP object, please
    return json_decode($response);
}
