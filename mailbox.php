<?php
require_once __DIR__.'/lib/google.checklogin.php';

function maxlen($text, $max, $right = false)
{
  if (strlen($text) > $max + 1)
  {
    if ($right)
    {
      return "...".substr($text, - $max);
    }
    else
    {
      return substr($text, 0, $max)."...";
    }
  }
  else
  {
    return $text;
  }
}

function formatday($date)
{
  $today = mktime(0,0,0);
  if ($date >= $today) { return "Today"; }
  elseif ($date >= $today - 86400) { return "Yesterday"; }
  elseif ($date >= $today - (86400 * 2)) { return "2 days ago"; }
  else { return date("M d", $date); }
}

if ($_GET['page'] == "newsletters")
{
  // redirect to newsletters page if access from .php file
  if (!preg_match("#^/newsletters#", $_SERVER['REQUEST_URI'])) { header('location: /newsletters'); }

  $header['title'] = "Newsletters";
  $header['page'] = "newsletters";
}
elseif ($_GET['page'] == "blacklist")
{
  // redirect to blacklist page if access from .php file
  if (!preg_match("#^/blacklist#", $_SERVER['REQUEST_URI'])) { header('location: /blacklist'); }

  $header['title'] = "Blacklist";
  $header['page'] = "blacklist";
}
elseif ($_GET['page'] == "allowed")
{
  // redirect to allowed page if access from .php file
  if (!preg_match("#^/allowed#", $_SERVER['REQUEST_URI'])) { header('location: /allowed'); }

  $header['title'] = "Allowed";
  $header['page'] = "allowed";
}
elseif ($_GET['page'] == "digest")
{
  // redirect to digest page if access from .php file
  if (!preg_match("#^/digest#", $_SERVER['REQUEST_URI'])) { header('location: /digest'); }

  $header['title'] = "Digest";
  $header['page'] = "digest";
}
else
{
  header("location: /");
}

include("headers.php");
?>

  <div class="page bg-white">
    <div class="page-main">
      <!-- Mailbox Header -->
      <div class="page-header">
        <h1 class="page-title"><?php echo $header['title']; ?></h1>
        <div class="page-header-actions">
          <form>
            <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">
<?php
if (isset($_GET['sort']))
{
  $sort = $_GET['sort'];
  echo '
            <input type="hidden" name="sort" value="'.$_GET['sort'].'">';
}
else
{
  $sort = "";
}

if (isset($_GET['sorttype']))
{
  $sorttype = $_GET['sorttype'];
  echo '
            <input type="hidden" name="sorttype" value="'.$_GET['sorttype'].'">';
}
else
{
  $sorttype = "";
}
?>
            <div class="input-search input-search-dark">
              <i class="input-search-icon md-search" aria-hidden="true"></i>
              <input type="text" class="form-control" name="q" placeholder="Search..." value="<?php if (isset($_GET['q'])) { echo $_GET['q']; } ?>">
            </div>
          </form>
        </div>
      </div>
      <!-- Mailbox Content -->
      <div id="mailContent" class="page-content page-content-table" data-plugin="selectable">
        <!-- Actions -->
        <div class="page-content-actions">
          <div class="float-right filter">
            <span class="icon md-filter-list" aria-hidden="true"></span><span> Sort :</span>
            <div class="dropdown">
              <button type="button" class="btn btn-pure" data-toggle="dropdown" aria-expanded="false">
                <?php echo (!empty($sort) ? ucfirst($sort) : "Number"); ?>
                <span class="icon md-chevron-down" aria-hidden="true"></span>
              </button>
              <div class="dropdown-menu dropdown-menu-right animation-scale-up animation-top-right animation-duration-250"
              role="menu">
