<?php
require_once __DIR__.'/google.checklogin.php';
require_once __DIR__.'/../process.php';

// simple date format
function formatdate($date)
{
  if ((time() - $date) < 3600) { return ceil((time() - $date) / 60)." mn ago"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("today"))) { $h = round((time() - $date) / 3600); return $h." hour". ($h > 1 ? "s" : "") ." ago"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("1 day ago"))) { return "yesterday"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("2 days ago"))) { return "2 days ago"; }
  else { return date("M d", $date); }
}

// mailbox stats and full sync stats
function mailbox_info($id)
{
  global $conn;

  // check full sync status
  $sql = "SELECT `progress`, `date` FROM `google_fullsync` WHERE `user_id` = ".$id." LIMIT 1";
  $res = mysqli_query($conn, $sql);

  if ($res && mysqli_num_rows($res))
  {
    $rowp = mysqli_fetch_assoc($res);
  }
  else
  {
    return false;
  }

  // nb newsletters, nb mails newsleters, nb mails traités
  $sql = "SELECT count(*) AS `msgcount`, count(DISTINCT `newsletter_id`) AS `newscount`
  FROM `message`
  WHERE (`box` = 'INBOX' OR `box` = 'TRASH')
  AND newsletter_id IN
  (SELECT `id` FROM `newsletter`
   WHERE `user_id` = $id
   AND `msgtype` = 2
   AND `category` != 'CATEGORY_PERSONAL')";

  $res = mysqli_query($conn, $sql);
  if ($res)
  {
    $row = mysqli_fetch_assoc($res);

    if (isset($rowp['progress']))
    {
      $row['fullsync'] = $rowp['progress'];
    }

    return $row;
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
elseif ($method != "GET" && $method != "POST" && $method != "DELETE")
{
  output_json($json, 400);
}


// check method
switch ($path[0])
{
  // handle lists
  case "newsletter":

  // handle second path element
  switch ($method)
  {
    // add a new alert
    case "POST":

    // get URL parameters
    $id = mysqli_real_escape_string($conn, $path[1]);
    $do = mysqli_real_escape_string($conn, $path[2]);

    // check input parameters
    if (empty($do) || !in_array($do, array("new", "allow", "block", "unblock", "digest")) || empty($id) || !is_numeric($id))
    {
      output_json($json, 400);
    }

    // check action
    if ($do == "allow")
    {
      $ids = explode(",", $id);
      foreach($ids as $nid)
      {
        // allow each selected newsletter
        $sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 1, UNIX_TIMESTAMP(), 1)";
        $allow = mysqli_query($conn, $sql);

        if ($allow === false)
        {
          output_json($json, 500);
        }
      }
      output_json($json, 200);
    }
    elseif ($do == "digest")
    {
      $ids = explode(",", $id);
      foreach($ids as $nid)
      {
        // digest each selected newsletter
        $sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 2, UNIX_TIMESTAMP(), 1)";
        $digest = mysqli_query($conn, $sql);

        if ($digest === false)
        {
          output_json($json, 500);
        }
      }
      output_json($json, 200);
    }
    elseif ($do == "unblock")
    {
      $ids = explode(",", $id);
      foreach($ids as $nid)
      {
        // unblock each selected newsletter
        $sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 0, UNIX_TIMESTAMP(), 1)";
        $unblock = mysqli_query($conn, $sql);

        if ($unblock === false)
        {
          output_json($json, 500);
        }
      }
      output_json($json, 200);
    }
    elseif ($do == "block")
    {
      $ids = explode(",", $id);
      foreach($ids as $nid)
      {
        // unsubscribe from each selected newsletter
        $sql = "INSERT INTO `queue`(`user_id`, `newsletter_id`, `action`, `date`, `process`) VALUES(".$_SESSION['userid'].", $nid, 3, UNIX_TIMESTAMP(), 1)";
        $unsub = mysqli_query($conn, $sql);

        if ($unsub === false)
        {
          output_json($json, 500);
        }
      }
      output_json($json, 200);
    }
    break;


    // bad method
    default:
    output_json($json, 400);
    break;
  }


  // handle lists
  case "list":

  // handle second path element
  switch ($method)
  {
    // add a new alert
    case "GET":

    // get input parameters
    $list = mysqli_real_escape_string($conn, $path[1]);

    // check input parameters
    if (empty($list) || !in_array($list, array("new", "allowed", "blocked", "digest")))
    {
      output_json($json, 400);
    }

    // sort : column
    if (isset($_GET['sort']))
    {
      $sort = $_GET['sort'];
    }
    else
    {
      $sort = "";
    }

    // sort : ASC/DESC
    if (isset($_GET['sorttype']))
    {
      $sorttype = $_GET['sorttype'];
    }
    else
    {
      $sorttype = "";
    }

    //echo (!empty($sort) ? ucfirst($sort) : "Number");

    // set $sqlsort
    if (!empty($sort))
    {
      if ($sort == "date") { if (empty($sorttype)) { $sorttype = "DESC"; } $sqlsort = "m.`date` ".$sorttype; }
      elseif ($sort == "sender") { if (empty($sorttype)) { $sorttype = "ASC"; } $sqlsort = "TRIM(REPLACE(m.`fromname`, '\'', '')) ".$sorttype; }
      elseif ($sort == "openrate") { if (empty($sorttype)) { $sorttype = "ASC"; } $sqlsort = "`openrate` ".$sorttype; }
      elseif ($sort == "number") { if (empty($sorttype)) { $sorttype = "DESC"; } $sqlsort = "n.`received` ".$sorttype; }
    }
    else { $sqlsort = "n.`received` DESC"; }

    // set $search
    if (isset($_GET['q']) && !empty($_GET['q']))
    {
      $search = 'AND (m.`from` LIKE "%'.str_replace(" ", "%", $_GET['q']).'%"';
      $search .= ' OR m.`fromname` LIKE "%'.str_replace(" ", "%", $_GET['q']).'%"';
      $search .= ' OR m.`subject` LIKE "%'.str_replace(" ", "%", $_GET['q']).'%")';
    }
    else
    {
      $search = "";
    }

    // set pagination
    $pmax = 100;
    if (empty($_GET['p'])) { $p = 1; } else { $p = $_GET['p']; }
    $pmin = ($p - 1) * $pmax;

    // if asked for specific mailbox
    if (isset($_GET['box']) && !empty($_GET['box']))
    {
      $box = "AND m.`box` = '".mysqli_real_escape_string($conn, $_GET['box'])."'";
    }
    else
    {
      $box = "AND (m.`box` = 'INBOX' OR m.`box` = 'TRASH')
      AND n.`msgtype` = 2";
    }

    // start of SQL request
    $sql = "SELECT n.`id`, n.`lastdate`, m.`fromname`, m.`from`, m.`subject`, n.`action`, n.`received`, r.`reads`, ROUND(r.`reads` / n.`received` * 100) as `openrate`
    FROM `newsletter` n, `message` m
    LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
    AS r ON r.`newsletter_id` = m.`newsletter_id`
    WHERE m.`user_id` = ".$_SESSION['userid']."
    AND m.`newsletter_id` = n.`id`";

    if ($list == "new")
    {
      $sql .= "
      AND m.`date` = n.`lastdate`
      AND n.`action` = 0
      $box";
    }
    elseif ($list == "blocked")
    {
      $sql = "
      AND m.`date` = n.`lastdate`
      AND n.`action` = 3
      $search
      GROUP BY m.`newsletter_id`
      ORDER BY $sqlsort";
    }
    elseif ($list == "allowed")
    {
      $sql = "
      AND m.`date` = n.`lastdate`
      AND n.`action` = 1
      $search
      GROUP BY m.`newsletter_id`
      ORDER BY $sqlsort";
    }
    elseif ($list == "digest")
    {
      $sql = "
      AND m.`date` = n.`lastdate`
      AND n.`action` = 2
      $search
      GROUP BY m.`newsletter_id`
      ORDER BY $sqlsort";
    }

    // end of SQL request
    $sql .= "
      $search
      GROUP BY m.`newsletter_id`
      ORDER BY $sqlsort";

    // get total results
    $res = mysqli_query($conn, $sql) or die(mysqli_error($conn));
    $rescount = mysqli_num_rows($res);

    // get this page results
    $res = mysqli_query($conn, $sql." LIMIT $pmin, $pmax") or die(mysqli_error($conn));
    if ($res) { $count = mysqli_num_rows($res); } else { $count = 0; }

    if ($count < 1)
    {
      $json['count'] = 0;
      output_json($json, 404);
    }
    else
    {
      $json['count'] = $count;

      while ($row = mysqli_fetch_assoc($res))
      {
        if ($row['received'] > 1)
        {
          if ($row['received'] <= 5) { $badgecolor = "success"; }
          elseif ($row['received'] <= 10) { $badgecolor = "primary"; }
          elseif ($row['received'] <= 20) { $badgecolor = "warning"; }
          else { $badgecolor = "danger"; }
        }
        else
        {
          $badgecolor = '';
        }

        if ($row['openrate'] >= 75) { $trustcolor = "success"; }
        elseif ($row['openrate'] >= 50) { $trustcolor = "primary"; }
        elseif ($row['openrate'] >= 25) { $trustcolor = "warning"; }
        else { $trustcolor = "danger"; }

        // sender image (first letter or default image)
        // TODO: get google+ profile image if any
        $img = "/global/portraits/default.png?v=4";
        if (!empty($row['fromname']))
        {
          $letter = preg_replace("/^[^a-z0-9]*([a-z0-9]).*$/i", '$1', $row['fromname']);
          if (!empty($letter))
          {
            $img = "/img/letters/".strtolower($letter).".png";

          }
        }

        // prepare row
        $row['img'] = $img;
        $row['lastdate_formatted'] = formatdate($row['lastdate']);
        $row['number_color'] = $badgecolor;
        $row['openrate_color'] = $trustcolor;

        // set row (need : row:id row:fromname row:subject row:openrate)
        $json['results'][] = $row;
      }

      // add mailbox stats
      $json['stats'] = mailbox_info($_SESSION['userid']);

      // send result
      output_json($json, 200);
    }
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
