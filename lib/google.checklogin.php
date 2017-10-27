<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/settings.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/google.config.php';
require_once __DIR__.'/google.functions.php';
require_once __DIR__.'/google.auth.php';

session_start();

// login : se connecter avec google -> google auth(with gmail access) -> create user if needed + store token in DB (and refresh_token if first time) + log user and TODO: site stuff in session)
// next access : check session for site token, if not, redirect to login.php => session may end sooner than site cookies (in that case, use site cookies and get token from db OR store token in cookies???)
// backend -> auth with google with db token, refresh if needed
// need to put that in a "plugin" to be able to use different kind of login methods => user table : method=google

// if user session has an access token
if (isset($_SESSION['access_token']) && is_array($_SESSION['access_token']))
{
	// set vars
	$token = $_SESSION['access_token'];
	$refresh = $token['refresh_token'];

	// if empty refresh from token
	if (false && empty($refresh))
	{
		// destroy the session first
		unset($_SESSION['access_token']);
		session_destroy();

		// restart auth with force approval
		logthat("auth", "checklogin: do gAuth('yes') because refresh empty from ".$_SERVER['REMOTE_ADDR']." and page ".$_SERVER['REQUEST_URI']);
		gAuth("yes");
		exit;
	}

	// try to auth
	$client = auth_gmail(0, $_SESSION['access_token'], $refresh);

	// if failed to auth
	if ($client == false)
	{
		// destroy the session first
		unset($_SESSION['access_token']);
		session_destroy();

		// restart auth with force approval
		logthat("auth", "checklogin: do gAuth('yes') because failed to auth from ".$_SERVER['REMOTE_ADDR']." and page ".$_SERVER['REQUEST_URI']);
		gAuth("yes");
		exit;
	}

	// get new token to be sure
	$token = $client->getAccessToken();
	$refresh = $token['refresh_token'];

	// set expiration time and refresh token
	$expire = $token['created'] + $token['expires_in'];

	// update lastseen in database
	$sql = "UPDATE `user` SET `lastseen` = UNIX_TIMESTAMP() WHERE `id` = ".$_SESSION['userid'];
	$res = mysqli_query($conn, $sql) or logthat(0, "SQL Error:\n$sql\n".mysqli_error($conn));

	logthat($_SESSION['userid'], "checklogin: ok user #".$_SESSION['userid']." logged in from ".$_SERVER['REMOTE_ADDR']." and page ".$_SERVER['REQUEST_URI']);
}
// user has no session token
else
{
	// redirect user to google login page to get a new token
	logthat("auth", "checklogin: do gAuth() because no token in session from ".$_SERVER['REMOTE_ADDR']." and page ".$_SERVER['REQUEST_URI']);
	gAuth();
	exit;
}