<?php
$params = "";
foreach($_GET as $key => $value)
{
  if (in_array($key, array("q"))) { $params .= $key."=".$value."&"; }
}
?>
                <a class="dropdown-item" href="?<?php echo $params; ?>sort=date<?php if ($sort == "date" && $sorttype != "ASC") { echo "&sorttype=ASC"; } ?>">Date</a>
                <a class="dropdown-item" href="?<?php echo $params; ?>sort=sender<?php if ($sort == "sender" && $sorttype != "DESC") { echo "&sorttype=DESC"; } ?>">Sender</a>
                <a class="dropdown-item" href="?<?php echo $params; ?>sort=openrate<?php if ($sort == "openrate" && $sorttype != "DESC") { echo "&sorttype=DESC"; } ?>">Open rate</a>
                <a class="dropdown-item" href="?<?php echo $params; ?>sort=number<?php if ($sort == "number" && $sorttype != "ASC") { echo "&sorttype=ASC"; } ?>">Number of e-mails</a>
              </div>
            </div>
          </div>
          <div class="actions-main">
            <span class="checkbox-custom checkbox-primary checkbox-lg inline-block vertical-align-bottom">
              <input type="checkbox" class="mailbox-checkbox selectable-all" id="select_all" />
              <label for="select_all"></label>
            </span>
            <!-- <div class="btn-group btn-group-flat">
              <div class="dropdown">
                <button class="btn btn-icon btn-pure btn-default" data-toggle="dropdown" aria-expanded="false"
                type="button"><i class="icon md-folder" aria-hidden="true" data-toggle="tooltip"
                  data-original-title="Category" data-container="body" title=""></i></button>
                <div class="dropdown-menu" role="menu">
                  <a class="dropdown-item" href="javascript:void(0)">Promotions</a>
                  <a class="dropdown-item" href="javascript:void(0)">Updates</a>
                  <a class="dropdown-item" href="javascript:void(0)">Forums</a>
                  <a class="dropdown-item" href="javascript:void(0)">Other</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="javascript:void(0)">Trash</a>
                  <a class="dropdown-item" href="javascript:void(0)">Spam</a>
                </div>
              </div>
              <div class="dropdown">
                <button class="btn btn-icon btn-pure btn-default" data-toggle="dropdown" aria-expanded="false"
                type="button"><i class="icon md-tag" aria-hidden="true" data-toggle="tooltip"
                  data-original-title="Tag" data-container="body" title=""></i></button>
                <div class="dropdown-menu" role="menu">
                  <a class="dropdown-item" href="javascript:void(0)">Work</a>
                  <a class="dropdown-item" href="javascript:void(0)">Family</a>
                  <a class="dropdown-item" href="javascript:void(0)">Private</a>
                  <a class="dropdown-item" href="javascript:void(0)">Friends</a>
                </div>
              </div>
            </div> -->
<?php
// check full sync status
$sql = "SELECT `progress`, `date` FROM `google_fullsync` WHERE `user_id` = ".$_SESSION['userid']." LIMIT 1";
$res = mysqli_query($conn, $sql);

if ($res && mysqli_num_rows($res))
{
  $rowp = mysqli_fetch_assoc($res);
}

// nb newsletters, nb mails newsleters, nb mails traités
$sql = "SELECT count(*) AS `msgcount`, count(DISTINCT `newsletter_id`) AS `newscount`
FROM `message`
WHERE (`box` = 'INBOX' OR `box` = 'TRASH')
AND newsletter_id IN
(SELECT `id` FROM `newsletter`
 WHERE `user_id` = ".$_SESSION['userid']."
 AND `msgtype` = 2
 AND `category` != 'CATEGORY_PERSONAL')";

$res = mysqli_query($conn, $sql);
if ($res)
{
  $row = mysqli_fetch_assoc($res);
  echo '<span style="line-height: 40px; margin-left: 20px;" id="progresstext">We have detected <b>'.$row['msgcount'].'</b> e-mails in <b>'.$row['newscount'].'</b> newsletters';
  if (isset($rowp['progress']) && $rowp['progress'] < 100)
  {
    echo ' so far, as we are currently scanning your mailbox...';
  }
  echo '</span>';
}
?>

          </div>
        </div>
