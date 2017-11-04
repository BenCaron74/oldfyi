<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/lib/settings.php';
require_once __DIR__.'/lib/db.php';
require_once __DIR__.'/lib/google.config.php';
require_once __DIR__.'/lib/google.functions.php';
require_once __DIR__.'/lib/google.auth.php';
require_once __DIR__.'/watcher.php';

function process_gmail_box($userid, $full = false)
{
   global $conn;

   // try to connect to user mailbox
   $client = auth_gmail($userid);
   if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    // TODO: send an e-mail to alert the user that he should come back on the site?
    return false;
   } else {
    logthat($userid, "Function process_gmail_box($userid, $full) called!");
   }

   // try to set gmail service
   try {
    $service = new Google_Service_Gmail($client);
   } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
   }

   // for partial synchro
   if ($full == false)
   {
    // get last historyId from db
    $sqlh = "SELECT * FROM `google_history` WHERE `user_id` = ".$userid;
    $resh = mysqli_query($conn, $sqlh) or logthat($userid, "SQL Error:\n$sqlh\n".mysqli_error($conn));

    if ($resh && mysqli_num_rows($resh) > 0)
    {
     // ok found historyId in db
     $rowh = mysqli_fetch_assoc($resh);
     $historyid = $rowh['historyId'];
     $hid = 0;
     logthat($userid, "Last historyId: $historyid");

     // check historyid value
     if (empty($historyid) || $historyid < 1) {
      logthat($userid, "Bad last historyId in db!");
      return false;
     }

     $pageToken = 0;
     while(true)
     {
      if ($pageToken == 0) {
       // get first page
       logthat($userid, "Get first page of user history...");
       $reqargs = array('startHistoryId' => $historyid, 'maxResults' => 500);
      } else {
       // if a pageToken is set, get that page
       logthat($userid, "Get next page of user history...");
       $reqargs = array('startHistoryId' => $historyid, 'pageToken' => $pageToken, 'maxResults' => 500);
      }

      // call Gmail API
      try {
        $results = $service->users_history->listUsersHistory("me", $reqargs);
        logthat($userid, "OK got user history...");
      } catch (Exception $e) {
        logthat($userid, "Exception while fetching user history: " . $e->getMessage() );
        logthat($userid, "Will try a full sync...");

        // ask cron script to do a fullsync
      	$sql = "UPDATE `google_fullsync` SET `progress` = 0 WHERE `user_id` = $userid";
      	$res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sqlh\n".mysqli_error($conn));
      	return false;
      }

      // get results
      $history = $results->history;
      logthat($userid, "Number of results: ".count($history));

      // for each history item, check message or action
      $received = array();
      foreach($history as $item)
      {
        $actions = array();
        $hid = $item->id;
        logthat($userid, "Handling historyId: $hid");

        if (isset($item->labelsRemoved))
        {
          foreach ($item->labelsRemoved as $action)
          {
            $mid = $action->message->id;

            // update existing message read status
            foreach ($action->labelIds as $label)
            {
              if ($label == "UNREAD") { $actions[$mid]['read'] = 1; }
              elseif ($label == "INBOX") { logthat($userid, "Message $mid removed from INBOX ($hid)"); }
              elseif ($label == "SENT") { logthat($userid, "Message $mid removed from SENT ($hid)"); }
              elseif ($label == "SPAM") { logthat($userid, "Message $mid removed from SPAM ($hid)"); }
              elseif ($label == "TRASH") { logthat($userid, "Message $mid removed from TRASH ($hid)"); }
              else { logthat($userid, "Label $label removed for $mid ($hid)"); }
            }
            $actions[$mid]['labelIds'] = json_encode($action->message->labelIds);
          }
        }

        if (isset($item->labelsAdded))
        {
          foreach ($item->labelsAdded as $action)
          {
            $mid = $action->message->id;

            // update existing message labels/box/read
            foreach ($action->labelIds as $label)
            {
              if ($label == "UNREAD") { $actions[$mid]['read'] = 0; }
              elseif ($label == "INBOX") { $actions[$mid]['box'] = "INBOX"; }
              elseif ($label == "SENT") { $actions[$mid]['box'] = "SENT"; }
              elseif ($label == "SPAM") { $actions[$mid]['box'] = "SPAM"; }
              elseif ($label == "TRASH") { $actions[$mid]['box'] = "TRASH"; }
              else { logthat($userid, "Label $label added for $mid ($hid)"); }
            }
            $actions[$mid]['labelIds'] = json_encode($action->message->labelIds);
          }
        }

        if (isset($item->messagesAdded))
        {
          foreach ($item->messagesAdded as $action)
          {
            $mid = $action->message->id;

            // check if this messageId have not been already processed
            if (!in_array($mid, $received))
            {
              logthat($userid, "Message $mid added ($hid)");
              // get and analyze message
              $email = get_gmail_msg($userid, $service, $mid);
              if (is_array($email)) { analyze_email($userid, $service, $email); }
              // add to received list to avoid processing the same email more than once
              $received[] = $mid;
            }
          }
        }

        if (isset($item->messagesDeleted))
        {
          foreach ($item->messagesDeleted as $action)
          {
            $mid = $action->message->id;

            // message has been completely deleted
            logthat($userid, "Message $mid deleted ($hid)");
            $actions[$mid]['box'] = "DELETED";
          }
        }

        // no action provided may mean: message already read and now re-opened

        // treat actions
        foreach ($actions as $mid => $action)
        {
          // update read status
          if (isset($action['read']))
          {
            if ($action['read'] == 1) { logthat($userid, "Message $mid has been READ ($hid)"); }
            else { logthat($userid, "Message $mid marked as UNREAD ($hid)"); }

            $sqlu = "UPDATE `message` m, `google_message` gm SET `read` = ".$action['read']." WHERE m.`id` = gm.`message_id` AND gm.`messageId` = '$mid'";
            $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
          }

          // update box
          if (!empty($action['box']))
          {
            logthat($userid, "Message $mid moved to ".$action['box']." ($hid)");

            $sqlu = "UPDATE `message` m, `google_message` gm SET `box` = '".$action['box']."' WHERE m.`id` = gm.`message_id` AND gm.`messageId` = '$mid'";
            $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
          }

          // update labelIds
          if (!empty($action['labelIds']))
          {
            logthat($userid, "Message $mid has now LABELS: ".$action['labelIds']);

            $sqlu = "UPDATE `google_message` SET `labelIds` = '".$action['labelIds']."' WHERE `messageId` = '$mid'";
            $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
          }
        }

      } // foreach $history

      logthat($userid, "End of foreach history");

      // check if we have a pageToken in results
      if (isset($results->nextPageToken))
      {
        logthat($userid, "Fetching next page...");
        $pageToken = $results->nextPageToken;
      }
      else
      {
        // if not, exit the while loop
        break;
      }
     } // while true

     // used to save the last historyId treated
     $historyid = $hid;

    } // mysqli_num_rows $resh (historyId)
    else
    {

      // did not find historyId in db, so we must perform a full sync (if not already in progress)
      $sql = "INSERT IGNORE INTO `google_fullsync`(`user_id`, `date`, `progress`) VALUES($userid, UNIX_TIMESTAMP(), 0)";
      $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    }
   } // full = false

   if ($full == true)
   {
    $messages = array();
    $historyid = 0;
    $pageToken = 0;

    // default request options
    $req_options = array('q' => 'after:'.date("Y/n/j", strtotime('6 month ago')), 'includeSpamTrash' => true, 'maxResults' => 500);

    // for each page of results ($i = max nb of pages)
    for ($i=1; $i <= 5; $i++)
    {
      if ($pageToken == 0)
      {
        // get first page
        logthat($userid, "Get first page of user messages...");

        // récupérer l'id du message le plus récent d'avant un mois et get message pour avoir son historyid
        $results = $service->users_messages->listUsersMessages("me", $req_options);
        logthat($userid, "OK got first page of user messages...");

        $messages = $results->messages;
      }
      else
      {
        // if a pageToken is set, get that page
        logthat($userid, "Get page $i of user messages...");

        // get results of next page
        $results = $service->users_messages->listUsersMessages("me", array_merge($req_options, array('pageToken' => $pageToken)));
        logthat($userid, "OK got page $i of user messages...");

        $messages = array_merge($messages, $results->messages);
      }

      // check if we have a pageToken in results
      if (isset($results->nextPageToken))
      {
        $pageToken = $results->nextPageToken;
      }
      else
      {
        // if not, exit the while loop
        break;
      }
    } // end for

    $total = count($messages);
    //$total = $total + $results->resultSizeEstimate;
    logthat($userid, "Number of messages: $total");

    // init vars
    $pct = 1;
    $pctlast = time();

    // for each message
    foreach($messages as $key => $message)
    {
      logthat($userid, "Get messageId ".$message['id']." ($key / $total)");

      // get and analyze message
      $email = get_gmail_msg($userid, $service, $message['id']);
      if (is_array($email)) { $hid = analyze_email($userid, $service, $email); }

      // store first historyId
      if ($historyid == 0 && $hid > 0) { $historyid = $hid; }

      // calculate processing percentage
      $pctnew = round(100 * ($key + 1) / $total);

      // if it has risen of at least 1% and it's been more than 5s
      if ($pctnew != $pct && $pctnew > 1 && $pctnew < 100 && $pctlast < (time() - 5))
      {
        // update in db
        logthat($userid, "Updating percentage to ".$pctnew."% ...");
        $sqlu = "UPDATE `google_fullsync` SET `progress` = $pctnew WHERE `user_id` = $userid";
        $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

        // update current db pct
        $pct = $pctnew;
        $pctlast = time();
      }
    } // foreach $messages

    logthat($userid, "End of foreach messages (fullsync)");


    // pre-filtering
    $sql = "SELECT n.`id`, n.`received`, r.`reads`, (r.`reads` / n.`received`) as `openrate`, mn.`metaactions`, mn.`metarateallow`, mn.`metarateunsub`, mn.`metareceived`, mn.`metaratefiltered`
      FROM `newsletter` n, `message` m
      JOIN
      (SELECT `newsletter_id`, MAX(`date`) as `lastdate`
       FROM `message`
       WHERE `box` != 'SPAM' AND `box` != 'DELETED'
       GROUP BY `newsletter_id`)
      AS l ON l.`newsletter_id` = m.`newsletter_id`
      LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
      AS r ON r.`newsletter_id` = m.`newsletter_id`
      LEFT JOIN (SELECT `from`, `actions` as `metaactions`, `received` as `metareceived`, (`act_allow` / `actions`) as `metarateallow`, (`act_unsubscribe` / `actions`) as `metarateunsub`, (`filtered` / `received`) as `metaratefiltered` FROM `metanewsletter` WHERE `actions` > 0)
      AS mn ON mn.`from` = m.`from`
      WHERE m.`user_id` = $userid
      AND m.`newsletter_id` = n.`id`
      AND m.`date` = l.`lastdate`
      AND n.`action` = 0
      AND n.`msgtype` = 2
      GROUP BY m.`newsletter_id`";

    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

    logthat($userid, "Starting pre-filtering...");

    if ($res && mysqli_num_rows($res))
    {
      $actions = array();

      while ($row = mysqli_fetch_assoc($res))
      {
        // received : 1-X
        // openrate : 0-1
        // metaactions : 1-X
        // metareceived : 1-X
        // metarateallow : 0-1 ou NULL
        // metarateunsub : 0-1 ou NULL
        // metaratefiltered : 0-1 ou NULL
        // action : 0=new 1=allow 2=digest 3=block

        // default action
        $action = 0;

        logthat($userid, "Stats for newsletter ".$row['id']." : received=".$row['received']." openrate=".$row['openrate']." metarateallow=".$row['metarateallow']." metarateunsub=".$row['metarateunsub']." metaactions=".$row['metaactions']);

        // rules
        if ($row['received'] >= 4)
        {
          if ($row['openrate'] < 0.5 && $row['received'] >= 10)
          {
            $action = 3;
          }
          elseif ($row['openrate'] < 0.3)
          {
            $action = 3;
          }
          elseif ($row['openrate'] <= 0.6 && $row['metarateunsub'] !== NULL && $row['metarateunsub'] >= 0.75 && $row['metaactions'] >= 3)
          {
            $action = 3;
          }
          elseif ($row['openrate'] > 0.5 && $row['metarateallow'] !== NULL && $row['metarateallow'] >= 0.75 && $row['metaactions'] >= 3)
          {
            $action = 1;
          }
          elseif ($row['openrate'] >= 0.9)
          {
            $action = 1;
          }
        }
        else
        {
          if ($row['openrate'] < 1 && $row['metarateunsub'] !== NULL && $row['metarateunsub'] >= 0.8 && $row['metaactions'] >= 5)
          {
            $action = 3;
          }
          elseif ($row['openrate'] > 0.5 && $row['metarateallow'] !== NULL && $row['metarateallow'] >= 0.8 && $row['metaactions'] >= 5)
          {
            $action = 1;
          }
          elseif ($row['openrate'] == 1)
          {
            $action = 1;
          }
        }

        if ($action > 0)
        {
          // update status
          logthat($userid, "Decided action $action for newsletter ".$row['id']);

          // add to actions list
          $actions[$action][] = $row['id'];
        }
      } // end while

      foreach($actions as $action => $ids)
      {
        $ids = implode(", ", $ids);
        $sqlu = "UPDATE `newsletter` SET `action` = $action WHERE `id` IN ($ids) AND `user_id` = $userid";
        $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

        // debug
        logthat($userid, "SQL req: ".$sqlu);
      }

    } // end if $res


    logthat($userid, "Finished all emails, updating percentage to 100% ...");
    $sqlu = "UPDATE `google_fullsync` SET `progress` = 100 WHERE `user_id` = $userid";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    // check and eventually set watcher
    set_gmail_watcher($userid);

   } // $full == true

   // store last historyId in db if any
   if ($historyid > 0)
   {
    logthat($userid, "Set historyId in google_history to: $historyid");
    $sqlu = "REPLACE INTO `google_history`(`user_id`, `historyId`) VALUES($userid, $historyid)";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
    return true;
   }
   elseif ($full == false)
   {
    // no result in partial sync
    logthat($userid, "No result in partial sync!");
    return true;
   }

   // if we arrived here, something went wrong
   logthat($userid, "Something went wrong!");
   return false;

} // function process_gmail_box()


