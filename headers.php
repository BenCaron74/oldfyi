<?php
function formatdate($date)
{
  //if (date("Y-m-d", $date) == date("Y-m-d", strtotime("today"))) { return date("H:i", $date); }
  //elseif ((time() - $date) < 43200) { return date("H:i", $date); }

  if ((time() - $date) < 3600) { return ceil((time() - $date) / 60)." mn ago"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("today"))) { $h = round((time() - $date) / 3600); return $h." hour". ($h > 1 ? "s" : "") ." ago"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("1 day ago"))) { return "yesterday"; }
  elseif (date("Y-m-d", $date) == date("Y-m-d", strtotime("2 days ago"))) { return "2 days ago"; }
  else { return date("M d", $date); }
}

if (!isset($header['title'])) {
 $header['title'] = "Dashboard";
 $header['page'] = "dashboard";
}
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="pragma" content="no-cache">
  <meta name="robots" content="noindex,nofollow">
  <meta name="description" content="Free Your Inbox | Supprimez vos newsletters en 1 clic">
  <meta name="author" content="freeyourinbox">
 
  <title>FreeYourInbox | <?php echo $header['title']; ?></title>

<link rel="apple-touch-icon-precomposed" sizes="57x57" href="https://freeyourinbox.com/img/apple-touch-icon-57x57.png"/>
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="https://freeyourinbox.com/img/apple-touch-icon-114x114.png"/>
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="https://freeyourinbox.com/img/apple-touch-icon-72x72.png"/>
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="https://freeyourinbox.com/img/apple-touch-icon-144x144.png"/>
<link rel="apple-touch-icon-precomposed" sizes="60x60" href="https://freeyourinbox.com/img/apple-touch-icon-60x60.png"/>
<link rel="apple-touch-icon-precomposed" sizes="120x120" href="https://freeyourinbox.com/img/apple-touch-icon-120x120.png"/>
<link rel="apple-touch-icon-precomposed" sizes="76x76" href="https://freeyourinbox.com/img/apple-touch-icon-76x76.png"/>
<link rel="apple-touch-icon-precomposed" sizes="152x152" href="https://freemyinbox.co/img/apple-touch-icon-152x152.png"/>
<link rel="icon" type="image/png" href="https://freeyourinbox.com/img/favicon-196x196.png" sizes="196x196"/>
<link rel="icon" type="image/png" href="https://freeyourinbox.com/img/favicon-96x96.png" sizes="96x96"/>
<link rel="icon" type="image/png" href="https://freeyourinbox.com/img/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://freeyourinbox.com/img/favicon-16x16.png" sizes="16x16"/>
<link rel="icon" type="image/png" href="https://freeyourinbox.com/img/favicon-128.png" sizes="128x128"/>

  <!-- Stylesheets -->
  <link rel="stylesheet" href="/global/css/bootstrap.min.css?v=1">
  <link rel="stylesheet" href="/global/css/bootstrap-extend.min.css?v=1">
  <link rel="stylesheet" href="/base/assets/skins/blue.css">
  <link rel="stylesheet" href="/base/assets/css/site.min.old.css">
  <link rel="stylesheet" href="/css/freeyourinbox.css?v=4">
  <!-- Plugins -->
  <link rel="stylesheet" href="/global/vendor/animsition/animsition.css">
  <link rel="stylesheet" href="/global/vendor/asscrollable/asScrollable.css">
  <link rel="stylesheet" href="/global/vendor/switchery/switchery.css">
  <link rel="stylesheet" href="/global/vendor/intro-js/introjs.css">
  <link rel="stylesheet" href="/global/vendor/slidepanel/slidePanel.css">
  <link rel="stylesheet" href="/global/vendor/flag-icon-css/flag-icon.css">
  <link rel="stylesheet" href="/global/vendor/waves/waves.css">
  <link rel="stylesheet" href="/global/vendor/bootstrap-markdown/bootstrap-markdown.css">
  <link rel="stylesheet" href="/global/vendor/select2/select2.css">
  <link rel="stylesheet" href="/global/vendor/toastr/toastr.min.css?v2.2.0">
  <link rel="stylesheet" href="/base/assets/examples/css/apps/mailbox.css">
  <!-- Fonts -->
  <link rel="stylesheet" href="/global/fonts/font-awesome/font-awesome.css">
  <link rel="stylesheet" href="/global/fonts/material-design/material-design.min.css">
  <link rel="stylesheet" href="/global/fonts/brand-icons/brand-icons.min.css">
  <link rel='stylesheet' href='//fonts.googleapis.com/css?family=Roboto:300,400,500,300italic'>
  <!--[if lt IE 9]>
    <script src="/global/vendor/html5shiv/html5shiv.min.js"></script>
    <![endif]-->
  <!--[if lt IE 10]>
    <script src="/global/vendor/media-match/media.match.min.js"></script>
    <script src="/global/vendor/respond/respond.min.js"></script>
    <![endif]-->
  <!-- Scripts -->
  <script src="/global/vendor/breakpoints/breakpoints.js"></script>
  <script>
  Breakpoints();
  </script>
