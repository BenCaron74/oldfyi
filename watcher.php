<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/lib/settings.php';
require_once __DIR__.'/lib/db.php';
require_once __DIR__.'/lib/google.auth.php';
require_once __DIR__.'/lib/google.config.php';
require_once __DIR__.'/lib/google.functions.php';


// select all gmail accounts expired in db and run set_gmail_watcher($email, false) on each
function check_gmail_watchers()
{
  global $conn;

  // check expiration date in db
  $sql = "SELECT u.*, w.`expiration` FROM `user` u LEFT JOIN `google_watch` w ON u.`id` = w.`user_id` WHERE u.`active` = 1 AND w.`expiration` < ".time();
  $res = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

  if (mysqli_num_rows($res) > 0)
  {
    while ($row = mysqli_fetch_assoc($res))
    {
      // set gmail watcher without checking
      set_gmail_watcher($row['id'], false);
    }
  }
}


// setup gmail watcher
function set_gmail_watcher($userid, $check = true)
{
  global $conn, $cfgGoogle;

  if ($check)
  {
    // check expiration date in db
    $sql = "SELECT * FROM `google_watch` WHERE `user_id` = $userid LIMIT 1";
    $res = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

    if (mysqli_num_rows($res) > 0)
    {
      $row = mysqli_fetch_assoc($res);
      // if not expired yet
      if ($row['expiration'] > time() - 60) {
        // exit
        return true;
      }
    }
  }

  // try to auth user mailbox
  $client = auth_gmail($userid);

  // check if auth
  if ($client == false) {
    echo "\n".date("d/m/Y H:i:s")." : failed to auth to user mailbox! (userid=$userid)\n";
    return false;
  }

  // try to instantiate a new Gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    echo "\n".date("d/m/Y H:i:s")." : exception while setting gmail service:\n",  $e->getMessage();
    return false;
  }

  // try to instantiate a new WatchRequest
  try {
    $wreq = new Google_Service_Gmail_WatchRequest();
  } catch (Exception $e) {
    echo "\n".date("d/m/Y H:i:s")." : exception while setting gmail WatchRequest:\n",  $e->getMessage();
    return false;
  }

  // set google cloud pub/sub topic name
  $wreq->setTopicName($cfgGoogle['topic']);
  // set watcher
  $watch = $service->users->watch("me", $wreq);

  // if got expiration time
  if (isset($watch->expiration))
  {
    //$wcurhis = $watch->historyId;
    $wexpire = round($watch->expiration / 1000); // in msecs
    echo "\n".date("d/m/Y H:i:s")." : watch set or renewed for user #".$userid.", expires in: $wexpire\n";

    // insert or update watcher expiration time in db
    $sqli = "INSERT INTO `google_watch`(`user_id`, `expiration`, `since`, `active`, `process`) VALUES($userid, $wexpire, UNIX_TIMESTAMP(), 1, 0)";
    $sqlu = "UPDATE `google_watch` SET `expiration` = $wexpire WHERE `user_id` = $userid";

    // execute insert query
    $res = mysqli_query($conn, $sqli);
    if ($res === false) {
	// if user_id already exists, execute update query
	$res = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));
    }

    // check result
    if ($res !== false) {
	return true;
    } else {
	return false;
    }
  }

  // we should have returned already if it was OK
  echo "\n".date("d/m/Y H:i:s")." : something went wrong!\n";
  return false;
}


// if in cli mode and watcher.php is called, launch watcher checking
if (php_sapi_name() == 'cli' && $argv[0] == "watcher.php")
{
  echo "\n".date("d/m/Y H:i:s")." : checking watchers...\n";
  check_gmail_watchers();
}