function get_gmail_msg($userid, $service, $mid, $dbg = false)
{
  global $conn;

  // check if message does not already exists
  $sql = "SELECT `message_id` FROM `google_message` WHERE `messageId` = '$mid'";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
  if (!$dbg && $res && mysqli_num_rows($res) > 0) { return false; }

  try {
    // get message
    $msg = $service->users_messages->get("me", $mid, array('format' => 'full'));

    // get thread to get headers
    $thread = $service->users_threads->get("me", $msg->threadId);
  }
  catch (Exception $e) {
    $error = $e->getMessage();
    $error = json_decode($error);
    if ($error->error->code == 404) { logthat($userid, "Message $mid not found, may have been deleted"); }
    else { logthat($userid, "Exception while trying to get email $mid : " . $e->getMessage() ); }
    return false;
  }

  $headers = $thread->messages[0]->payload->headers;
  $msgheaders = $msg->payload->headers;

  // init vars
  $email = array();
  $email['unsub'] = array();
  $email['listid'] = array();
  $email['subject'] = "";

  // for each message header
  foreach($headers as $header)
  {
    //if (in_array(strtolower($header['name']), array("from", "list-unsubscribe", "to", "reply-to", "delivered-to", "date", "subject", "x-campaign", "list-id")))

    if (strtolower($header['name']) == "from")
    {
      if (preg_match('/(.*) <([^>]+)>/', $header['value'], $matches)) {
        $email['fromname'] = trim(str_replace('"', '', $matches[1]));
        $email['fromemail'] = trim($matches[2]);
      } else {
        $email['fromname'] = "";
        $email['fromemail'] = trim(str_replace(array("<", ">"), "", $header['value']));
      }
    }
    elseif (strtolower($header['name']) == "to")
    {
      if (preg_match('/(.*) <([^>]+)>/', $header['value'], $matches)) {
        $email['toname'] = trim(str_replace('"', '', $matches[1]));
        $email['toemail'] = trim($matches[2]);
      } else {
        $email['toname'] = "";
        $email['toemail'] = trim(str_replace(array("<", ">"), "", $header['value']));
      }
    }
    elseif (strtolower($header['name']) == "subject")
    {
      $email['subject'] = trim($header['value']);
    }
    elseif (strtolower($header['name']) == "list-unsubscribe")
    {
      logthat($userid, "List-unsubscribe: ".$header['value']);
      // separateur=, données=<data> et virer ce qui est en dehors de <data>
      foreach(explode(", ", $header['value']) as $item)
      {
        if (preg_match("/<([^>]+)>\s*$/", $item, $matches))
        {
          // item with tags removed
          $email['unsub'][] = trim($matches[1]);
        }
        else
        {
          // item without tags
          $email['unsub'][] = trim($item);
        }
      }
    }
    elseif (preg_match("/^(list-id|x?-?feedback-id|x-.*campaign.*)$/i", $header['name']))
    {
      // get list-id in priority, otherwise get what we can
      //if (empty($email['listid']) || strtolower($header['name']) == "list-id" || strtolower($header['name']) == "feedback-id" || strtolower($header['name']) == "x-feedback-id")
      $key = $header['name'];
      $email['listid'][$key] = trim($header['value']);
    }
  }

  // check if mail sent
  $profile = $service->users->getProfile("me");
  if (!empty($email['fromemail']) && $email['fromemail'] == $profile->emailAddress)
  {
    logthat($userid, "Discarding mail sent by current user: ".$email['fromemail']);
    return false;
  }

  // print headers
  //print_r($email);

  $email['id'] = $mid;
  $email['threadId'] = $msg->threadId;
  $email['historyId'] = $msg->historyId;
  $email['labelIds'] = json_encode($msg->labelIds);
  $email['date'] = $msg->internalDate / 1000;
  $email['snippet'] = $msg->snippet;
  $email['category'] = "unknown";
  $email['box'] = "none";
  $email['read'] = 1;
  $email['topic'] = 'sometopic';
  $email['headers'] = json_encode(array("list-id" => $email['listid'], "list-unsubscribe" => $email['unsub']), JSON_UNESCAPED_SLASHES);

  // for each message label
  foreach ($msg->labelIds as $label)
  {
    if (preg_match("/^CATEGORY_/", $label)) { $email['category'] = $label; logthat($userid, "Message $mid has category $label"); }
    elseif (in_array(strtoupper($label), array("INBOX", "SENT", "SPAM", "TRASH"))) { $email['box'] = $label; logthat($userid, "Message $mid is in box: $label"); }
    elseif ($label == "UNREAD") { $email['read'] = 0; logthat($userid, "Message $mid has NOT been READ"); }
    elseif (!preg_match("/^Label_/", $label)) { logthat($userid, "Message $mid has label $label"); }
  }

  if ($email['box'] == "SENT")
  {
    logthat($userid, "Discarding mail in SENT box");
    return false;
  }

  logthat($userid, "Message $mid arrived on ".date("Y-m-d H:i:s", $email['date']) );

  // get message body
  if (isset($msg->payload->body->data) && !empty($msg->payload->body->data))
  {
    // single part email
    logthat($userid, "INFO: found body in payload");
    $email['body'] = $msg->payload->body->data;
  }
  elseif (isset($msg->payload->parts))
  {
    // multipart email
    $parts = $msg->payload->parts;
    foreach ($parts as $key => $part)
    {
     if (isset($part->parts))
     {
      // part has subparts...
      $subparts = $part->parts;
      foreach ($subparts as $subkey => $subpart)
      {
       if (!empty($subpart->body->data))
       {
        // found body in subpart
        logthat($userid, "INFO: found body in part $key - subpart $subkey of type ".$subpart->mimeType);
        $email['body'] = $subpart->body->data;

        // exit loop if text/html
        if ($subpart->mimeType == "text/html") { break(2); }
       }
      }
     }
     if (isset($part->body->data) && !empty($part->body->data))
     {
      // found body in part
      logthat($userid, "INFO: found body in part ".$key);
      $email['body'] = $part->body->data;

      // decode body and test if DOM document
      $body = base64url_decode($email['body']);
      $DOM = new DOMDocument;
      @$DOM->loadHTML($body);

      // test if body has links
      if ($DOM !== false) {
        $links = $DOM->getElementsByTagName('a');
        if ($links->length > 0) {
          logthat($userid, "INFO: this one is a valid DOM document with links");
          break;
        }
      }
     }
    }
  }

  // check body
  if (isset($email['body'])) {
    $email['body'] = base64url_decode($email['body']);
  } else {
    $email['body'] = "";
    logthat($userid, "WARNING: body not found!");
    //var_dump($msg);
  }

  return $email;
}

