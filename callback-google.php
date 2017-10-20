<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/lib/google.config.php';

session_start();

// set up google oAuth2
$client = new Google_Client();
$client->setAuthConfigFile($cfgGoogle['secret']);
$client->setRedirectUri('https://' . $_SERVER['HTTP_HOST'] . '/callback-google.php');
$client->addScope($cfgGoogle['scopes']);
$client->setAccessType($cfgGoogle['accessType']);

// force approval if requested to do so
if ($_GET['force'] == "yes") {
 $client->setApprovalPrompt('force');
} else {
 $client->setApprovalPrompt($cfgGoogle['approval']);
}

if (!isset($_GET['code']))
{
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}
else
{
  // authenticate from GET code=
  $client->authenticate($_GET['code']);

  // set access token
  $token = $client->getAccessToken();

  // check if token is valid
  $payload = $client->verifyIdToken($token['id_token']);
  if ($payload)
  {
    //$userid = $payload['sub'];
    $_SESSION['access_token'] = $token;

    // redirect to home
    $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/newsletters';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  } else {
    echo "invalid token!";
    exit;
  }
}
