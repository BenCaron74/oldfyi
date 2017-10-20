<?php
if (php_sapi_name() !== 'cli') { exit; } // FATAL: this should be launched by crontab only!

require_once __DIR__.'/process.php';

echo "\n".date("Y-m-d H:i:s")." : script worker.php launched\n";

while (date("s") <= 57)
{
	echo "\n".date("Y-m-d H:i:s")." : checking for new partial syncs to process...\n";

	// find partial syncs to process
	$sql = "SELECT * FROM `google_watch` WHERE `active` = 1 AND `process` = 1";
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
		$id = $row['user_id'];
		echo "\n".date("Y-m-d H:i:s")." : processing user: #".$id."\n";

		// check again to avoid multiple full sync at the same time
		$sqlc = "SELECT `process` FROM `google_watch` WHERE `user_id` = $id AND `process` = 1";
		$resc = mysqli_query($conn, $sqlc) or die("SQL Error:\n$sqlc\n".mysqli_error($conn));

		// go to next user if full sync is already in progress
		if (!mysqli_num_rows($resc)) {
			echo date("Y-m-d H:i:s")." : partial sync already in progress from another script!\n";
			continue;
		}

		// set as "in progress" immediately after checking
		$sqlu = "UPDATE `google_watch` SET `process` = 2 WHERE `user_id` = $id";
		$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

		// process partial sync on mailbox
		if (process_gmail_box($id, false))
		{
			// OK, set process to 0
			$sqlu = "UPDATE `google_watch` SET `active` = 1, `process` = 0 WHERE `user_id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			echo date("Y-m-d H:i:s")." : finished processing user: #".$id."\n";
		}
		else
		{
			// KO, set process to 3 (error) and active to 3 (error)
			$sqlu = "UPDATE `google_watch` SET `active` = 3, `process` = 3 WHERE `user_id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));
			logthat($id, "Error while processing user!");

			echo date("Y-m-d H:i:s")." : error while processing user: #".$id."\n";
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

echo "\n".date("Y-m-d H:i:s")." : script worker.php has ended\n";
