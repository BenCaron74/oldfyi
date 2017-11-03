<?php
require(__DIR__.'/settings.php');
require(__DIR__.'/db.php');
require(__DIR__.'/google.functions.php');


// get clean address and check if gmail or gsuite account
function wl_clean_address($email)
{
  // get the host part of the email
  $host = substr($email, strrpos($email, '@') + 1);

  // if gmail account
  if ($host == "gmail.com")
  {
    // remove everything after + and remove dots in email, then check email format validity
    $email = filter_var(gmailCleanAddress($email), FILTER_VALIDATE_EMAIL);

    // if email invalid, $email equals to false
    return $email;
  }
  // or check if gsuite account
  else
  {
    // host's MX DNS records should be handled by google
    $dns = dns_get_record($host, DNS_MX);

    // for each MX record
    foreach ($dns as $value)
    {
      // check if MX is handled by google
      if (isset($value['target']) && stristr($value['target'], ".google.com"))
      {
        // yep
        return $email;
      }
    }
  }

  // email is not handled by google
  return false;
}

// get max position
function wl_max_position()
{
  global $conn;

  // get current and max position
  $sql = "SELECT MAX(`position`) AS `total` FROM `waitinglist` LIMIT 1";
  $res = mysqli_query($conn, $sql);

  // check result
  if ($res)
  {
    // set row
    $row = mysqli_fetch_assoc($res);
    if ($row['total'] == NULL) { $row['total'] = 0; }

    return $row['total'];
  }
  else
  {
    // bad query or no result
    return false;
  }
}

// get current position
function wl_get_position($referrer)
{
  global $conn;

  // escape string
  $referrer = mysqli_real_escape_string($conn, $referrer);

  // get current and max position
  $sql = "SELECT `position` FROM `waitinglist` WHERE `code` = '$referrer' LIMIT 1";
  $res = mysqli_query($conn, $sql);

  // check result
  if ($res)
  {
    // set row
    $row = mysqli_fetch_assoc($res);

    // return false if no position
    if ($row['position'] == NULL) { return false; }

    // get max position
    $row['total'] = wl_max_position();

    return $row;
  }
  else
  {
    // bad query or no result
    return false;
  }
}


// add user at the end of the list
function wl_position_add($code)
{
  global $conn;

  // escape string
  $code = mysqli_real_escape_string($conn, $code);

  // get max position
  $newpos = wl_max_position() + 1;

  // update position of everyone at or after his new position
  $sql = "UPDATE `waitinglist` SET `position` = $newpos WHERE `code` = '$code' LIMIT 1";
  $res = mysqli_query($conn, $sql);

  if ($res)
  {
    return $newpos;
  }

  return false;
}


/* up position in the waiting list
 parameters:
 - referrer : referrer's code
 - amount : how much positions to up
*/
function wl_position_up($referrer, $amount = 1)
{
  global $conn;

  // escape string
  $referrer = mysqli_real_escape_string($conn, $referrer);

  // get current and max position
  $refpos = wl_get_position($referrer);

  // return false if user has no position
  if ($refpos === false) { return false; }

  // referrer's current position
  $curpos = $refpos['position'];

  // cannot be less than 1st
  if ($curpos <= 1) { return $curpos; }

  // referrer's future position
  $newpos = $refpos['position'] - $amount;

  // update position of everyone at or after his new position
  $sql = "UPDATE `waitinglist` SET `position` = `position` + 1 WHERE `position` >= $newpos AND `position` < $curpos";
  $res = mysqli_query($conn, $sql);

  if ($res)
  {
    // update referrer's position
    $sql = "UPDATE `waitinglist` SET `position` = $newpos WHERE `code` = '$referrer' LIMIT 1";
    $res = mysqli_query($conn, $sql);
  }

  if ($res)
  {
    // if succeded, return new and max position
    $refpos['position'] = $newpos;
    return $refpos;
  }

  // or else return false
  return false;
}


