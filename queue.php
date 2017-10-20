<?php
if (php_sapi_name() !== 'cli') { exit; } // FATAL: this should be launched by crontab only!

require_once __DIR__.'/process.php';

echo "\n".date("Y-m-d H:i:s")." : script queue.php launched\n";

while (date("s") <= 59)
{
	echo "\n".date("Y-m-d H:i:s")." : checking for actions to process...\n";

	// find partial syncs to process
	$sql = "SELECT * FROM `queue` WHERE `process` = 1";
	$res = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

	// nothing to process
	if (!mysqli_num_rows($res))
	{
		if (date("s") >= 59)
		{
			break;
		}

		sleep(1);
		continue;
	}

	while ($row = mysqli_fetch_assoc($res))
	{
		$id = $row['id'];
		$userid = $row['user_id'];
		$nid = $row['newsletter_id'];
		$action = $row['action'];

		echo "\n".date("Y-m-d H:i:s")." : processing user: #".$userid." - id: #".$id."\n";

		// check again to avoid multiple full sync at the same time
		$sqlc = "SELECT `process` FROM `queue` WHERE `id` = $id AND `process` = 1";
		$resc = mysqli_query($conn, $sqlc) or die("SQL Error:\n$sqlc\n".mysqli_error($conn));

		// go to next user if full sync is already in progress
		if (!mysqli_num_rows($resc)) {
			echo date("Y-m-d H:i:s")." : action already in progress from another instance!\n";
			continue;
		}

		// set as "in progress" immediately after checking
		$sqlu = "UPDATE `queue` SET `process` = 2 WHERE `id` = $id";
		$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

		// process action
		if ($action == 1) {
			$status = allow_newsletter($userid, $nid);
		} elseif ($action == 2) {
			$status = digest_newsletter($userid, $nid);
		} elseif ($action == 3) {
			$status = unsubscribe_newsletter($userid, $nid);
		} elseif ($action == 0) {
			$status = unblock_newsletter($userid, $nid);
		} else {
			$status = false;
		}

		if ($status !== false)
		{
			// OK, delete from queue
			$sqlu = "DELETE FROM `queue` WHERE `id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			echo "\n\n".date("Y-m-d H:i:s")." : finished processing id: #".$id."\n";
		}
		else
		{
			// KO, set process to 3 (error)
			$sqlu = "UPDATE `queue` SET `process` = 3 WHERE `id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			echo "\n\n".date("Y-m-d H:i:s")." : error while processing id: #".$id."\n";
		}
	}
}

/*
 process:
 - 0 = nothing todo/doing
 - 1 = partial sync todo
 - 2 = partial sync ongoing
 - 3 = error
*/

echo "\n".date("Y-m-d H:i:s")." : script queue.php has ended\n";
