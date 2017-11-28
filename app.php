<?php
session_start();
require_once __DIR__.'/lib/google.checklogin.php';
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>App | Free Your Inbox</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/owl.carousel.css">
  <link rel="stylesheet" href="css/owl.theme.default.css">
  <link rel="stylesheet" href="css/revillz.css?v=<?php echo time(); ?>">
<style>
.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;  /* Preferred icon size */
  display: inline-block;
  line-height: 1;
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;

  /* Support for all WebKit browsers. */
  -webkit-font-smoothing: antialiased;
  /* Support for Safari and Chrome. */
  text-rendering: optimizeLegibility;

  /* Support for Firefox. */
  -moz-osx-font-smoothing: grayscale;

  /* Support for IE. */
  font-feature-settings: 'liga';
}
</style>
</head>

<body>
  <template id="template">
  <header>
    <div class="header-brand">
      <div class="header-logo">
        <a href="#"><img class="brand-img" src="testPic/brand.png" alt=""></a>
        <ul class="header-nav">
          <li id="nNew" class="nav-item nav-active"><a href="#">{{ appPage.home }}</a></li>
          <li id="nBlacklist" class="nav-item"><a href="#">{{ appPage.blacklist }}</a></li>
          <li id="nWhitelist" class="nav-item"><a href="#">{{ appPage.whitelist }}</a></li>
          <li id="nDigest" class="nav-item"><a href="#">{{ appPage.digest }}</a></li>
        </ul>
      </div>
      <div class="header-user">
        <ul>
          <li class="more-nav"><i class="ion-android-more-vertical"></i></li>
          <!-- <li class="hidden-xs"><i id="setting" class="ion-android-settings"></i></li>
          <li class="hidden-xs"><i id="notif" class="ion-android-notifications"></i></li> -->
          <li><img id="user" class="user-img" src="<?php echo $_SESSION['image']; ?>" alt=""></li>
        </ul>
      </div>
    </div>
    <div class="alert-display">
      <ul>
        <li class="ion-ios-flask"></li>
        <li class="alert-display-text">Hi! Check me. I'm a cool inline alert box.</li>
      </ul>
    </div>
    <div class="header-navbar">
      <ul class="header-nav">
        <li id="nNew" class="nav-item nav-active"><a href="#">{{ appPage.home }}</a></li>
        <li id="nBlacklist" class="nav-item"><a href="#">{{ appPage.blacklist }}</a></li>
        <li id="nWhitelist" class="nav-item"><a href="#">{{ appPage.whitelist }}</a></li>
        <li id="nDigest" class="nav-item"><a href="#">{{ appPage.digest }}</a></li>
      </ul>
      <!-- <div class="webflow-style-input">
        <input id="seek" class="search" type="text" placeholder="Search..."></input>
        <button type="submit"><i class="icon ion-android-arrow-forward"></i></button>
      </div> -->
    </div>
  </header>
  <div class="content">
    <div id="loader" class="container">
      <h2>Processing</h2>
      <div class="progress-bar">
        <div class="progress" id="progress"></div>
      </div>
      <!-- <p>Yolo</p> -->
    </div>
    <div id="pNew" class="">
      <!-- CARDS -->
      <section class="cards-blocked">
        <div class="container">
          <h4 class="context-title">{{ cardType.blacklisted }}</h4>
        </div>
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-12">
              <div class="pre-filtering owl-carousel blocked">
              </div>
            </div>
          </div>
        </div>
        <div class="container">
          <hr>
        </div>
      </section>
      <section class="cards-allowed">
        <div class="container">
          <h4 class="context-title yolMobile">{{ cardType.whitelisted }}</h4>
        </div>
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-12">
              <div class="pre-filtering owl-carousel allowed">
              </div>
            </div>
          </div>
        </div>
        <div class="container">
          <hr>
        </div>
      </section>
      <section class="cards-new">
        <div class="container">
          <h4 class="context-title yolMobile">Train us</h4>
        </div>
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-12">
              <div class="pre-filtering owl-carousel new">
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="modal-card-overlay"></div>
      <div class="modal-card-content">
        <ul id="modalGoWhite">
          <li class="ion-checkmark-circled"></li>
          <li>Whitelist</li>
        </ul>
        <hr>
        <ul id="modalGoBlack">
          <li class="ion-close-circled"></li>
          <li>Blacklist</li>
        </ul>
        <hr>
        <ul id="modalGoDigest">
          <li class="ion-ios-list"></li>
          <li>Digest</li>
        </ul>
      </div>
    </div>

    <div id="pBlacklist" class="container">
      <h4 class="context-title">{{ appPage.blacklist }}</h4>
    </div>

    <div id="pWhitelist" class="container">
      <h4 class="context-title">{{ appPage.whitelist }}</h4>
    </div>

    <div id="pDigest" class="container">
      <h4 class="context-title">{{ appPage.digest }}</h4>

      <div class="modal-action-overlay"></div>
      <div class="modal-action-content">
        <ul class="">
          <li class="ion-checkmark-circled"></li>
          <li>{{ appPage.whitelist }}</li>
        </ul>
        <ul class="">
          <li class="ion-close-circled"></li>
          <li>{{ appPage.blacklist }}</li>
        </ul>
        <ul class="">
          <li class="ion-ios-list"></li>
          <li>{{ appPage.digest }}</li>
        </ul>
      </div>

    </div>
  </div>
  <div class="settings-pane card-shadow">
    <h4>Settings</h4>
    <div>
      <ul>
        <li>Do Something</li>
        <li>
          <label class="form-switch">
            <input type="checkbox" checked>
            <i></i>
          </label>
        </li>
      </ul>
    </div>
    <div>
      <ul>
        <li>Something Else</li>
        <li>
          <label class="form-switch">
            <input type="checkbox">
            <i></i>
          </label>
        </li>
      </ul>
    </div>
    <div>
      <ul>
        <li>Allow Notification</li>
        <li>
          <label class="form-switch">
            <input type="checkbox" checked>
            <i></i>
          </label>
        </li>
      </ul>
    </div>
    <hr>
    <div class="set-select">
      Filter Experience
      <div class="select select--white">
        <span class="placeholder">Default</span>
        <ul>
          <li data-value="es">Default</li>
          <li data-value="en">Full Control</li>
          <li data-value="fr">Inteligent</li>
          <li data-value="de">User Control</li>
        </ul>
        <input type="hidden" name="changemetoo" />
      </div>
      <div class="select select--white">
        <span class="placeholder">en - US</span>
        <ul>
          <li data-value="es">en - US</li>
          <li data-value="en">fr - FR</li>
          <li data-value="fr">es - ES</li>
          <li data-value="de">es - AS</li>
        </ul>
        <input type="hidden" name="changemetoo" />
      </div>
    </div>


  </div>

  <div class="notification-pane card-shadow">
    <h4>Notification</h4>
    <div class="notif">
      <div class="notif-icon">
        <i class="ion-speedometer"></i>
      </div>
      <div class="notif-content">
        <h5>Time Saved</h5>
        <p>Congratulation, since 2017 y...</p>
      </div>
      <span>24/10/2017</span>
    </div>
    <div class="notif">
      <div class="notif-icon">
        <i class="ion-speedometer"></i>
      </div>
      <div class="notif-content">
        <h5>Time Saved</h5>
        <p>Congratulation, since 2017 y...</p>
      </div>
      <span>24/10/2017</span>
    </div>
    <div class="notif">
      <div class="notif-icon">
        <i class="ion-speedometer"></i>
      </div>
      <div class="notif-content">
        <h5>Time Saved</h5>
        <p>Congratulation, since 2017 y...</p>
      </div>
      <span>24/10/2017</span>
    </div>
  </div>

  <div class="user-pane card-shadow">
    <h4><?php echo $_SESSION['name']; ?></h4>
    <div>
      <ul>
        <li><a href="/logout" onClick="location.href='/logout'">{{ userAction.logout }}</a></li>
      </ul>
    </div>
  </div>

  <div class="xs-nav">
    <ul class="xs-item">
      <li id="nNew" class="nav-item"><a href="#">{{ appPage.home }}</a></li>
      <li id="nBlacklist" class="nav-item"><a href="#">{{ appPage.blacklist }}</a></li>
      <li id="nWhitelist" class="nav-item"><a href="#">{{ appPage.whitelist }}</a></li>
      <li id="nDigest" class="nav-item"><a href="#">{{ appPage.digest }}</a></li>
    </ul>
  </div>
  </template>
  <script src="js/jquery.min.js" charset="utf-8"></script>
  <script src="js/revillz.js?v=<?php echo time(); ?>" charset="utf-8"></script>
  <script src="js/translate.js" charset="utf-8"></script>
  <script src="js/color-thief.js" charset="utf-8"></script>
  <script src="js/owl.carousel.js" charset="utf-8"></script> 
</body>

</html>