<!-- Global Site Tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-106638436-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments)};
  gtag('js', new Date());
  gtag('config', 'UA-106638436-1');
</script>
<!-- User Report -->
<script type="text/javascript">
window._urq = window._urq || [];
_urq.push(['initSite', '5ccd9f75-15f7-4d58-aed1-3619b0efb09b']);
(function() {
var ur = document.createElement('script'); ur.type = 'text/javascript'; ur.async = true;
ur.src = ('https:' == document.location.protocol ? 'https://cdn.userreport.com/userreport.js' : 'http://cdn.userreport.com/userreport.js');
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ur, s);
})();
</script>
<!-- Hotjar Tracking Code for https://freeyourinbox.com -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:627163,hjsv:5};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
</script>
<!-- Intercom -->
<style>
#intercom-container .intercom-launcher-frame, #intercom-container .intercom-messenger-frame, #intercom-container .intercom-borderless-frame {
  left: 20px !important;
  right: auto !important;
}
</style>
<script>
  window.intercomSettings = {
    app_id: "xpc089go",
    name: "<?php echo $_SESSION['name']; ?>", // Full name
    email: "<?php echo $_SESSION['email']; ?>", // Email address
    created_at: <?php echo $_SESSION['signup']; ?>, // Signup date as a Unix timestamp
    user_hash: "<?php echo hash_hmac('sha256', $_SESSION['email'], 'LPNAFSrbVxEmCgyGCcPvBeuYYgHzldMYDIFweIaQ'); ?>" // HMAC using SHA-256
  };
</script>
<script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/xpc089go';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()</script>
</head>
<body class="animsition app-mailbox page-aside-left site-menubar-fold site-menubar-keep">
  <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
  <nav class="site-navbar navbar navbar-default navbar-fixed-top navbar-mega" role="navigation">
    <div class="navbar-header text-center">
      <button type="button" class="navbar-toggler hamburger hamburger-close navbar-toggler-left hided"
      data-toggle="menubar">
        <span class="sr-only">Toggle navigation</span>
        <span class="hamburger-bar"></span>
      </button>
      <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-collapse"
      data-toggle="collapse">
        <i class="icon md-more" aria-hidden="true"></i>
      </button>
      <div class="navbar-brand navbar-brand-center site-gridmenu-toggle" data-toggle="gridmenu">
        <img class="navbar-brand-logo" src="/img/logo-small.png?v=1" title="FreeYourInbox">
        <span class="navbar-brand-text hidden-xs-down rubik"> freeyourinbox</span>
      </div>
      <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-search"
      data-toggle="collapse">
        <span class="sr-only">Toggle Search</span>
        <i class="icon md-search" aria-hidden="true"></i>
      </button>
    </div>
    <div class="navbar-container container-fluid">
      <!-- Navbar Collapse -->
      <div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">
        <!-- Navbar Toolbar -->
        <ul class="nav navbar-toolbar">
          <li class="nav-item hidden-float" id="toggleMenubar">
            <a class="nav-link" data-toggle="menubar" href="#" role="button">
              <i class="icon hamburger hamburger-arrow-left">
                  <span class="sr-only">Toggle menubar</span>
                  <span class="hamburger-bar"></span>
                </i>
            </a>
          </li>
        </ul>
        <!-- End Navbar Toolbar -->
        <!-- Navbar Toolbar Right -->
        <ul class="nav navbar-toolbar navbar-right navbar-toolbar-right">
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" data-animation="scale-up"
            aria-expanded="false" role="button">
              <span class="flag-icon flag-icon-gb"></span>
            </a>
            <div class="dropdown-menu" role="menu">
              <a class="dropdown-item" href="javascript:void(0)" role="menuitem">
                <span class="flag-icon flag-icon-fr"></span> French</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link navbar-avatar" data-toggle="dropdown" href="#" aria-expanded="false"
            data-animation="scale-up" role="button">
              <span class="avatar avatar-online">
                <img src="<?php echo $_SESSION['image']; ?>" alt="...">
                <i></i>
              </span>
            </a>
            <div class="dropdown-menu" role="menu">
              <a class="dropdown-item" href="javascript:void(0)" role="menuitem"><?php echo $_SESSION['name']; ?></a>
              <div class="dropdown-divider" role="presentation"></div>
              <a class="dropdown-item" href="https://freeyourinbox.com/FAQ.html" role="menuitem"><i class="icon md-info" aria-hidden="true"></i> FAQ</a>
              <a class="dropdown-item" href="javascript:void(0)" role="menuitem"><i class="icon md-settings" aria-hidden="true"></i> Settings</a>
              <div class="dropdown-divider" role="presentation"></div>
              <a class="dropdown-item" href="/logout.php" role="menuitem"><i class="icon md-power" aria-hidden="true"></i> Logout</a>
            </div>
          </li>
