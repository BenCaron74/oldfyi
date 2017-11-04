<?php
$cfgGoogle['appName'] = 'FreeYourInbox';
$cfgGoogle['secret'] = '../tmp/.credentials/client_secret_dev.json';
$cfgGoogle['scopes'] = array('https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.profile', 'profile');
/*
array(	'https://www.googleapis.com/auth/gmail.readonly',
				'https://www.googleapis.com/auth/gmail.labels',
				'https://www.googleapis.com/auth/gmail.modify',
				'https://www.googleapis.com/auth/gmail.settings.basic');
*/
//Google_Service_Gmail::GMAIL_READONLY
$cfgGoogle['accessType'] = 'offline';
$cfgGoogle['approval'] = 'auto';
$cfgGoogle['topic'] = 'projects/sturdy-block-141512/topics/fyi';
