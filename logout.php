<?php
// Destroy session
session_start();
unset($_SESSION['access_token']);
header('location: /login');
exit;

// Load settings
require('lib/settings.php');

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn)
{
  die("Connection failed: " . mysqli_connect_error());
}

// Check login
require('lib/CheckLogin.php');

// Destroy everything
killsession();
?>
