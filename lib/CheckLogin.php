<?php
// Check URL
if (($sitesecure && $_SERVER['HTTPS'] != "on") || $_SERVER['HTTP_HOST'] != $sitehost)
{
  header("location: $siteurl/login.php?msg=badurl");
}

// init session
session_start();

function killsession($all=false)
{
  global $conn;

  // get vars
  $uid = mysqli_real_escape_string($conn, $_COOKIE['u']);
  $series = mysqli_real_escape_string($conn, $_COOKIE['s']);
  $token = mysqli_real_escape_string($conn, $_COOKIE['t']);

  // clear token
  $sql = "DELETE FROM `token` WHERE `series` = '$series' OR `hash` = '$token'";
  if ($all) { $sql .= " OR `user_id` = $uid"; } // delete all tokens for that user
  $result = mysqli_query($conn, $sql." LIMIT 1") or die("SQL Error:\n$sql\n".mysqli_error($conn));

  // destroy all session vars
  $_COOKIE['u'] = "";
  $_COOKIE['s'] = "";
  $_COOKIE['t'] = "";
  $_SESSION = array();

  // destroy session cookie
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }

  // destroy session
  session_destroy();

  // redirect to login page
  header('location: login.php?msg=kill');
  exit;
}

if (!empty($_COOKIE['u']) && !empty($_COOKIE['s']) && !empty($_COOKIE['t']))
{
  // get session data
  $uid = mysqli_real_escape_string($conn, $_COOKIE['u']);
  $series = mysqli_real_escape_string($conn, $_COOKIE['s']);
  $token = mysqli_real_escape_string($conn, $_COOKIE['t']);
  $ip = $_SERVER['REMOTE_ADDR'];
}
else
{
  // no token
  killsession();
}

// clean old tokens
$sql = "DELETE FROM `token` WHERE `expire` < UNIX_TIMESTAMP()";
$result = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

// get data from token
$sql = "SELECT `hash`, `ip`, `lifetime` FROM `token` WHERE `user_id` = $uid AND `series` = '$series' LIMIT 1";
$result = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

// if series found
if (mysqli_num_rows($result) == 1)
{
  // get row
  $row = mysqli_fetch_assoc($result);

  if ($row['hash'] != $token && $row['ip'] != $ip)
  {
    // stolen token used meanwhile
    $sql0 = "UPDATE `user` SET `rank` = 0 WHERE `id` = $uid LIMIT 1";
    $res0 = mysqli_query($conn, $sql0) or die("SQL Error:\n$sql0\n".mysqli_error($conn));
    // we destroy all tokens of this user
    killsession(true);
  }
/*
  elseif ($row['ip'] != $ip)
  {
    // bad IP
    killsession();
  }
*/
  else
  {
    // get user info from database
    $sql0 = "SELECT `email`, `rank`, `firstname`, `method` FROM `user` WHERE `id` = $uid LIMIT 1";
    $res0 = mysqli_query($conn, $sql0) or die("SQL Error:\n$sql0\n".mysqli_error($conn));

    // TODO: catch exception if no result

    // get user row
    $urow = mysqli_fetch_assoc($res0);

    if ($urow['rank'] <= 1)
    {
      // inactive user or stolen account
      killsession(true);
    }
    else
    {
      // get google token if needed
      if ($urow['method'] == "google")
      {
        // get user info from database
        $sqltok = "SELECT * FROM `google_token` WHERE `user_id` = $uid LIMIT 1";
        $restok = mysqli_query($conn, $sqltok) or die("SQL Error:\n$sql0\n".mysqli_error($conn));

        if (mysqli_num_rows($restok) != 1) {
		// get token from google
		// TODO: change url
		header('location: /quickstart.php');
	}

        // get token row
        $trow = mysqli_fetch_assoc($restok);
      }

      // get session lifetime
      $expire = time() + $row['lifetime'];

      // generate a new token
      $newtoken = hash('sha256', mcrypt_create_iv(16, MCRYPT_RAND), false);

      $sql1 = "UPDATE `token` SET `hash` = '$newtoken', `expire` = $expire WHERE `series` = '$series'";
      $res1 = mysqli_query($conn, $sql1) or die("SQL Error:\n$sql1\n".mysqli_error($conn));

      if ($res1 !== false)
      {
        setcookie ('u', $uid, $expire, "/", $sitehost, $sitesecure, true);
        setcookie ('s', $series, $expire, "/", $sitehost, $sitesecure, true);
        setcookie ('t', $newtoken, $expire, "/", $sitehost, $sitesecure, true);
        $_SESSION['uid'] = $uid;
        $_SESSION['email'] = $urow['email'];
        $_SESSION['rank'] = $urow['rank'];
        $_SESSION['firstname'] = $urow['firstname'];
        $_SESSION['first_token'] = $trow['first_token'];
        $_SESSION['current_token'] = $trow['current_token'];
        session_write_close();
      }
      else
      {
        // update error
        killsession();
      }
    }
  }
}
else
{
  // bad series
  killsession();
}

// token OK
?>
