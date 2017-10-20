<?php
/*
echo "argc:\n";
print_r($argc);
echo "\nargv:\n";
print_r($argv);
*/

if ($argv[1] == "unsubscribe_newsletter")
{
	require_once __DIR__.'/process.php';
	unsubscribe_newsletter($argv[2], $argv[3]);
}