function analyze_email($userid, $service, $email)
{
  global $conn;

  $filtered = 0;

  // searching for unsubscribe link in body if not found in headers
  if (count($email['unsub']) == 0)
  {
    // open body as DOM document
    $DOM = new DOMDocument;
    @$DOM->loadHTML($email['body']);
    //@$DOM->loadHTML('<?xml encoding="UTF-8">' . $email['body']);
    //$DOM->encoding = 'UTF-8';

    if ($DOM == false)
    {
      logthat($userid, "Error: could not open DOM document!");
      $links = array();
    }
    else
    {
      // get all links in body
      $links = $DOM->getElementsByTagName('a');
    }

    foreach ($links as $link)
    {
      $title = $link->nodeValue;
      $title .= " - ".$link->textContent;
      $title .= " - ".$link->getAttribute('title');
      $title .= " - ".$link->getAttribute('alt');
      $href = $link->getAttribute('href');

      logthat($userid, "-- title: ".strip_tags($title)."\n-- link: ".$href);
      $linksearch = strip_tags($title)." ".$href;
      if (preg_match("/un-?subscribe|un-?register|sinscri|sabonn|recevoir|receiv|manage/ui", $linksearch))
      {
        logthat($userid, "found unsubscribe link : ".$href);
        $email['unsub'][] = trim($href);
      }
    }

    if (count($email['unsub']) == 0)
    {
      logthat($userid, "-- not found any link");
    }

    // Old regex:
    //if (preg_match_all('/<a [^>]*href=[\'"]?([^\'" ]+)[\'"]?[^>]*>(.+)<\/a>/i', $email['body'], $matches, PREG_SET_ORDER))
  }

  //echo "\n";

  //var_dump($email['unsub']);

  // if trash or spam: return true ?

  // args to find similar newsletters
  $where = "";

  // check listid
  foreach ($email['listid'] as $key => $value)
  {
    $where .= " OR `listid` LIKE '%".sanitize(substr(json_encode(array($key => $value), JSON_UNESCAPED_SLASHES), 1, -1))."%'";
  }

  // check unsub links
  foreach ($email['unsub'] as $unsublink)
  {
    $unsublink = preg_replace("#https?://#i", "", $unsublink);
    $where .= " OR `listunsubscribe` LIKE '%".sanitize($unsublink)."%'";
  }

  $resn = false;

  // find newsletters with same subject or listunsub or listid (except figaro which is treated via fulltext on subject only)
  if (!preg_match("/[@.]lefigaro\.fr$/i", $email['fromemail']))
  {
    // find similar newsletters
    $sqln = "SELECT `id`, `action`, `unsubscribed`, `msgtype`, `firstdate`, `lastdate`
      FROM `newsletter`
      WHERE `user_id` = $userid
      AND `from` = '".sanitize($email['fromemail'])."'
      AND (`subject` = '".sanitize($email['subject'])."'$where)
      LIMIT 1";

    logthat($userid, "Find query: $sqln");
    $resn = mysqli_query($conn, $sqln) or logthat($userid, "SQL Error:\n$sqln\n".mysqli_error($conn));
  }

  // if found nothing, try with fulltext on fromname (except figaro which is treated via fulltext on subject only)
  if (!preg_match("/[@.]lefigaro\.fr$/i", $email['fromemail']) && (!$resn || !mysqli_num_rows($resn)) && !empty($email['fromname']))
  {
    $sqln = "SELECT `id`, `action`, `unsubscribed`, `msgtype`, `firstdate`, `lastdate`, MATCH (`fromname`) AGAINST ('".sanitize($email['fromname'])."' IN NATURAL LANGUAGE MODE) AS `score`
      FROM `newsletter`
      WHERE `user_id` = $userid AND `from` = '".sanitize($email['fromemail'])."'
      AND MATCH (`fromname`) AGAINST ('".sanitize($email['fromname'])."' IN NATURAL LANGUAGE MODE)
      HAVING `score` >= 2
      ORDER BY `score`, `lastdate` DESC
      LIMIT 1";

    logthat($userid, "Find query fulltext on fromname: $sqln");
    $resn = mysqli_query($conn, $sqln) or logthat($userid, "SQL Error:\n$sqln\n".mysqli_error($conn));
  }

  // otherwise, try a fulltext search on subject
  // goal: identification by piece of subject ("Ce mois-ci sur CANAL+ : ...", "... - Quora", "... autres ventes sur vente-privee")
  if ((!$resn || !mysqli_num_rows($resn)) && !empty($email['subject']))
  {
    $sqln = "SELECT `id`, `action`, `unsubscribed`, `msgtype`, `firstdate`, `lastdate`, MATCH (`subject`) AGAINST ('".sanitize($email['subject'])."' IN NATURAL LANGUAGE MODE) AS `score`
      FROM `newsletter`
      WHERE `user_id` = $userid AND `from` = '".sanitize($email['fromemail'])."'
      AND MATCH (`subject`) AGAINST ('".sanitize($email['subject'])."' IN NATURAL LANGUAGE MODE)
      HAVING `score` >= 2
      ORDER BY `score`, `lastdate` DESC
      LIMIT 1";

    logthat($userid, "Find query fulltext on subject: $sqln");
    $resn = mysqli_query($conn, $sqln) or logthat($userid, "SQL Error:\n$sqln\n".mysqli_error($conn));
  }

  // if we finally found something
  if (mysqli_num_rows($resn) > 0)
  {
    // found similar newsletter
    $row = mysqli_fetch_assoc($resn);
    $newsletter_id = $row['id'];

    logthat($userid, "Found newsletter $newsletter_id - action=".$row['action']." - unsubscribed=".$row['unsubscribed']." - msgtype=".$row['msgtype']." -- box=".$email['box']);

    // move blocked newsletter to FreeYourInbox label
    // action = 0 : nothing/unknown
    // action = 1 : allow
    // action = 2 : digest
    // action = 3 : block
    // unsubscribed = 1 : unsubscribed but no URL/mailto
    // unsubscribed = 2 : unsubscribe by URL
    // unsubscribed = 3 : unsubscribe by mailto

    if ($row['action'] == 1)
    {
      // allowed mail
      logthat($userid, "Allowed email, not touching it");
    }
    elseif ($row['action'] == 2)
    {
      // newsletter in digest
      logthat($userid, "Move email to label FreeYourInbox_Digest now...");
      $postBody = new Google_Service_Gmail_ModifyMessageRequest();
      $postBody->setRemoveLabelIds(array("INBOX", "SPAM", "TRASH"));
      $postBody->setAddLabelIds(array( get_label($userid, $service, "digest") ));
      $service->users_messages->modify("me", $email['id'], $postBody);
    }
    elseif ($row['action'] == 3)
    {
      // blocked newsletter
      logthat($userid, "Moving email to label FreeYourInbox + TRASH now...");
      $postBody = new Google_Service_Gmail_ModifyMessageRequest();
      $postBody->setRemoveLabelIds(array("INBOX", "SPAM"));
      $postBody->setAddLabelIds(array( get_label($userid, $service, "block"), "TRASH" ));
      $service->users_messages->modify("me", $email['id'], $postBody);

      $filtered = 1;
    }
/*
    elseif ($email['box'] != "SPAM" && $row['msgtype'] == 99)
    {
      // newsletter is spam (type 99) but this message is not in SPAM box
      logthat($userid, "Deleting email now (spam)...");
      $service->users_messages->trash("me", $email['id']);

      $filtered = 1;
    }
*/

    $set = "";
    // specific to personnal newsletter table
    if ($email['date'] < $row['firstdate'])
    {
      $set .= ", `firstdate` = ".$email['date'];
    }

    if ($email['date'] > $row['lastdate'])
    {
      $set .= ", `lastdate` = ".$email['date'];
    }

    // update newsletter stats
    $sqlu = "UPDATE `newsletter` SET `received` = `received` + 1, `filtered` = `filtered` + $filtered $set WHERE `id` = ".$newsletter_id;
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));
  }
  else
  {
    // no similar newsletter found, gonna add it

    // find type of message
    if ($email['box'] == "SPAM") { $email['type'] = 99; } // TODO: check against safebrowsing for SPAM in TRASH
    elseif ($email['category'] == "CATEGORY_PERSONAL") { $email['type'] = 1; } // google does a better job than us, helps against false positives
    elseif (count($email['unsub']) > 0) { $email['type'] = 2; }
    elseif (preg_match("/news[ -]?letter|un-?subscribe|un-?register|sinscri|sabonn|ne plus recevoir/i", $email['subject'].$email['body'])) { $email['type'] = 2; }
    elseif ($email['category'] == "CATEGORY_PROMOTIONS") { $email['type'] = 2; }
    elseif ($email['category'] == "CATEGORY_UPDATES") { $email['type'] = 3; }
    elseif ($email['category'] == "CATEGORY_SOCIAL") { $email['type'] = 4; }
    elseif ($email['category'] == "CATEGORY_FORUMS") { $email['type'] = 5; }
    else { $email['type'] = 0; }

    $action = 0;
    $filtered = 0;

    // check if FreeYourInbox label is applied to this email
    if (in_array(get_label($userid, $service, "digest"), json_decode($email['labelIds'], true))) {
      // then set it as digest
      $action = 2;
    }
    // check if FreeYourInbox label is applied to this email
    elseif (in_array(get_label($userid, $service, "block"), json_decode($email['labelIds'], true))) {
      // then set it as unsubscribed/filtered
      $filtered = 1;
      $action = 3;
    }

    // do not add spam
    //if ($email['type'] != 99)
    //{
      // store newsletter
      $sql = "INSERT INTO `newsletter`(`user_id`, `from`, `fromname`, `subject`, `listid`, `listunsubscribe`, `action`, `unsubscribed`, `msgtype`, `firstdate`, `lastdate`, `received`, `filtered`)
      VALUES($userid, '".sanitize($email['fromemail'])."', '".sanitize($email['fromname'])."', '".sanitize($email['subject'])."', '".sanitize(json_encode($email['listid'], JSON_UNESCAPED_SLASHES))."', '".sanitize(json_encode($email['unsub'], JSON_UNESCAPED_SLASHES))."', $action, $filtered, ".$email['type'].", ".$email['date'].", ".$email['date'].", 1, $filtered)";
      //echo "\nQuery: $sql \n\n";
      $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
      $newsletter_id = mysqli_insert_id($conn);
    //} else {
    //  $newsletter_id = 0;
    //}

    // insert stats in metanewsletter
    $sql = "INSERT IGNORE INTO `metanewsletter`(`from`, `firstdate`, `lastdate`) VALUES('".sanitize($email['fromemail'])."', ".$email['date'].", ".$email['date'].")";
    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
  }

  // if newsletter found or created
  if ($newsletter_id > 0)
  {
    // update metanewsletter stats
    $sqlu = "UPDATE `metanewsletter` SET `received` = `received` + 1, `filtered` = `filtered` + $filtered WHERE `from` = '".sanitize($email['fromemail'])."'";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    // update firstdate in metanewsletter
    $sqlu = "UPDATE `metanewsletter` SET `firstdate` = '".$email['date']."' WHERE `from` = '".sanitize($email['fromemail'])."' AND `firstdate` > '".$email['date']."'";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    // update lastdate in metanewsletter
    $sqlu = "UPDATE `metanewsletter` SET `lastdate` = '".$email['date']."' WHERE `from` = '".sanitize($email['fromemail'])."' AND `lastdate` < '".$email['date']."'";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));


    // store message
    $sql = "INSERT INTO `message`(`user_id`, `box`, `read`, `newsletter_id`, `category`, `topic`, `date`, `from`, `fromname`, `to`, `toname`, `subject`, `headers`)
    VALUES($userid, '".sanitize($email['box'])."', ".$email['read'].", $newsletter_id, '".sanitize($email['category'])."', '".sanitize($email['topic'])."', ".$email['date'].", '".sanitize($email['fromemail'])."', '".sanitize($email['fromname'])."', '".sanitize($email['toemail'])."', '".sanitize($email['toname'])."', '".sanitize($email['subject'])."', '".sanitize($email['headers'])."')";
    //echo "Query: $sql \n\n";
    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
    $message_id = mysqli_insert_id($conn);

    // store google_message
    $sql = "INSERT INTO `google_message`(`message_id`, `messageId`, `threadId`, `labelIds`, `historyId`)
    VALUES($message_id, '".$email['id']."', '".$email['threadId']."', '".$email['labelIds']."', ".$email['historyId'].")";
    //echo "Query: $sql \n\n";
    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
  }

  logthat($userid, "Finished analysis of email");

  return $email['historyId'];
}


