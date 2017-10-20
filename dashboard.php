<?php
require_once __DIR__.'/lib/google.checklogin.php';

$header['title'] = "Dashboard";
$header['page'] = "dashboard";
include("headers.php");
?>


  <!-- Page -->
  <div class="page">
    <div class="page-header page-header-bordered">
      <h1 class="page-title">Dashboard</h1>
      <div class="page-header-actions">
        <button type="button" class="btn btn-sm btn-outline btn-default btn-round">
          <span class="text hidden-sm-down">Settings</span>
          <i class="icon md-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
    </div>
    <div class="page-content">
      <div class="panel">
        <div class="panel-heading">
<?php
echo '
          <h3 class="panel-title">Hello ' . $gprofile->displayName . ',</h3>
        </div>
        <div class="panel-body">
          <p>';

/*
echo "First name: ".$gprofile->name->givenName."<br>";
echo "Last name: ".$gprofile->name->familyName."<br>";
echo 'Photo : <img src="'.$gprofile->image->url.'">'."<br>";
echo "Language: ".$gprofile->language."<br>";
echo "<br>";
*/

//var_dump($profile);
echo "Your e-mail address is: " . $profile->emailAddress . "<br>";
echo "You have " . $profile->messagesTotal . " messages";
echo " within " . $profile->threadsTotal . " threads<br>";
//echo "Your last historyId is: " . $profile->historyId . "<br>";

// check full sync status
$sql = "SELECT `progress`, `date` FROM `google_fullsync` WHERE `user_id` = ".$_SESSION['userid']." LIMIT 1";
$res = mysqli_query($conn, $sql);

if (mysqli_num_rows($res))
{
  $row = mysqli_fetch_assoc($res);
}
else
{
  $row = false;
}

if (is_array($row) && $row['progress'] == -1)
{
  echo 'Last full synchronization in error! <a href="/act.php?do=fullsync&id='.$_SESSION['userid'].'">Click here to restart</a>';
}
elseif (is_array($row) && $row['progress'] < 100)
{
  echo '<p>A full synchronization is running since '.date("d/m/Y", $row['date']).' at '.date("H:i:s", $row['date']).' (<b>'.$row['progress'].'%</b>)</p>';
}
elseif (is_array($row) && $row['progress'] == 100 && $row['date'] > time() - 86400)
{
  echo '<p>Your mailbox has been successfully synchronized!</p>';
  //echo '<p>Votre dernière synchronisation complète est trop récente, merci de patienter 24h entre chaque demande.</p>';
}
else
{
  if ($row['progress'] == 100) { echo '<p>Your mailbox has been successfully synchronized!</p>'; }
  echo '<a href="/act.php?do=fullsync&id='.$_SESSION['userid'].'">Restart a full synchronization</a> <b>(Caution, this wipes out all your history!)</b>';
}

//TODO: check if last historyId from db exists, otherwise do a fullsync in backend mode and show a progress bar in front with ajax refresh
?></p>
        </div>
      </div>
    </div>
  </div>
  <!-- End Page -->

 <?php include("footer.php"); ?>
