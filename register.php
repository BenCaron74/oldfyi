<?php
// Load settings
require("lib/settings.php");
require("lib/PassHash.php");
require("lib/password.php");

if (isset($_POST['email']))
{
 // TODO: check email validity and password complexity!

 if ($_POST['password'] != $_POST['password2'])
 {
  $error = "Passwords don't match!";
 }
 else
 {
  // Create connection
  $conn = mysqli_connect($servername, $username, $password, $database);

  // Check connection
  if (!$conn) {
      die("FATAL: Connection failed: ".mysqli_connect_error())."\n\n";
  }

  // Get password
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $pass = $_POST['password'];

  // hash and salt password and encode it to base64
  $hash = password_hash($pass, PASSWORD_DEFAULT, array("cost" => 10));

  // encrypt password with key
  $encrypted = mycrypt($hash);

  // store user as inactive
  $sql = "INSERT INTO `user`(`email`, `password`, `rank`) VALUES('$email', '$encrypted', 1)";
  $result = mysqli_query($conn, $sql);

  if ($result !== false)
  {
    header('location: /login');
  }
  else
  {
    $error = mysqli_error($conn);
    if (preg_match('/duplicate entry/i', $error)) {
      $error = "This user already exists!";
    }
    $error = "Something went wrong:<br>".$error;
  }
 }
}

$header['css'] = "forms";
$header['title'] = "Sign Up";
include('headers.php');
?>
    <div class="container" role="main">

<?php
if (isset($error)) {
  echo '      <div class="alert alert-danger" role="alert"><strong>'.$error.'</strong></div>';
}
?>
      <form class="form-signin" method="post" action="register.php">
        <h2 class="form-signin-heading">Register form</h2>
        <label for="inputEmail" class="sr-only">Email address</label>
        <input type="email" name="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
        <br><label for="inputPassword" class="sr-only">Password</label>
        <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
        <label for="inputPassword2" class="sr-only">Re-type password</label>
        <input type="password" name="password2" id="inputPassword2" class="form-control" placeholder="Re-type password" required>
        <br><button class="btn btn-lg btn-primary btn-block" type="submit">Register</button>
      </form>

    </div> <!-- /container -->

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="../../assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>
