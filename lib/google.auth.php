<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/settings.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/google.config.php';
require_once __DIR__.'/google.functions.php';

$debug = true;

// connect to a gmail account from userid or token value
function auth_gmail($userid = 0, $token = "", $refresh = "")
{
  global $conn, $cfgGoogle, $debug;

  $logdest = "auth";

  if ($userid > 0)
  {
    $logdest = $userid;

    // get users info
    $sql = "SELECT u.*, gt.* FROM `user` u, `google_token` gt WHERE u.`method` = 'google' AND u.`id` = gt.`user_id` AND u.`id` = $userid LIMIT 1";
    $res = mysqli_query($conn, $sql) or logthat($logdest, "SQL Error:\n$sql\n".mysqli_error($conn));

    if (mysqli_num_rows($res) > 0)
    {
      $row = mysqli_fetch_assoc($res);

      // get user id and email
      if ($debug) { logthat($logdest, "auth to mailbox ".$row['email']); }

      // set vars
      $token = json_decode($row['access_token'], true);
      $refresh = $row['refresh_token'];
    }
    else
    {
      logthat($logdest, "user id $userid not found in database");
      return false;
    }
  }

  if (!empty($token))
  {
    try {
      // Setup google oAuth2
      $client = new Google_Client();
      $client->setApplicationName($cfgGoogle['appName']);
      $client->setAuthConfig($cfgGoogle['secret']);
      $client->addScope($cfgGoogle['scopes']);
      $client->setAccessType($cfgGoogle['accessType']);

    } catch (Exception $e) {
      logthat($logdest, "Got exception with Google client:\n" .  $e->getMessage());
      gAuth("yes");
    }

    // check value
    if ($token == "null") { $token = ""; } elseif ($debug) { logthat($logdest, "got access token : ".json_encode($token)); }
    if ($refresh == "null") { $refresh = ""; } elseif ($debug) { logthat($logdest, "got refresh token : ".$refresh); }

    $client->setAccessToken($token);
    if ($debug) { logthat($logdest, "access token set"); }

    // get token expiration time
    $expire = $token['created'] + $token['expires_in'];
    if ($debug) { logthat($logdest, "expires in: ". ($expire - time()) . "s"); }

    // refresh the token if it's expired.
    if ($client->isAccessTokenExpired() || $expire < time())
    {
      if ($debug) { logthat($logdest, "need to refresh token"); }

      // if the refresh token is empty
      if (empty($refresh) || $refresh == "null")
      {
        // restart full auth
        if (isset($_SESSION))
        {
          logthat($logdest, "restart full auth for token: ".$token['access_token']);
          gAuth("yes");
        }
        else
        {
          logthat($logdest, "cannot refresh token: refresh empty for token: ".$token['access_token']);
        }
        return false;
      }

      if ($debug) { logthat($logdest, "we already have a refresh token : $refresh"); }

      // get a new access token from the refresh token
      $token = $client->fetchAccessTokenWithRefreshToken($refresh);

      if ($token == "null") { $token = ""; }
      if ($debug) { logthat($logdest, "got new token : ". json_encode($token)); }

      // check if new token is valid
      $payload = $client->verifyIdToken($token['id_token']);

      if ($payload)
      {
        if ($debug) { logthat($logdest, "ok token is verified"); }
      }
      else
      {
        // should restart auth with force approval
        logthat($logdest, "token invalid: ".json_encode($token));

        // if CLI mode
        if ($userid > 0)
        {
          // remove token from database
          $sql = "UPDATE `google_token` SET `access_token` = '', `refresh_token` = '', `expires_at` = 0 WHERE `user_id` = $userid";
          $res = mysqli_query($conn, $sql) or logthat($logdest, "SQL Error:\n$sql\n".mysqli_error($conn));
        }
        // or if browser mode
        elseif (isset($_SESSION))
        {
          unset($_SESSION['access_token']);
          session_destroy();
        }

        // token invalid, get out
        return false;
      }

      // set new expiration date
      $expire = $token['created'] + $token['expires_in'];
      if ($debug) { logthat($logdest, "expires in: ". ($expire - time()) . "s"); }

      // set new access token (not needed?)
      $client->setAccessToken($token);

      // escape that for database use
      $etoken = mysqli_real_escape_string($conn, json_encode($token));
      $eexpire = mysqli_real_escape_string($conn, ($expire));

    } // end refresh

    // AUTH OK

    // GOOGLE+ PROFILE INFO
    try {
      $plusService = new Google_Service_Plus($client);

      // get user profile info
      $gprofile = $plusService->people->get("me");
      $userLanguage = $gprofile->language;
      $userFirstname = $gprofile->name->givenName;
      $userLastname = $gprofile->name->familyName;
      $userDisplayName = $gprofile->displayName;
      $userImage = $gprofile->image->url;

    } catch (Exception $e) {
      logthat($logdest, "Got exception with Google+ service:\n" .  $e->getMessage());
      gAuth("yes");

      $userLanguage = "en";
      $userFirstname = "";
      $userLastname = "";
      $userDisplayName = "";
      $userImage = "";
    }

    // GMAIL BOX INFO
    try {
      $service = new Google_Service_Gmail($client);

    } catch (Exception $e) {
      logthat($logdest, "Got exception with gmail service:\n" .  $e->getMessage());
      echo "Erreur : vous n'avez pas Gmail d'activé sur votre compte Google semble-t-il!";
      return false;
    }

    $profile = $service->users->getProfile("me");
    // Object ( [emailAddress] => alexb38@gmail.com [historyId] => 10565267 [messagesTotal] => 62605 [threadsTotal] => 34045

    $userEmail = mysqli_real_escape_string($conn, $profile->emailAddress);

    // get user info if he exists
    $sql = "SELECT * FROM `user` WHERE `email` = '$userEmail' LIMIT 1";
    $res = mysqli_query($conn, $sql) or logthat($logdest, "SQL Error:\n$sql\n".mysqli_error($conn));

    if (mysqli_num_rows($res) > 0)
    {
      $row = mysqli_fetch_assoc($res);

      // get user vars
      $userid = $row['id'];
      $active = $row['active'];
      $userSignupDate = $row['firstseen'];

      // update user info
      if ($userLanguage != $row['language'] || $userFirstname != $row['firstname'] || $userLastname != $row['lastname'])
      {
        $sqlu = "UPDATE `user` SET `language` = '$userLanguage', `firstname` = '$userFirstname', `lastname` = '$userLastname' WHERE `id` = $userid";
        $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
      }
    }
    else
    {
      // check if allowed to create account
      if (!isset($_SESSION['invite']) || !in_array($_SESSION['invite'], array("HELLOFYI2K17", "BETAFYI2K17")))
      {
        // check position in waiting list
        $sql = "SELECT `position` FROM `waitinglist` WHERE `email` = '".gmailCleanAddress($userEmail)."' LIMIT 1";
        $res = mysqli_query($conn, $sql) or logthat($logdest, "SQL Error:\n$sql\n".mysqli_error($conn));

        if ($res && mysqli_num_rows($res))
        {
          $row = mysqli_fetch_assoc($res);

          // if position is not 0 (valve opened), deny access
          if ($row['position'] !== 0)
          {
            header('location: /');
            exit;
          }
          else
          {
            $tag = "waitinglist";
          }
        }
      }
      else
      {
        $tag = $_SESSION['invite'];
      }

      // create user account
      $sql = "INSERT INTO `user`(`email`, `password`, `method`, `language`, `rank`, `firstseen`, `lastseen`, `firstname`, `lastname`, `tag`) VALUES('$userEmail', NULL, 'google', '$userLanguage', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '$userFirstname', '$userLastname', '$tag')";
      $res = mysqli_query($conn, $sql) or logthat($logdest, "SQL Error:\n$sql\n".mysqli_error($conn));
      $userid = mysqli_insert_id($conn);

      // user vars
      $active = 1;
      $userSignupDate = time();

      // insert user_id to backend table to do first full sync in background
      $sql = "INSERT INTO `google_fullsync`(`user_id`, `date`, `progress`) VALUES($userid, UNIX_TIMESTAMP(), 0)";
      $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

      // insert user_id to google_label
      $sql = "INSERT INTO `google_label`(`user_id`) VALUES($userid)";
      $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
    }

    // escape that for database use
    $etoken = mysqli_real_escape_string($conn, json_encode($token));
    $eexpire = mysqli_real_escape_string($conn, ($expire));
    $erefresh = mysqli_real_escape_string($conn, ($refresh));

    // get google token if he has any
    $sql = "SELECT * FROM `google_token` WHERE `user_id` = $userid LIMIT 1";
    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

    // if user already has a google token in database
    if (mysqli_num_rows($res) > 0)
    {
      $row = mysqli_fetch_assoc($res);

      if (!empty($erefresh)) { $setrefresh = ", `refresh_token` = '".$erefresh."'"; } else { $setrefresh = ""; }

      // just update the current token
      $sqlu = "UPDATE `google_token` SET `access_token` = '$etoken', `expires_at` = ".$eexpire.$setrefresh." WHERE `user_id` = $userid";
      $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

      if (empty($refresh))
      {
        // get first token to get refresh token
        $refresh = $row['refresh_token'];

	// check if refresh is not empty
	if (!empty($refresh))
	{
          if ($debug) { logthat($userid, "got refresh token from db : " . $refresh); }
          if (isset($_SESSION))
          {
            $_SESSION['access_token']['refresh_token'] = $refresh;
            if ($debug) { logthat($userid, "session refresh token set"); }
          }
	}
	else
	{
	  // restart full auth if session
	  if (isset($_SESSION))
	  {
	    logthat($userid, "restart full auth as no refresh token in session nor db!");
	    gAuth("yes");
	    exit;
	  }
	}
      }
    }
    // user has no token in database, but we have a refresh token
    elseif (!empty($refresh))
    {
      // insert the token in database
      $erefresh = mysqli_real_escape_string($conn, ($refresh));
      $sql = "INSERT INTO `google_token`(`user_id`, `refresh_token`, `access_token`, `expires_at`) VALUES($userid, '$erefresh', '$etoken', $eexpire)";
      $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
    }
    // user has no token in database and no refresh token
    else
    {
      // should get a new token with force approval
      logthat($userid, "refresh token empty, we may force approval to get a new one!");
      return false;
    }

    // TOKEN DEBUG INFO
    /* if ($debug)
    {
      logthat($userid, "Token: ".$token['access_token']);
      logthat($userid, "Refresh token: ".$refresh);
      logthat($userid, "Expires in: ".date("Y-m-d H:i:s", $expire));
    } */

    if ($userid > 0 && empty($refresh))
    {
      logthat($userid, "refresh token still empty");
      return false;
    }

    // OK return google client
    if (!empty($token['access_token']))
    {
      if ($debug) { logthat($userid, "Auth successful!"); }

      if (isset($_SESSION))
      {
        $_SESSION['userid'] = $userid;
        $_SESSION['email'] = $userEmail;
        $_SESSION['image'] = $userImage;
        $_SESSION['name'] = $userDisplayName;
        $_SESSION['signup'] = $userSignupDate;

        // if user was not active
        if ($active != 1)
        {
          // set it back to active and force a partial sync
          $sql = "UPDATE `google_watch` SET `active` = 1, `process` = 1 WHERE `user_id` = $userid LIMIT 1";
          $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
        }
      }
      return $client;
    }
  }

  // no result
  $sql = "UPDATE `user` SET `active` = 0 WHERE `id` = $userid LIMIT 1";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  logthat($userid, "Disabled active for this user, auth problem!");

  return false;
}