// check status
function wl_status($email)
{
  global $conn, $json;

  // safe var
  $email = mysqli_real_escape_string($conn, $email);

  // check if already in waitinglist
  $sql = "SELECT `validation`, `position` FROM `waitinglist` WHERE `email` = '$email' LIMIT 1";
  $res = mysqli_query($conn, $sql);

  if ($res && mysqli_num_rows($res))
  {
    $row = mysqli_fetch_assoc($res);

    // send validation status
    if (!empty($row['validation']))
    {
      $json['validated'] = false;
    }
    else
    {
      // and position status if validated
      $json['validated'] = true;
      $json['position'] = $row['position'];
      $json['total'] = wl_max_position();
    }

    output_json($json, 200);
  }

  return false;
}


// register new early access
function wl_register($to, $lang = "en", $referrer = "")
{
  global $conn, $json;

  // check input parameters
  if ($to == false || empty($to))
  {
    output_json($json, 400);
  }

  // check email address
  $to = wl_clean_address($to);

  // address not handled by google
  if (!$to)
  {
    // bye
    output_json($json, 403);
  }

  // check if user email in waiting-list
  wl_status($to);

  // generate validation code
  $validation = md5("fyi-validate_" . time() . "+" . rand(0,100));

  // if mail sent, add email to database
  if (wl_send_code($to, $validation, $lang))
  {
    // db vars
    $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
    $email = mysqli_real_escape_string($conn, $to);
    $emailmd5 = md5($to);
    $lang = mysqli_real_escape_string($conn, $lang);
    $referrer = mysqli_real_escape_string($conn, $referrer);

    // init vars
    $i = 1;
    $res = false;

    // retry if code is not unique
    while ($res == false && $i <= 3)
    {
      // generate invitation code
      $code = strtoupper(substr(md5("fyi-referral_" . time() . "+" . $to . rand(0,100)), -6, 6));

      // add email to waiting list
      $sql = "INSERT INTO `waitinglist`(`date`, `ip`, `email`, `email_md5`, `validation`, `lastsend`, `lang`, `code`, `referrer`) VALUES(UNIX_TIMESTAMP(), '$ip', '$email', '$emailmd5', '$validation', 0, '$lang', '$code', '$referrer')";
      $res = mysqli_query($conn, $sql);

      $i++;
    }

    // if email has been added to database
    if ($res)
    {
      $json['registered'] = true;

      // output
      output_json($json, 200);
    }

    output_json($json, 500);
  }

  output_json($json, 404);
}


// send validation code
function wl_send_code($to, $validation = "", $lang = "")
{
  global $conn, $json;

  // if asked to re-send code
  if (empty($validation))
  {
    $email = mysqli_real_escape_string($conn, $to);
    $sql = "SELECT `validation`, `lastsend`, `lang` FROM `waitinglist` WHERE `email` = '$email' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res))
    {
      $row = mysqli_fetch_assoc($res);

      // only allow one re-send every
      if ($row['lastsend'] > time() - 3600)
      {
        return false;
      }

      // set date to now
      $sqlu = "UPDATE `waitinglist` SET `lastsend` = UNIX_TIMESTAMP() WHERE `email` = '$email' LIMIT 1";
      $resu = mysqli_query($conn, $sqlu);

      $validation = $row['validation'];
      $lang = $row['lang'];
    }
    else
    {
      return false;
    }
  }

  // prepare message
  if (preg_match('/^fr$|^fr[_-]/i', $lang))
  {
    $subject = "Validation early access";
    $message = "Merci d'ouvrir l'adresse suivante avec votre navigateur pour valider votre inscription early access à Free Your Inbox :
https://freeyourinbox.com/early?email=".$to."&validate=".$validation."

Merci !";
  }
  else
  {
    $subject = "Validate your subscription to FreeYourInbox early access";
    $message = "Please open the following address in your browser to validate your early access subscription to Free Your Inbox :
https://freeyourinbox.com/early?email=".$to."&validate=".$validation."

Thanks!";
  }

  $headers = 'From: FreeYourInbox <no-reply@freeyourinbox.com>';

  // send email
  $to = "alexb38@gmail.com";
  if (mail($to, $subject, $message, $headers))
  {
    return true;
  }

  return false;
}


