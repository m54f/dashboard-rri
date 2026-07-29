<?php
/**
 * pages/auth/logout.php
 * Hancurkan session dan redirect ke dashboard dalam mode Open.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

// Redirect ke dashboard (Open mode)
header('Location: ../dashboard.php');
exit;