<?php
// check notifications
$sql = "SELECT `date`, `icon`, `color`, `text` FROM `notification` WHERE `user_id` = ".$_SESSION['userid']." ORDER BY `date` DESC";
$res = mysqli_query($conn, $sql);

$notifcount = mysqli_num_rows($res);
?>
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" title="Notifications"
            aria-expanded="false" data-animation="scale-up" role="button">
              <i class="icon md-notifications" aria-hidden="true"></i>
<?php
if ($notifcount > 0) {
	echo '
              <span class="badge badge-pill badge-danger up">'.$notifcount.'</span>';
} ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-media" role="menu">
              <div class="dropdown-menu-header">
                <h5>NOTIFICATIONS</h5>
<?php
if ($notifcount > 0) {
	echo '
                <span class="badge badge-round badge-danger">New '.$notifcount.'</span>';
} ?>
              </div>
              <div class="list-group">
                <div data-role="container">
                  <div data-role="content">
<?php

if ($notifcount > 0)
{
  while ($row = mysqli_fetch_assoc($res))
  {
  	echo '
                     <a class="list-group-item dropdown-item" href="javascript:void(0)" role="menuitem">
                      <div class="media">
                        <div class="pr-10">
                          <i class="icon md-'.$row['icon'].' bg-'.$row['color'].'-600 white icon-circle" aria-hidden="true"></i>
                        </div>
                        <div class="media-body">
                          <h6 class="media-heading">'.$row['text'].'</h6>
                          <time class="media-meta" datetime="'.date("c", $row['date']).'">'.formatdate($row['date']).'</time>
                        </div>
                      </div>
                    </a>';
  }
}
else
{
  echo '
                    <a class="list-group-item dropdown-item" href="javascript:void(0)" role="menuitem">
                      <div class="media">
                        <div class="media-body">
                          <h6 class="media-heading">You have no notifications</h6>
                        </div>
                      </div>
                    </a>';
}
?>

                  </div>
                </div>
              </div>
              <div class="dropdown-menu-footer">
                <a class="dropdown-menu-footer-btn" href="javascript:void(0)" role="button">
                  <i class="icon md-settings" aria-hidden="true"></i>
                </a>
                <a class="dropdown-item" href="javascript:void(0)" role="menuitem">
                    All notifications
                  </a>
              </div>
            </div>
          </li>
        </ul>
        <!-- End Navbar Toolbar Right -->
      </div>
      <!-- End Navbar Collapse -->
    </div>
  </nav>
  <div class="site-menubar">
    <div class="site-menubar-body">
      <div>
        <div>
          <ul class="site-menu" data-plugin="menu">
            <li class="site-menu-category">General</li>
<!--
            <li class="site-menu-item<?php if ($header['page'] == "dashboard") { echo " active"; } ?>">
              <a class="animsition-link" href="/dashboard.php">
                <i class="site-menu-icon md-view-dashboard" aria-hidden="true"></i>
                <span class="site-menu-title">Dashboard</span>
              </a>
            </li>
-->
            <li class="site-menu-item<?php if ($header['page'] == "newsletters") { echo " active"; } ?>">
              <a class="animsition-link" href="/newsletters">
                <i class="site-menu-icon md-email" aria-hidden="true"></i>
                <span class="site-menu-title">Newsletters</span>
              </a>
            </li>
            <li class="site-menu-item<?php if ($header['page'] == "allowed") { echo " active"; } ?>">
              <a class="animsition-link" href="/allowed">
                <i class="site-menu-icon md-check" aria-hidden="true"></i>
                <span class="site-menu-title">Allowed</span>
              </a>
            </li>
            <li class="site-menu-item<?php if ($header['page'] == "digest") { echo " active"; } ?>">
              <a class="animsition-link" href="/digest">
                <i class="site-menu-icon md-star" aria-hidden="true"></i>
                <span class="site-menu-title">Digest</span>
              </a>
            </li>
            <li class="site-menu-item<?php if ($header['page'] == "blacklist") { echo " active"; } ?>">
              <a class="animsition-link" href="/blacklist">
                <i class="site-menu-icon md-block" aria-hidden="true"></i>
                <span class="site-menu-title">Blacklist</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