/* add mailbox to processing queue */
function queue_process_box($userid)
{
  global $conn;

  // check new actions on mailbox
  $sql = "UPDATE `google_watch` SET `process` = 1 WHERE `user_id` = $userid AND `active` = 1 AND `process` = 0";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  return $res;
}


/* allow this newsletter */
function allow_newsletter($userid, $id)
{
  global $conn;

  // try to connect to user mailbox
  $client = auth_gmail($userid);
  if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
  }

  // try to set gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
  }

  logthat($userid, "Allow newsletter $id");

  // move all e-mails of this newsletter from FYI/Digest/TRASH labels to INBOX
  $count = move_newsletter($userid, $service, $id, array( get_label($userid, $service, "block"), get_label($userid, $service, "digest"), "TRASH" ), array( "INBOX" ));

  // set as allowed in table
  $sqlu = "UPDATE `newsletter` SET `action` = 1 WHERE `user_id` = $userid AND `id` = $id";
  $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

  // update metanewsletter stats
  update_metastats($id, 1, $count);

  // check new actions on mailbox
  queue_process_box($userid);

  // return true by default
  return true;
}

/* put this newsletter in digest */
function digest_newsletter($userid, $id)
{
  global $conn;

  // try to connect to user mailbox
  $client = auth_gmail($userid);
  if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
  }

  // try to set gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
  }

  logthat($userid, "Put newsletter $id in digest");

  // move last e-mail of this newsletter to FreeYourInbox/Digest label
  $count = move_newsletter($userid, $service, $id, array( get_label($userid, $service, "block"), "INBOX", "TRASH" ), array( get_label($userid, $service, "digest") ), 1);

  // add to digest table ?

  // set as allowed in table
  $sqlu = "UPDATE `newsletter` SET `action` = 2 WHERE `user_id` = $userid AND `id` = $id";
  $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

  // update metanewsletter stats
  update_metastats($id, 2, $count);

  // check new actions on mailbox
  queue_process_box($userid);

  // return true by default
  return true;
}

