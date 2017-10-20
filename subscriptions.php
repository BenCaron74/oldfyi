<?php
ob_start();
require_once __DIR__.'/process.php';

$data = file_get_contents("php://input");
$data = json_decode($data);

$msg = base64url_decode($data->message->data);
//file_put_contents("sub.txt", $msg."\n", FILE_APPEND);
$msg = json_decode($msg);

if (!isset($msg->emailAddress) || empty($msg->emailAddress))
{
	exit;
}

// find user id from email address
$sql = "SELECT u.`id`, u.`active`, w.`active` as `watch`, w.`process` FROM `user` u LEFT JOIN `google_watch` w ON u.`id` = w.`user_id` WHERE u.`email` = '". $msg->emailAddress ."' LIMIT 1";
$res = mysqli_query($conn, $sql) or die("SQL Error");

if (mysqli_num_rows($res) > 0)
{
	$row = mysqli_fetch_assoc($res);

	if ($row['active'] != 1)
	{
		file_put_contents("logs/subscriptions.log", date("d/m/Y H:i:s")." : " . $msg->emailAddress . " - " . $msg->historyId . " (user inactive)\n", FILE_APPEND);
	}
	elseif ($row['watch'] != 1)
	{
		file_put_contents("logs/subscriptions.log", date("d/m/Y H:i:s")." : " . $msg->emailAddress . " - " . $msg->historyId . " (watcher inactive)\n", FILE_APPEND);
	}
	elseif ($row['process'] > 0)
	{
		file_put_contents("logs/subscriptions.log", date("d/m/Y H:i:s")." : " . $msg->emailAddress . " - " . $msg->historyId . " (already processing)\n", FILE_APPEND);
	}
	else
	{
		//$process = process_gmail_box($row['id'], false);

		$sql = "UPDATE `google_watch` SET `process` = 1 WHERE `user_id` = ".$row['id']." AND `active` = 1 AND `process` = 0";
		$res = mysqli_query($conn, $sql) or die("SQL Error");

		file_put_contents("logs/subscriptions.log", date("d/m/Y H:i:s")." : " . $msg->emailAddress . " - " . $msg->historyId . " (added to processlist)\n", FILE_APPEND);

		// something went wrong during process_gmail_box, get the logs for debug:
		/* if ($result != 1)
		{
			file_put_contents("logs/subscriptions.log", ob_get_contents() . "\n", FILE_APPEND);
		} */
	}
}
else
{
	file_put_contents("logs/subscriptions.log", date("d/m/Y H:i:s")." : " . $msg->emailAddress . " - " . $msg->historyId . " (user not found)\n", FILE_APPEND);
}

// no need to say anything: Google Pub/Sub will consider OK if HTTP code 200 received,
// otherwise the message will be sent again after the delay set in the subscription settings,
// so beware to not respond after this delay (10s?) => do long things like full sync in separated batches
