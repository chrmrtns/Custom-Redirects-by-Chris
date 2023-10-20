<?php
/*
Plugin Name: Custom Redirects by Chris
Plugin URI: https://support.christian-martens.com
Description: Plugin for Custom Redirects
Author: Chris Martens
Author URI: https://chris-martens.com
Version: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-redirects-by-chris
*/

/* Prohibit direct access to the plugin file */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include functions from the 'includes' directory
require plugin_dir_path(__FILE__) . 'includes/functions.php';
require plugin_dir_path(__FILE__) . 'includes/constants.php';


// Function to render existing redirects
function chrmrtns_render_existing_redirects($redirects) {
    $output = '<h3>' . esc_html__('Existing Redirects', 'custom-redirects-by-chris') . '</h3>';
    $output .= '<p>' . sprintf(esc_html__('To edit, just modify the redirect and hit "%s".', 'custom-redirects-by-chris'), esc_html__('Save', 'custom-redirects-by-chris')) . '</p>';    
    $output .= '<div class="chrmrtns-custom-redirects-table">';

    foreach ($redirects as $redirect) {
        $output .= '<div class="chrmrtns-row">';
        $output .= '<div class="chrmrtns-cell">';
        $output .= '<form method="post" style="display:inline;">'; 
        $output .= '<input type="hidden" name="id" value="' . intval($redirect->id) . '">';
        $output .= '<input type="text" name="slug" value="' . esc_attr($redirect->slug) . '">';
        $output .= '<input type="text" name="target" value="' . esc_attr($redirect->target) . '">';
        $output .= '<input type="submit" name="edit" value="' . esc_attr__('Save', 'custom-redirects-by-chris') . '" class="button-secondary">';
        $output .= '</form>';
        $output .= '&nbsp;'; // Space between forms
        $output .= '<form method="post" style="display:inline;">'; 
        $output .= '<input type="hidden" name="id" value="' . intval($redirect->id) . '">';
        $output .= '<input type="submit" name="delete" value="' . esc_attr__('Delete', 'custom-redirects-by-chris') . '" class="button-secondary" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this redirect?', 'custom-redirects-by-chris')) . '\');">';
        $output .= '</form>';
        $output .= '</div>'; // Close chrmrtns-cell
        $output .= '</div>'; // Close chrmrtns-row
    }

    $output .= '</div>'; // Close chrmrtns-custom-redirects-table

    echo $output;
}

// Translation ready
function chrmrtns_load_textdomain() {
    load_plugin_textdomain('custom-redirects-by-chris', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'chrmrtns_load_textdomain');