/* block, unsubscribe and move e-mails to FYI label */
function unsubscribe_newsletter($userid, $id)
{
  global $conn;

  // try to connect to user mailbox
  $client = auth_gmail($userid);
  if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
  }

  // try to set gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
  }

  logthat($userid, "Unsubscribe from newsletter $id");

  // move e-mails of this newsletter to FreeYourInbox label
  $count = move_newsletter($userid, $service, $id, array( "INBOX", get_label($userid, $service, "digest") ), array( get_label($userid, $service, "block") ));

  $status = 1;

  // find the list-unsubscribe field
  $sql = "SELECT `listunsubscribe` FROM `newsletter` WHERE `id` = $id LIMIT 1";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  // if we have one
  if ($res && mysqli_num_rows($res) > 0)
  {
    // get it
    $row = mysqli_fetch_assoc($res);
    $unsub = json_decode($row['listunsubscribe'], true);

    // check if we have a mailto: in the list
    $mailtos = preg_grep("/^\s*mailto:/i", $unsub);
    foreach ($mailtos as $mailto)
    {
      $mailto = parse_url($mailto);

      // get the destination address and optional params
      //if (preg_match("/^mailto:\s*([a-z0-9_.-]+)@([a-z0-9_.-]+)(\?.*)?$/i", trim($mailto), $matches))
      if ($mailto != false)
      {
        $to = $mailto['path']; //$matches[1]."@".$matches[2];
        $cc = "";
        $bcc = "";
        $subject = "Unsubscribe";
        $body = "This e-mail was automatically generated and sent by FreeYourInbox.\nVisit www.freeyourinbox.com for more information)";

        if (!empty($mailto['query']))
        {
          $params = explode("&", $mailto['query']); // $matches[3]
          foreach ($params as $param)
          {
            $param = explode("=", $param);
            if (strtolower($param[0]) == "subject") { $subject = $param[1]; }
            elseif (strtolower($param[0]) == "body") { $body = $param[1]; }
            elseif (strtolower($param[0]) == "cc") { $cc = $param[1]; }
            elseif (strtolower($param[0]) == "bcc") { $bcc = $param[1]; }
            //else { echo "unrecognized param : ".$param[0]." = ".$param[1]; exit; }
          }
        }

        $raw = "To: <".$to.">\r\n";
        if (!empty($cc)) { $raw .= "Cc: ".$cc."\r\n"; }
        if (!empty($bcc)) { $raw .= "Bcc: ".$bcc."\r\n"; }
        $raw .= "Subject: ".$subject."\r\n\r\n".$body."\r\n";
        //echo "\n".htmlspecialchars($raw); exit;

        $postBody = new Google_Service_Gmail_Message();
        $postBody->setRaw(base64url_encode($raw));

        $result = $service->users_messages->send("me", $postBody);
        if ($result !== false)
        {
          logthat($userid, "unsubscribed via mailto");
          $status = 3;
        }

        // debug:
        // echo "Sent mail:\n $raw"; exit;
        //if (!$result) { echo "KO sending mail to $to \n"; exit; }
      }
    }

    // or unsubscribe by URL
    if ($status != 3 && !count($mailtos))
    {
      foreach ($unsub as $url)
      {
        $result = file_get_contents($url);
        if ($result !== false)
        {
          logthat($userid, "unsubscribed via URL");
          $status = 2;
        }

        // debug:
        // echo "Called URL: $url \nWith result:\n $result"; exit;
        // if ($result === false) { echo "KO trying $url \n"; exit; }
      }
    }

    if ($status == 1)
    {
      logthat($userid, "unsubscribed via blocking only");
    }

    if ($count !== false)
    {
      // set this newsletter as unsubscribed
      $sqlu = "UPDATE `newsletter` SET `action` = 3, `unsubscribed` = $status, `filtered` = `filtered` + $count WHERE `user_id` = $userid AND `id` = $id";
      $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

      // update meta stats
      update_metastats($id, 3, $count);
    }

    // check new actions on mailbox
    queue_process_box($userid);

    return true;
  }

  // check new actions on mailbox
  queue_process_box($userid);

  // return false by default
  logthat($userid, "error while unsubscribing");
  return false;
}

