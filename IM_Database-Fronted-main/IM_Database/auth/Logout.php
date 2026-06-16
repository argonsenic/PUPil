<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

session_start();

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../login.html');
exit();
