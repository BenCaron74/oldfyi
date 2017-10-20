#!/bin/bash

cd /var/www/sites/freeyourinbox.com/public_html

testpid=$(ps ux | grep php | grep worker)
if [ "$testpid" == "" ]; then
	/usr/bin/php -f worker.php >> logs/worker.log 2>&1 &
fi

testpid=$(ps ux | grep php | grep queue)
if [ "$testpid" == "" ]; then
	/usr/bin/php -f queue.php >> logs/queue.log 2>&1 &
fi

testpid=$(ps ux | grep php | grep fullsync)
if [ "$testpid" == "" ]; then
	/usr/bin/php -f fullsync.php >> logs/fullsync.log 2>&1 &
fi

testpid=$(ps ux | grep php | grep watcher)
if [ "$testpid" == "" ]; then
	/usr/bin/php -f watcher.php >> logs/watcher.log 2>&1 &
fi