/* block and move e-mails without unsubscribing */
function block_newsletter($userid, $id)
{
  global $conn;

  // try to connect to user mailbox
  $client = auth_gmail($userid);
  if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
  }

  // try to set gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
  }

  logthat($userid, "Block from newsletter $id");

  // move all e-mails of this newsletter to FYI label
  $count = move_newsletter($userid, $service, $id, array("INBOX", get_label($userid, $service, "digest")), array( get_label($userid, $service, "block") ));

  if ($count !== false)
  {
    // set as unblocked in table
    $sqlu = "UPDATE `newsletter` SET `action` = 3, `unsubscribed` = 1 WHERE `user_id` = $userid AND `id` = $id";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    // update number of filtered e-mails
    $sqlu = "UPDATE `newsletter` SET `filtered` = `filtered` + $count WHERE `user_id` = $userid AND `id` = $id";
    $resu = mysqli_query($conn, $sqlu);

    // update meta stats
    update_metastats($id, 3, $count);
  }

  // check new actions on mailbox
  queue_process_box($userid);

  // return true by default
  return true;
}

/* unblock and move back e-mails */
function unblock_newsletter($userid, $id)
{
  global $conn;

  // try to connect to user mailbox
  $client = auth_gmail($userid);
  if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
  }

  // try to set gmail service
  try {
    $service = new Google_Service_Gmail($client);
  } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
  }

  logthat($userid, "Unblock from newsletter $id");

  // move back all e-mails of this newsletter to inbox
  $count = move_newsletter($userid, $service, $id, array( get_label($userid, $service, "block"), "TRASH" ), array("INBOX"));

  if ($count !== false)
  {
    // set as unblocked in table
    $sqlu = "UPDATE `newsletter` SET `action` = 0, `unsubscribed` = 0 WHERE `user_id` = $userid AND `id` = $id";
    $resu = mysqli_query($conn, $sqlu) or logthat($userid, "SQL Error:\n$sqlu\n".mysqli_error($conn));

    // update number of filtered e-mails
    $sqlu = "UPDATE `newsletter` SET `filtered` = `filtered` - $count WHERE `user_id` = $userid AND `id` = $id";
    $sqlualt = "UPDATE `newsletter` SET `filtered` = 0 WHERE `user_id` = $userid AND `id` = $id";
    $resu = mysqli_query($conn, $sqlu) or mysqli_query($conn, $sqlualt);

    // update metanewsletter stats
    update_metastats($id, 0, $count);
  }

  // check new actions on mailbox
  queue_process_box($userid);

  // return true by default
  return true;
}

