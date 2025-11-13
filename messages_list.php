<?php
/**
 * DEPRECATED: This file is deprecated and redirects to messages.php
 * Use messages.php for the unified messaging interface
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session-init.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Redirect to unified messages interface
header('Location: messages.php');
exit;