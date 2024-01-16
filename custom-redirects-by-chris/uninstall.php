<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Include functions file
include_once 'includes/functions.php';
include_once 'includes/constants.php';

// Call the uninstall function
chrmrtnsCRD_custom_redirects_uninstall();

