<?php
if (php_sapi_name() !== 'cli') { exit; } // FATAL: this should be launched by crontab only!

require_once __DIR__.'/process.php';

echo "\n".date("Y-m-d H:i:s")." : script fullsync.php launched\n";

while (date("s") <= 45)
{
	echo "\n".date("Y-m-d H:i:s")." : checking for new full syncs to process...\n";

	// find full syncs to process
	$sql = "SELECT * FROM `google_fullsync` WHERE `progress` = 0";
	$res = mysqli_query($conn, $sql) or die("SQL Error:\n$sql\n".mysqli_error($conn));

	// nothing to process
	if (!mysqli_num_rows($res))
	{
		sleep(10);
		continue;
	}

	while ($row = mysqli_fetch_assoc($res))
	{
		$id = $row['user_id'];
		echo "\n".date("Y-m-d H:i:s")." : processing user: #".$id."\n";

		// check again to avoid multiple full sync at the same time
		$sqlc = "SELECT `progress` FROM `google_fullsync` WHERE `user_id` = $id AND `progress` = 0";
		$resc = mysqli_query($conn, $sqlc) or die("SQL Error:\n$sqlc\n".mysqli_error($conn));

		// go to next user if full sync is already in progress
		if (!mysqli_num_rows($resc)) {
			echo date("Y-m-d H:i:s")." : full sync already in progress from another script!\n";
			continue;
		}

		// set as "in progress" immediately after checking
		$sqlu = "UPDATE `google_fullsync` SET `progress` = 1 WHERE `user_id` = $id";
		$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

		// remove all e-mails
		$sqlds = array();
		$sqlds[] = "DELETE FROM `google_history` WHERE `user_id` = $id";
		$sqlds[] = "DELETE FROM `newsletter` WHERE `user_id` = $id";
		$sqlds[] = "DELETE FROM `message` WHERE `user_id` = $id";
		$sqlds[] = "DELETE FROM `google_message` WHERE `message_id` NOT IN (SELECT `id` FROM `message`)";
		$sqlds[] = "DELETE FROM `messagekeyword` WHERE `message_id` NOT IN (SELECT `id` FROM `message`)";
		$sqlds[] = "DELETE FROM `keyword` WHERE `id` NOT IN (SELECT `keywordid` FROM `messagekeyword`)";

		// execute each delete query
		foreach ($sqlds as $sqld)
		{
			$resd = mysqli_query($conn, $sqld) or die("SQL Error:\n$sqld\n".mysqli_error($conn));
		}

		// process full sync on mailbox
		if (process_gmail_box($id, true))
		{
			// OK, set progress to 100
			$sqlu = "UPDATE `google_fullsync` SET `progress` = 100 WHERE `user_id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			// activate google_watch
			$sqlu = "UPDATE `google_watch` SET `active` = 1, `process` = 0 WHERE `user_id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			echo "\n\n".date("Y-m-d H:i:s")." : finished processing user: #".$id."\n";
		}
		else
		{
			// KO, set progress to -1 (error)
			$sqlu = "UPDATE `google_fullsync` SET `progress` = -1 WHERE `user_id` = $id";
			$resu = mysqli_query($conn, $sqlu) or die("SQL Error:\n$sqlu\n".mysqli_error($conn));

			echo "\n\n".date("Y-m-d H:i:s")." : error while processing user: #".$id."\n";
		}
	}
}

echo "\n".date("Y-m-d H:i:s")." : script fullsync.php has ended\n";
