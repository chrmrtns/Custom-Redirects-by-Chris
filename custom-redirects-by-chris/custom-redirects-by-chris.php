<?php
/*
Plugin Name: Custom Redirects by Chris
Plugin URI: https://support.christian-martens.com
Description: Plugin for Custom Redirects
Author: Chris Martens
Author URI: https://chris-martens.com
Version: 1.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-redirects-by-chris
*/

/* Prohibit direct access to the plugin file */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include functions from the 'includes' directory
require plugin_dir_path(__FILE__) . 'includes/functions.php';
require plugin_dir_path(__FILE__) . 'includes/constants.php';

// Register the activation hook
register_activation_hook(__FILE__, 'chrmrtnsCRD_custom_redirects_create_table');

// Function to render existing redirects with an Edit button
function chrmrtnsCRD_render_existing_redirects($redirects) {
    if (!empty($redirects)) {
        echo '<h2>Existing Redirects</h2>';
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Page Slug', 'custom-redirects-by-chris') . '</th>';
        echo '<th>' . esc_html__('Target URL', 'custom-redirects-by-chris') . '</th>';
        echo '<th>' . esc_html__('Actions', 'custom-redirects-by-chris') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($redirects as $redirect) {
            echo '<tr>';
            echo '<form method="post">';
            echo '<td>' . esc_html($redirect->slug) . '</td>';
            echo '<td><input type="text" name="target" value="' . esc_attr($redirect->target) . '"></td>';
            echo '<input type="hidden" name="slug" value="' . esc_attr($redirect->slug) . '">'; // Hidden input for slug
            echo '<td>';
            echo '<input type="hidden" name="id" value="' . esc_attr($redirect->id) . '">';
            echo '<input type="submit" name="edit" class="button" value="' . esc_attr__('Update', 'custom-redirects-by-chris') . '">';
            echo '<input type="submit" name="delete" class="button" value="' . esc_attr__('Delete', 'custom-redirects-by-chris') . '">';
            echo '</td>';
            echo '</form>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}

// Translation ready
function chrmrtnsCRD_load_textdomain() {
    load_plugin_textdomain('custom-redirects-by-chris', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'chrmrtnsCRD_load_textdomain');











