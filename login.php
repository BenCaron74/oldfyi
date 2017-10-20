<?php
/*
session_start();
if (isset($_SESSION['access_token']) && is_array($_SESSION['access_token'])) { header('location: /newsletters'); }
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="index,follow">
  <meta name="description" content="Free Your Inbox | Supprimez vos newsletters en 1 clic">
  <meta name="author" content="freeyourinbox">

  <title>Login | Free Your Inbox</title>

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

  <link rel="stylesheet" href="css/animate.css">
  <link rel="stylesheet" href="css/bootstrap3.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/master.css">
</head>

<body>
  <div class="info-modal wow bounceInDown" data-wow-delay="1s">
    <span class="ion-ios-information"></span>
    <p>Beta only available with Gmail</p>
  </div>
  <div class="container login-page">
  	<a href="https://freeyourinbox.com" title="FreeYourInbox">
      <img src="img/fyi_blue.png" class="img-responsive login-logo">
    </a>
    <div>
      <h1 class="login-title">Congratulations,</h1>
      <h3 class="login-subtitle">You can now join our beta test program!</h3>
    </div>

    <div class="col-md-6 col-md-offset-3 wow fadeInUp">
      <div class="login-panel">
        <div id="gmail" class="login-gmail"><img src="img/icon/google.png" class="img-responsive">
          <p>Login with Gmail</p>
        </div>
        <div id="outlook" class="login-outlook"><img src="img/icon/outlook.png" class="img-responsive">
          <p>Login with Outlook</p>
        </div>
        <div id="yahoo" class="login-yahoo"><img src="img/icon/yahoo.png" class="img-responsive">
          <p>Login with Yahoo</p>
        </div>
        <div id="other" class="login-other">Login with an other method</div>
        <div class="loader">
          <svg version="1.1" id="L5" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
            <circle fill="#1e88e5" stroke="none" cx="30" cy="50" r="6">
              <animateTransform
                 attributeName="transform"
                 dur="1s"
                 type="translate"
                 values="0 15 ; 0 -15; 0 15"
                 repeatCount="indefinite"
                 begin="0.1"/>
            </circle>
            <circle fill="#1e88e5" stroke="none" cx="50" cy="50" r="6">
              <animateTransform
                 attributeName="transform"
                 dur="1s"
                 type="translate"
                 values="0 10 ; 0 -10; 0 10"
                 repeatCount="indefinite"
                 begin="0.2"/>
            </circle>
            <circle fill="#1e88e5" stroke="none" cx="70" cy="50" r="6">
              <animateTransform
                 attributeName="transform"
                 dur="1s"
                 type="translate"
                 values="0 5 ; 0 -5; 0 5"
                 repeatCount="indefinite"
                 begin="0.3"/>
            </circle>
          </svg>
        </div>
      </div>
    </div>

  </div>
  <script src="js/jquery.min.js" charset="utf-8"></script>
  <script src="js/bootstrap.min.js" charset="utf-8"></script>
  <script src="js/wow.js" charset="utf-8"></script>
  <script src="js/app.js?v=2" charset="utf-8"></script>
</body>

</html>
