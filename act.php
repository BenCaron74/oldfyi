<?php 
require_once __DIR__.'/lib/google.checklogin.php';
require_once __DIR__.'/process.php';

// check referer to avoid XSS
if (empty($_SERVER['HTTP_REFERER']) || !strstr($_SERVER['HTTP_REFERER'], $siteurl))
{
	http_response_code(403);
	exit;
}

// check if requested via AJAX
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
{
	http_response_code(403);
	exit;
}

// get vars
$do = $_GET['do'];
$id = $_GET['id'];

// check action
if ($do == "allow")
{
	$ids = explode(",", $id);
	foreach($ids as $nid)
	{
		$sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 1, UNIX_TIMESTAMP(), 1)";
		$allow = mysqli_query($conn, $sql);
/*
		// allow each selected newsletter
		$allow = allow_newsletter($_SESSION['userid'], $nid);
*/
		if ($allow === false)
		{
			http_response_code(500);
			exit;
		}
	}

	http_response_code(200);
	exit;
}
elseif ($do == "digest")
{
	$ids = explode(",", $id);
	foreach($ids as $nid)
	{
		$sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 2, UNIX_TIMESTAMP(), 1)";
		$digest = mysqli_query($conn, $sql);
/*
		// digest each selected newsletter
		$digest = digest_newsletter($_SESSION['userid'], $nid);
*/
		if ($digest === false)
		{
			http_response_code(500);
			exit;
		}
	}

	http_response_code(200);
	exit;
}
elseif ($do == "unblock")
{
	$ids = explode(",", $id);
	foreach($ids as $nid)
	{
		$sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 0, UNIX_TIMESTAMP(), 1)";
		$unblock = mysqli_query($conn, $sql);
/*
		// unblock each selected newsletter
		$unblock = unblock_newsletter($_SESSION['userid'], $nid);
*/
		if ($unblock === false)
		{
			http_response_code(500);
			exit;
		}
	}

	http_response_code(200);
	exit;
}
elseif ($do == "filter")
{
	$ids = explode(",", $id);
	foreach($ids as $nid)
	{
		$sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 3, UNIX_TIMESTAMP(), 1)";
		$unsub = mysqli_query($conn, $sql);
/*
		// unsubscribe from each selected newsletter
		$unsub = unsubscribe_newsletter($_SESSION['userid'], $nid);
*/
		if ($unsub === false)
		{
			http_response_code(500);
			exit;
		}
	}

	http_response_code(200);
	exit;
}
elseif ($do == "fullsync")
{
	// TODO: check last full sync date and status before replace!!
	$sqlu = "REPLACE INTO `google_fullsync`(`user_id`, `date`, `progress`) VALUES($id, UNIX_TIMESTAMP(), 0)";
	$resu = mysqli_query($conn, $sqlu);

	if ($resu)
	{
		http_response_code(200);
		exit;
	}
	else
	{
		http_response_code(500);
		exit;
	}
}
elseif ($do == "checkstatus")
{
	// check full sync status
	//$sql = "SELECT `progress`, `date` FROM `google_fullsync` WHERE `user_id` = ".$_SESSION['userid']." LIMIT 1";

	$sql = "SELECT count(m.`id`) AS `msgcount`, count(DISTINCT m.`newsletter_id`) AS `newscount`, f.`progress`, f.`date`
	FROM `message` m, `google_fullsync` f
	WHERE f.`user_id` = ".$_SESSION['userid']."
	AND (m.`box` = 'INBOX' OR m.`box` = 'TRASH')
	AND m.newsletter_id IN
	(SELECT `id` FROM `newsletter`
	 WHERE `user_id` = ".$_SESSION['userid']."
	 AND `msgtype` = 2
	 AND `category` != 'CATEGORY_PERSONAL')";

	$res = mysqli_query($conn, $sql);

	if ($res && mysqli_num_rows($res))
	{
	  $row = mysqli_fetch_assoc($res);

	  header('Content-Type: application/json');
	  echo json_encode($row);
	  exit;
	}

	http_response_code(500);
	exit;
}
else
{
	http_response_code(400);
	exit;
}