// validate address
function wl_validate($email, $validation)
{
  global $conn, $json;

echo $email." - ".$validation."\n";

  // get validation, code and referrer
  $sql = "SELECT `validation`, `code`, `referrer` FROM `waitinglist` WHERE `email` = '$email' LIMIT 1";
  $res = mysqli_query($conn, $sql);

  // check result
  if ($res && mysqli_num_rows($res))
  {
    $row = mysqli_fetch_assoc($res);
  }
  else
  {
    return false;
  }

  // check validation
  if ($row['validation'] == $validation)
  {
    // set account as validated
    $sql = "UPDATE `waitinglist` SET `validation` = '' WHERE `email` = '$email' AND `validation` = '$validation' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    // add user to position's table
    wl_position_add($row['code']);

    // check if has a referrer
    if (!empty($row['referrer']))
    {
      // up referrer's position
      wl_position_up($row['referrer']);
    }

    return true;
  }

  return false;
}


// function to encode safely as JSON
function safe_json_encode($value)
{
    if (false && version_compare(PHP_VERSION, '5.4.0') >= 0) {
        $encoded = json_encode($value, JSON_PRETTY_PRINT);
    } else {
        $encoded = json_encode($value);
    }
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return $encoded;
        case JSON_ERROR_DEPTH:
            return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_STATE_MISMATCH:
            return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_CTRL_CHAR:
            return 'Unexpected control character found';
        case JSON_ERROR_SYNTAX:
            return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_UTF8:
            $clean = utf8ize($value);
            return safe_json_encode($clean);
        default:
            return 'Unknown error'; // or trigger_error() or throw new Exception()

    }
}


// convert array or string from UTF8 to safe UTF8
function utf8ize($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string ($mixed)) {
  return iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $mixed);
    }
    return $mixed;
}


// function to output result as json and quit
function output_json($json, $code=0)
{
  global $conn;

  if ($code > 0)
  {
    http_response_code($code);
  }

  // output json
  header('Content-Type: application/json');
  //echo json_encode($json) or die("JSON error: " . json_last_error_msg() . "\n");
  echo safe_json_encode($json);

  // cleanup
  mysqli_close($conn);
  exit;
}


// START

/*
// check referer to avoid XSS
if (empty($_SERVER['HTTP_REFERER']) || !strstr($_SERVER['HTTP_REFERER'], $siteurl))
{
  http_response_code(403);
  exit;
}

// check if requested via AJAX
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
{
  http_response_code(403);
  exit;
}
*/

// default response
$json = array();

// get path and method
$path = explode("/", $_GET['path']);
$method = $_SERVER['REQUEST_METHOD'];

// check URL
if (substr($_SERVER['REQUEST_URI'], 0, 5) != "/api/")
{
  output_json($json, 400);
}


// check method and get params
if ($method == "PUT")
{
  $input = file_get_contents("php://input");
  $input = json_decode($input);
}
elseif (!in_array($method, array("GET", "POST", "DELETE")))
{
  output_json($json, 400);
}


// check second path element
switch ($path[1])
{
  // handle early registering
  case "register":

    // handle method
    switch ($method)
    {
      // new early access request
      case "PUT":

        // get input parameters
        $to = filter_var($input->email, FILTER_VALIDATE_EMAIL);
        if (preg_match("/^[A-F0-9]{6}$/", $input->referrer)) { $referrer = $input->referrer; } else { $referrer = ""; }
        $lang = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        // register email
        wl_register($to, $lang, $referrer);

        // default response
        output_json($json, 500);
        break;


      // get status
      case "GET":

        // get input parameter
        $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);

        // register email
        wl_status($email);

        // default response (email not found)
        output_json($json, 404);
        break;


      // bad method
      default:
        output_json($json, 400);
        break;
    }


  // handle email validation
  case "validate":

    // handle method
    switch ($method)
    {
      // validate account + up position of referrer if any
      case "POST":

        // get input parameters
        $email = wl_clean_address($_POST['email']);
        if (preg_match("/^[a-f0-9]+$/", $_POST['code'])) { $code = $_POST['code']; } else { output_json($json, 403); }

        if (wl_validate($email, $code) == true)
        {
          output_json($json, 200);
        }

        // default response
        output_json($json, 500);
        break;


      // re-send validation code
      case "GET":

        // get input parameter
        $email = wl_clean_address($_GET['email']);

        if (wl_send_code($email) == true)
        {
          output_json($json, 200);
        }

        // default response
        output_json($json, 500);
        break;


      // bad method
      default:
        output_json($json, 400);
        break;
    }


  // bad method
  default:
    output_json($json, 400);
    break;
}

// TODO: delete unvalidated accounts after X days
