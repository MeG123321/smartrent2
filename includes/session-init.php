<?php
/**
 * Standardized session initialization
 * Include this file at the top of any file that needs session access
 */

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