<?php
if (isset($rowp['progress']) && $rowp['progress'] < 100)
{
  //echo '<p>A full synchronization is running since '.date("d/m/Y", $row['date']).' at '.date("H:i:s", $row['date']).' (<b>'.$row['progress'].'%</b>)</p>';
  echo '
        <div class="progress" style="margin-left:30px; margin-right:30px;">
          <div class="progress-bar progress-bar-striped active" aria-valuenow="'.$rowp['progress'].'" aria-valuemin="0" aria-valuemax="100" style="width: '.$rowp['progress'].'%" role="progressbar" id="syncprogress">
            <span class="sr-only">'.$rowp['progress'].'% Complete</span>
          </div>
        </div>';
}
?>
        <!-- Mailbox -->
        <table id="mailboxTable" class="table" data-plugin="animateList" data-animate="fade"
        data-child="tr">
          <tbody>
<?php
if ($_SESSION['userid'] > 0)
{
  if (!empty($sort))
  {
    if ($sort == "date") { if (empty($sorttype)) { $sorttype = "DESC"; } $sqlsort = "m.`date` ".$sorttype; }
    elseif ($sort == "sender") { if (empty($sorttype)) { $sorttype = "ASC"; } $sqlsort = "TRIM(REPLACE(m.`fromname`, '\'', '')) ".$sorttype; }
    elseif ($sort == "openrate") { if (empty($sorttype)) { $sorttype = "ASC"; } $sqlsort = "`openrate` ".$sorttype; }
    elseif ($sort == "number") { if (empty($sorttype)) { $sorttype = "DESC"; } $sqlsort = "n.`received` ".$sorttype; }
  }
  else { $sqlsort = "n.`received` DESC"; }

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

  $pmax = 1000;
  if (empty($_GET['p'])) { $p = 1; } else { $p = $_GET['p']; }
  $pmin = ($p - 1) * $pmax;

  if (!empty($_GET['box'])) {
    $box = "AND m.`box` = '".mysqli_real_escape_string($conn, $_GET['box'])."'";
  } else {
    $box = "AND (m.`box` = 'INBOX' OR m.`box` = 'TRASH')
    AND n.`msgtype` = 2";
  }

  if ($header['page'] == "newsletters")
  {
    $sql = "SELECT n.`id`, n.`lastdate`, m.`fromname`, m.`from`, m.`subject`, n.`action`, n.`received`, r.`reads`, ROUND(r.`reads` / n.`received` * 100) as `openrate`
    FROM `newsletter` n, `message` m
    LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
    AS r ON r.`newsletter_id` = m.`newsletter_id`
    WHERE m.`user_id` = ".$_SESSION['userid']."
    AND m.`newsletter_id` = n.`id`
    AND m.`date` = n.`lastdate`
    AND n.`action` = 0
    $box
    $search
    GROUP BY m.`newsletter_id`
    ORDER BY $sqlsort";
  }
  elseif ($header['page'] == "blacklist")
  {
    $sql = "SELECT n.`id`, n.`lastdate`, m.`fromname`, m.`from`, m.`subject`, n.`action`, n.`received`, r.`reads`, ROUND(r.`reads` / n.`received` * 100) as `openrate`
    FROM `newsletter` n, `message` m
    LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
    AS r ON r.`newsletter_id` = m.`newsletter_id`
    WHERE m.`user_id` = ".$_SESSION['userid']."
    AND m.`newsletter_id` = n.`id`
    AND m.`date` = n.`lastdate`
    AND n.`action` = 3
    $search
    GROUP BY m.`newsletter_id`
    ORDER BY $sqlsort";
  }
  elseif ($header['page'] == "allowed")
  {
    $sql = "SELECT n.`id`, n.`lastdate`, m.`fromname`, m.`from`, m.`subject`, n.`action`, n.`received`, r.`reads`, ROUND(r.`reads` / n.`received` * 100) as `openrate`
    FROM `newsletter` n, `message` m
    LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
    AS r ON r.`newsletter_id` = m.`newsletter_id`
    WHERE m.`user_id` = ".$_SESSION['userid']."
    AND m.`newsletter_id` = n.`id`
    AND m.`date` = n.`lastdate`
    AND n.`action` = 1
    $search
    GROUP BY m.`newsletter_id`
    ORDER BY $sqlsort";
  }
  elseif ($header['page'] == "digest")
  {
    $sql = "SELECT n.`id`, n.`lastdate`, m.`fromname`, m.`from`, m.`subject`, n.`action`, n.`received`, r.`reads`, ROUND(r.`reads` / n.`received` * 100) as `openrate`
    FROM `newsletter` n, `message` m
    LEFT JOIN (SELECT `newsletter_id`, SUM(`read`) AS `reads` FROM `message` GROUP BY `newsletter_id`)
    AS r ON r.`newsletter_id` = m.`newsletter_id`
    WHERE m.`user_id` = ".$_SESSION['userid']."
    AND m.`newsletter_id` = n.`id`
    AND m.`date` = n.`lastdate`
    AND n.`action` = 2
    $search
    GROUP BY m.`newsletter_id`
    ORDER BY $sqlsort";
  }
  else
  {
    exit;
  }

  // get total results
  $res = mysqli_query($conn, $sql) or die(mysqli_error($conn));
  $rescount = mysqli_num_rows($res);

  // AND (m.`category` = 'CATEGORY_PROMOTIONS' OR m.`category` = 'CATEGORY_SOCIAL' OR m.`category` = 'CATEGORY_FORUMS' OR m.`category` = 'CATEGORY_UPDATES' AND n.`received` > 1)

  // get this page results
  $res = mysqli_query($conn, $sql." LIMIT $pmin, $pmax") or die(mysqli_error($conn));

  if (mysqli_num_rows($res) < 1)
  {
    if ($header['page'] == "newsletters")
    {
      echo "<tr><td>Hippie Yeah... Nothing here. Well done!</td></tr>";
    }
    elseif ($header['page'] == "blacklist")
    {
      echo "<tr><td>Nothing here. You can start blocking e-mails from the newsletters page and you'll see them here!</td></tr>";
    }
    elseif ($header['page'] == "allowed")
    {
      echo "<tr><td>Nothing here. You can start allowing e-mails from the newsletters page and you'll see them here!</td></tr>";
    }
    elseif ($header['page'] == "digest")
    {
      echo "<tr><td>Nothing here. You can start putting e-mails in the digest from the newsletters page and you'll see them here!</td></tr>";
    }
  }
  else
  {
    $lastsep = "";

    while ($row = mysqli_fetch_assoc($res))
    {
      if ($row['received'] > 1)
      {
        if ($row['received'] <= 5) { $badgecolor = "success"; }
        elseif ($row['received'] <= 10) { $badgecolor = "primary"; }
        elseif ($row['received'] <= 20) { $badgecolor = "warning"; }
        else { $badgecolor = "danger"; }
        $nbmsg = '<a href="#" data-toggle="tooltip" data-placement="top" title="" data-original-title="Number of e-mails" class="tooltip-primary tooltip-top"><span class="badge badge-pill badge-lg badge-'.$badgecolor.'">'.$row['received'].'</span></a>';
      }
      else
      {
        $nbmsg = '';
      }

      if ($row['openrate'] >= 75) { $trustcolor = "success"; }
      elseif ($row['openrate'] >= 50) { $trustcolor = "primary"; }
      elseif ($row['openrate'] >= 25) { $trustcolor = "warning"; }
      else { $trustcolor = "danger"; }

      $img = "/global/portraits/default.png?v=4";

      if (!empty($row['fromname']))
      {
        $letter = preg_replace("/^[^a-z0-9]*([a-z0-9]).*$/i", '$1', $row['fromname']);
        if (!empty($letter))
        {
          $img = "/img/letters/".strtolower($letter).".png";

          if ($sort == "sender" && $lastsep != strtoupper($letter))
          {
            echo '
            <tr style="background-color:#fafafa; font-size:18px;">
              <td class="cell-60"></td>
              <td class="cell-60">
              <td><b>'.strtoupper($letter).'</b></td>
              <td class="cell-60"></td>
              <td class="cell-30"></td>
              <td class="cell-130"></td>
            </tr>';
            $lastsep = strtoupper($letter);
          }
        }
      }

      if ($sort == "date" && $lastsep != date("Y-m-d", $row['lastdate']))
      {
        echo '
            <tr style="background-color:#fafafa; font-size:18px;">
              <td class="cell-60"></td>
              <td class="cell-60">
              <td><b>'.formatday($row['lastdate']).'</b></td>
              <td class="cell-60"></td>
              <td class="cell-30"></td>
              <td class="cell-130"></td>
            </tr>';
        $lastsep = date("Y-m-d", $row['lastdate']);
      }
?>

            <tr id="mid_<?php echo $row['id']; ?>" data-id="<?php echo $row['id']; ?>">
              <td class="cell-60">
                <span class="checkbox-custom checkbox-primary checkbox-lg">
                  <input type="checkbox" class="mailbox-checkbox selectable-item" id="mail_mid_<?php echo $row['id']; ?>"
                  />
                  <label for="mail_mid_<?php echo $row['id']; ?>"></label>
                </span>
              </td>
              <td class="cell-60 responsive-hide">
                <a class="avatar" href="javascript:void(0)">
                  <img class="img-fluid" src="<?php echo $img; ?>" alt="default">
                </a>
              </td>
              <td>
                <div class="content">
                  <div class="title"><?php echo "<b>" . $row['fromname'] . "</b> " . $row['from']; ?>
                  <a title="<?php echo date("d/m/Y H:i:s", $row['lastdate']); ?>" style="margin-left:5px; color:#a0a0a0"><?php echo formatdate($row['lastdate']); ?></a></div>
                  <div class="abstract"><?php echo $row['subject']; ?></div>
                </div>
              </td>
              <td class="cell-60 text-center">
                 <?php echo $nbmsg; ?>
              </td>
              <td class="cell-30 responsive-hide">
              </td>
              <td class="cell-130 text-center">
                <a href="#" data-toggle="tooltip" data-placement="top" title="" data-original-title="Open rate" class="tooltip-primary tooltip-top">
                  <span class="badge badge-pill badge-lg badge-<?php echo $trustcolor; ?>" style="width:"><?php echo $row['openrate']; ?> %</span>
                </a>
              </td>
            </tr>
<?php
    }
  }
}
?>

          </tbody>
        </table>
