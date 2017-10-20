<?php
if (preg_match('/menu\.php/', $_SERVER['PHP_SELF'])) { header('location: /'); }
?>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/" target="_blank">FreeYourInbox</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li<?php if ($page=="dashboard") { echo ' class="active"'; } ?>><a href="dashboard.php"><span class="hidden-xs glyphicon glyphicon-dashboard"></span>&nbsp;Dashboard</a></li>
            <li<?php if ($page=="newsletters") { echo ' class="active"'; } ?>><a href="newsletters.php"><span class="hidden-xs glyphicon glyphicon-envelope"></span>&nbsp;Newsletters</a></li>
            <li<?php if ($page=="blacklist") { echo ' class="active"'; } ?>><a href="blacklist.php"><span class="hidden-xs glyphicon glyphicon-ban-circle"></span>&nbsp;Blacklist</a></li>
            <li><a href="#"><?php echo $_SESSION['email']; ?></a></li>
            <!-- <li<?php if ($page=="user") { echo ' class="active"'; } ?>><a href="user.php"><span class="hidden-xs glyphicon glyphicon-list"></span>&nbsp;Users</a></li> -->
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li<?php if ($page=="account") { echo ' class="active"'; } ?>><a href="account.php"><span class="hidden-xs glyphicon glyphicon-user" title="Account"></span><span class="visible-xs">Account</span></a></li>
            <li<?php if ($page=="settings") { echo ' class="active"'; } ?>><a href="settings.php"><span class="hidden-xs glyphicon glyphicon-cog" title="Settings"></span><span class="visible-xs">Settings</span></a></li>
            <li><a href="logout.php"><span class="hidden-xs glyphicon glyphicon-log-out" title="Logout"></span><span class="visible-xs">Logout</span></a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
