<?php
// decode mails
function base64url_decode($data) {
    return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
}

// encode mails
function base64url_encode($data) {
    return str_replace(array('+', '/'), array('-', '_'), base64_encode($data));
}

// if user created without gmail API
function gmailCleanAddress($email) {
    // lowercase email address without dots and before any + sign and finished by @gmail.com
    return strtolower(preg_replace_callback('/^([^+]+)(\+.*)?@(.*)$/', function ($matches) { return str_replace('.', '', $matches[1])."@gmail.com"; }, $email));
}

// redirect to auth page
function gAuth($force = "no") {
  $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/callback-google.php?force='.$force;
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

function logthat($userid, $msg)
{
  file_put_contents("/var/www/sites/freeyourinbox.com/public_html/logs/process.".$userid.".log", date("d/m/Y H:i:s")." : " . $msg . "\n", FILE_APPEND);
}

function sanitize($str)
{
  global $conn;

  return mysqli_real_escape_string($conn, $str);
}