/*
 action:
 - 0 : new
 - 1 : allow
 - 2 : digest
 - 3 : block
*/


/*
 Check if FreeYourInbox label exists, if not create it, then return it
*/
function get_label($userid, $service, $type)
{
  global $conn;

  if ($type == "block")
  {
    $label = "FreeYourInbox";
  }
  elseif ($type == "digest")
  {
    $label = "FreeYourInbox/Digest";
  }
  else
  {
    logthat($userid, "Unknown label type!");
    return false;
  }

  $labelid = "";

  // check FreeYourInbox label number
  $sql = "SELECT `".$type."` FROM `google_label` WHERE `user_id` = $userid LIMIT 1";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  if ($res && mysqli_num_rows($res))
  {
    // get label number
    $row = mysqli_fetch_row($res);
    $labelid = $row[0];
  }

  if (!empty($labelid))
  {
    logthat($userid, "found label $label with id $labelid in database");
  }
  else
  {
    // check if label already exists
    $UsersLabels = $service->users_labels->listUsersLabels("me");

    foreach ($UsersLabels->labels as $key => $value)
    {
      if (strtolower($value['name']) == strtolower($label))
      {
        $labelid = $value['id'];
        logthat($userid, "found existing label $label with id $labelid");
        break;
      }
    }

    // if label not found
    if ($labelid == "")
    {
      // create label
      $postBody = new Google_Service_Gmail_Label();
      $postBody->setLabelListVisibility("labelHide");
      $postBody->setMessageListVisibility("show");
      $postBody->setName($label);

      $labelid = $service->users_labels->create("me", $postBody);
      $labelid = $labelid->getId();

      logthat($userid, "created new label $label with id $labelid");
    }

    // store labelid in database
    $sql = "UPDATE `google_label` SET `".$type."` = '$labelid' WHERE `user_id` = $userid";
    $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));
  }

  if (!empty($labelid))
  {
    return $labelid;
  }
  else
  {
    return false;
  }
}