<?php
if ($rescount > $pmax)
{
?>
        <!-- pagination -->
        <ul id="pagination-mailbox" data-plugin="paginator" data-total="<?php echo $rescount; ?>" data-current-page="<?php echo $p; ?>" data-items-per-page="<?php echo $pmax; ?>" data-skin="pagination-gap"></ul>
<?php } ?>
      </div>
    </div>
  </div>
  <div class="site-action" data-plugin="actionBtn">
    <button type="button" data-action="add" class="site-action-toggle btn-raised btn btn-success btn-floating">
      <i class="front-icon md-refresh animation-scale-up" aria-hidden="true" id="refreshButton"></i>
      <i class="back-icon md-close animation-scale-up" aria-hidden="true"></i>
    </button>
<?php if ($header['page'] == "newsletters") { ?>
    <div class="site-action-buttons">
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="allowButton">
        <i class="icon md-check" aria-hidden="true"></i>
      </button>
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="digestButton">
        <i class="icon md-star" aria-hidden="true"></i>
      </button>
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="trashButton">
        <i class="icon md-block" aria-hidden="true"></i>
      </button>
    </div>
<?php } elseif ($header['page'] == "blacklist") { ?>
    <div class="site-action-buttons">
      <button type="button" data-action="inbox" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="inboxButton">
        <i class="icon md-inbox" aria-hidden="true"></i>
      </button>
    </div>
<?php } elseif ($header['page'] == "allowed") { ?>
    <div class="site-action-buttons">
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="digestButton">
        <i class="icon md-star" aria-hidden="true"></i>
      </button>
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="trashButton">
        <i class="icon md-block" aria-hidden="true"></i>
      </button>
    </div>
<?php } elseif ($header['page'] == "digest") { ?>
    <div class="site-action-buttons">
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="allowButton">
        <i class="icon md-check" aria-hidden="true"></i>
      </button>
      <button type="button" data-action="trash" class="btn-raised btn btn-success btn-floating animation-slide-bottom" id="trashButton">
        <i class="icon md-block" aria-hidden="true"></i>
      </button>
    </div>
<?php } ?>
  </div>
 <?php include("footer.php"); ?>
 