function move_newsletter($userid, $service, $nid, $from, $to, $limit = 1000)
{
  global $conn;
  $realcount = 0;

  logthat($userid, "Start of move_newsletter($userid, -, $nid, ".json_encode($from).", ".json_encode($to).", $limit)");

  // move back all e-mails of this newsletter to inbox
  $sql = "SELECT `messageId`, `labelIds` FROM `google_message` gm, `message` m WHERE gm.`message_id` = m.`id` AND m.`newsletter_id` = $nid ORDER BY m.`date` DESC LIMIT $limit";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  // check number of e-mails to move
  if ($res)
  {
    $count = mysqli_num_rows($res);
    logthat($userid, "Found $count to move");
  }
  else
  {
    $count = 0;
    logthat($userid, "Found none to move");
  }

  if ($count > 0)
  {
    $i = 0;
    $batch = 1;
    $batches = array();

    // set batches of 50 requests max
    while ($row = mysqli_fetch_assoc($res))
    {
      $pass = false;

      // check if message really needs to go in INBOX
      if (in_array("INBOX", $to))
      {
        //logthat($userid, "Label INBOX in destination, check if moving needed...");
        //logthat($userid, "message ".$row['messageId']." has labels: ".$row['labelIds']);

        // for each label in $from (should be FYI - FYI/Digest - TRASH)
        foreach ($from as $label)
        {
          //logthat($userid, "checking label $label");
          // check if e-mail is at least in one of these
          if (in_array($label, json_decode($row['labelIds'])))
          {
            // if so, allow moving (otherwise not needed)
            $pass = true;
            //logthat($userid, "Message ".$row['messageId']." allowed to move");
            break;
          }
        }
      }
      else
      {
        // check if in any $from label
        foreach ($from as $label)
        {
          if (in_array($label, json_decode($row['labelIds'])))
          {
            // allowed to move
            $pass = true;
            break;
          }
        }

        if (!$pass)
        {
          // check if really need to go in any $to
          foreach ($to as $label)
          {
            if (!in_array($label, json_decode($row['labelIds'])))
            {
              // allowed to move
              $pass = true;
              break;
            }
          }
        }
      }

      // check if allowed to move this message
      if ($pass)
      {
        // add message to the batch
        if ($i >= 50) { $i = 0; $batch++; }
        $batches[$batch][] = $row['messageId'];
        $i++;
        $realcount++;
      }
    } // end while $row

    if (count($batches) == 0)
    {
      logthat($userid, "No message has to move (not needed)");
    }

    // for each batch of 50 requests max
    foreach ($batches as $ids)
    {
      // move messages
      logthat($userid, "Move message(s) ".json_encode($ids)." from any label ".json_encode($from)." to ".json_encode($to));

      try {
        $postBody = new Google_Service_Gmail_BatchModifyMessagesRequest();
        $postBody->setRemoveLabelIds($from);
        $postBody->setAddLabelIds($to);
        $postBody->setIds($ids);
        $service->users_messages->batchModify("me", $postBody);
      } catch (Exception $e) {
        logthat($userid, "Exception while moving e-mails: " . $e->getMessage() );
        return false;
      }
    }
  }

  logthat($userid, "End of move_newsletter($userid, -, $nid, ".json_encode($from).", ".json_encode($to).", $limit)");

  return $realcount;
}


// update stats on each action
function update_metastats($id, $action, $count = 0)
{
  global $conn;

  logthat("meta", "Meta called: id=$id - action=$action - count=$count");

  // set field per action
  if ($action == 0) { $field = "act_new"; $count = - $count; }
  elseif ($action == 1) { $field = "act_allow"; $count = - $count; }
  elseif ($action == 2) { $field = "act_digest"; $count = false; }
  elseif ($action == 3) { $field = "act_unsubscribe"; }
  else { return false; }

  // update metanewsletter stats
  $sqlu = "UPDATE `metanewsletter` m, `newsletter` n SET m.`actions` = m.`actions` + 1, m.`$field` = m.`$field` + 1 WHERE m.`from` = n.`from` AND n.`id` = $id";
  $resu = mysqli_query($conn, $sqlu) or logthat("meta", "SQL Error:\n$sqlu\n".mysqli_error($conn));

  // if need to update filtered number
  if ($resu && $count !== false)
  {
    // get current number
    $sql = "SELECT m.`filtered` FROM `metanewsletter` m, `newsletter` n WHERE m.`from` = n.`from` AND n.`id` = $id";
    $res = mysqli_query($conn, $sql) or logthat("meta", "SQL Error:\n$sql\n".mysqli_error($conn));

    // check if result
    if ($res && mysqli_num_rows($res))
    {
      $row = mysqli_fetch_assoc($res);

      // check if filtered will still be >= 0
      if ($row['filtered'] + $count >= 0)
      {
        // update filtered number
        $sqlu = "UPDATE `metanewsletter` m, `newsletter` n SET m.`filtered` = m.`filtered` + $count WHERE m.`from` = n.`from` AND n.`id` = $id";
        $resu = mysqli_query($conn, $sqlu) or logthat("meta", "SQL Error:\n$sqlu\n".mysqli_error($conn));
      }
    }
  }

  logthat("meta", "Meta worked!");

  return $resu;
}


// update stats of all newsletters (obsolete)
function update_metanewsletters()
{
  global $conn;

  return false;

  $sql = "TRUNCATE TABLE `metanewsletter`";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  $sql = "INSERT INTO `metanewsletter` (SELECT `from`, count(`id`) AS `identified`, (SELECT count(u.`id`) FROM `newsletter` u WHERE u.`unsubscribed` > 0 AND u.`from` = n.`from`) AS `unsubscribed`, MIN(`firstdate`) AS `firstdate`, MAX(`lastdate`) AS `lastdate`, SUM(`received`) AS `received`, SUM(`filtered`) AS `filtered` FROM `newsletter` n GROUP BY `from`)";
  $res = mysqli_query($conn, $sql) or logthat($userid, "SQL Error:\n$sql\n".mysqli_error($conn));

  return $res;
}

function debug_getmail($userid, $messageid)
{
   global $conn;

   // try to connect to user mailbox
   $client = auth_gmail($userid);
   if ($client == false) {
    logthat($userid, "Failed to auth to user mailbox!");
    return false;
   }

   // try to set gmail service
   try {
    $service = new Google_Service_Gmail($client);
   } catch (Exception $e) {
    logthat($userid, "Exception while setting gmail service: " . $e->getMessage() );
    return false;
   }

   echo "auth and service ok<br><pre>\n";
   $email = get_gmail_msg($userid, $service, $messageid, true);

   var_dump($email);

   $DOM = new DOMDocument;
   @$DOM->loadHTML($email['body']);

   // test if body has links
   if ($DOM !== false) {
     $links = $DOM->getElementsByTagName('a');
     if ($links->length > 0) {
       echo "INFO: this one is a valid DOM document with links\n";
       var_dump($links);

    foreach ($links as $link)
    {
      $title = $link->nodeValue;
      $title .= " - ".$link->textContent;
      $title .= " - ".$link->getAttribute('title');
      $title .= " - ".$link->getAttribute('alt');
      $href = $link->getAttribute('href');

      echo "-- title: ".strip_tags($title)."\n-- link: ".$href."\n";
      $linksearch = strip_tags($title)." ".$href;
      if (preg_match("/un-?subscribe|un-?register|sinscri|sabonn|recevoir|receiv/ui", $linksearch))
      {
        echo "found unsubscribe link : ".$href."\n";
      }
    }
     }
   }

   return true;
}

if (isset($argc) && $argc > 1 && $argv[1] == "test") {
	//debug_getmail(39, "15f34d36034d0d70"); // greenpeace multipart with subparts
	//debug_getmail(37, "15f36b0dc5d052a3"); // icloud fwd multiparts
	//debug_getmail(39, "15f3a0900bc2a019");
}

//process_gmail_box(1, false);

