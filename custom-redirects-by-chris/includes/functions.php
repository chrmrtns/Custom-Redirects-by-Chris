<?php

/** On installation and deinstallation */
// Define the custom database table creation function
function chrmrtns_custom_redirects_create_table() {
    global $wpdb;
    $table_name = chrmrtns_get_table_name();

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        slug varchar(255) NOT NULL,
        target varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check for errors
    if (!empty($wpdb->last_error)) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Display an error message and exit
        wp_die(sprintf(esc_html__('Error creating custom redirects table: %s', 'custom-redirects-by-chris'), esc_html($wpdb->last_error)));
    }
}
// Hook the table creation function to plugin activation
register_activation_hook(__FILE__,'chrmrtns_custom_redirects_create_table');


// Hook to delete the custom table when the plugin is deleted
function chrmrtns_custom_redirects_uninstall() {
    global $wpdb;
    $table_name = chrmrtns_get_table_name();

    // Correctly prepare the SQL statement
    $sql = "DROP TABLE IF EXISTS `$table_name`";
    $wpdb->query($sql);
}
register_uninstall_hook(__FILE__,'chrmrtns_custom_redirects_uninstall');

// Define the plugin settings page
function chrmrtns_custom_redirects_menu() {
    add_submenu_page(
        'tools.php', // Parent menu (Tools)
        'Custom Redirects', // Page title
        'Custom Redirects', // Menu title
        'manage_options', // Capability required to access
        'custom-redirects', // Menu slug
        'chrmrtns_custom_redirects_page' // Callback function for the page content
    );
}
add_action('admin_menu', 'chrmrtns_custom_redirects_menu');



/** Settings Page */
// Callback function for the settings page
function chrmrtns_custom_redirects_page() {
    global $wpdb;
    $table_name = chrmrtns_get_table_name();

    // Handle form submission
    if (isset($_POST['submit'])) {
        echo chrmrtns_process_redirect();
    }

    // Handle form submission for editing existing redirect
    if (isset($_POST['edit'])) {
        $id = intval($_POST['id']);
        echo chrmrtns_process_redirect($id);
    }


    // Handle form submission for deleting existing redirect
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        if ($id <= 0) {
            echo '<div class="error"><p>' . esc_html__('Invalid ID for deletion.', 'custom-redirects-by-chris') . '</p></div>';
        } else {
            $deleted = $wpdb->delete($table_name, array('id' => $id));

            if ($deleted !== false) {
                echo '<div class="updated"><p>' . esc_html__('Redirect deleted.', 'custom-redirects-by-chris') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__( 'Failed to delete redirect. Please try again.', 'custom-redirects-by-chris' ) . '</p></div>';
            }
        }
    }

    // Retrieve the saved redirects from the custom table
    $redirects = $wpdb->get_results("SELECT * FROM $table_name");

     // Display the settings form
     ?>
     <div class="wrap chrmrtns-custom-redirects-wrap">
         <h2>Custom Redirects</h2>
         <form method="post">
             <table class="form-table">
                 <tr valign="top">
                     <th scope="row"><?php echo esc_html__( 'Page Slug', 'custom-redirects-by-chris' ); ?></th>
                     <td><input type="text" name="slug" value="">
                     <p class="description"><?php echo esc_html__( "Please include the leading '/' before your page slug. For example: \"/mypage\".", 'custom-redirects-by-chris' ); ?></p>
                 </td>
                 </tr>
                 <tr valign="top">
                     <th scope="row"><?php echo esc_html__( 'Target URL', 'custom-redirects-by-chris' ); ?></th>
                     <td><input type="text" name="target" value=""></td>
                 </tr>
             </table>
             <p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__( 'Save Changes', 'custom-redirects-by-chris' ); ?>"></p>
         </form>
     <?php
 
     // Render the existing redirects
     chrmrtns_render_existing_redirects($redirects);
 }


 /** Redirection */
// Define the redirection logic
function chrmrtns_custom_page_redirect() {
    global $wpdb;
    $table_name = $wpdb->prefix . CHRMRTNS_CUSTOM_REDIRECTS_TABLE;

    // Get the current requested URL without query string
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');

    // Remove trailing slash for comparison
    $current_url = rtrim($current_url, '/');

    // Check if there's a matching redirect in the custom table
    $redirects = $wpdb->get_results("SELECT * FROM $table_name");

    foreach ($redirects as $redirect) {
        // Remove trailing slash from stored slug for comparison
        $stored_slug = rtrim($redirect->slug, '/');

        if ($current_url === $stored_slug) {
            $target_url = esc_url($redirect->target);

            // Perform a 301 redirect
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $target_url");
            exit();
        }
    }
}
add_action('template_redirect', 'chrmrtns_custom_page_redirect');


/**  Plugin related*/

// Function to add the settings link
function chrmrtns_add_settings_link($links) {
    $settings_link = '<a href="tools.php?page=custom-redirects">' . __('Settings', 'custom-redirects-by-chris') . '</a>';
    array_unshift($links, $settings_link); // Add the settings link to the beginning of the array
    return $links;
}

// Redefine the plugin basename
$plugin_basename = plugin_basename(plugin_dir_path( dirname( __FILE__ ) ) . 'custom-redirects-by-chris.php');
add_filter('plugin_action_links_' . $plugin_basename, 'chrmrtns_add_settings_link');


function chrmrtns_enqueue_admin_styles() {
    // Check if we're on the 'Custom Redirects' page
    $current_screen = get_current_screen();
    if ($current_screen->base == 'tools_page_custom-redirects') {
        // Enqueue the CSS file
        wp_enqueue_style('chrmrtns-custom-redirects-style', plugin_dir_url(plugin_dir_path( dirname( __FILE__ ) ) . 'custom-redirects-by-chris.php') . 'css/style.css');
    }
}

add_action('admin_enqueue_scripts', 'chrmrtns_enqueue_admin_styles');

/** Database related checks and updates */

// Utility function to get the custom redirects table name
function chrmrtns_get_table_name() {
    global $wpdb;
    return $wpdb->prefix . CHRMRTNS_CUSTOM_REDIRECTS_TABLE;
}

// Check if slug exists
function chrmrtns_slug_exists($slug) {
    global $wpdb;
    $table_name = chrmrtns_get_table_name();

    $existing_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $table_name WHERE slug = %s", $slug));

    return ($existing_slug) ? true : false;
}
// Check for valid URL
function isValidURL($url) {
    // Check if it's a valid URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check for a valid scheme and host
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }

    // Check if the scheme is in a list of allowed schemes
    $valid_schemes = array('http', 'https', 'ftp', 'ftps');  // Add any other schemes you want to allow
    if (!in_array($parts['scheme'], $valid_schemes)) {
        return false;
    }

    return true;
}

//  Check valid domain
function hasValidDomain($url) {
    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) {
        return false;
    }
    
    // Check if the host part of the URL has a valid domain format
    return preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $parts['host']) //valid chars check
            && preg_match('/^.{1,253}$/', $parts['host']) //overall length check
            && preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $parts['host']); //length of each label
}

// Separate function for validation and processing
function chrmrtns_process_redirect($id = null) {
    global $wpdb;
    $table_name = chrmrtns_get_table_name();

    $slug = sanitize_text_field($_POST['slug']);
    $target = esc_url_raw($_POST['target']);

    // Validate if the target is a valid URL
    if (!filter_var($target, FILTER_VALIDATE_URL)) {
        return '<div class="error"><p>' . esc_html__( 'The target URL is not valid. Please enter a valid URL.', 'custom-redirects-by-chris' ) . '</p></div>';
    }
    // Additional validation for slug
    elseif (strpos($slug, '/') !== 0) {
        return '<div class="error"><p>' . esc_html__( 'Slug should start with a "/".', 'custom-redirects-by-chris' ) . '</p></div>';
    } elseif (empty($slug) || empty($target)) {
        return '<div class="error"><p>' . esc_html__( 'Both slug and target URL are required.', 'custom-redirects-by-chris' ) . '</p></div>';
    } else {
        if ($id) {
            $updated = $wpdb->update(
                $table_name,
                array('slug' => $slug, 'target' => $target),
                array('id' => $id)
            );
            if ($updated !== false) {
                return '<div class="updated"><p>' . esc_html__( 'Redirect updated.', 'custom-redirects-by-chris' ) . '</p></div>';
            } else {
                return '<div class="error"><p>' . esc_html__( 'Failed to update redirect. Please try again.', 'custom-redirects-by-chris' ) . '</p></div>';
            }
        } else {
            $inserted = $wpdb->insert(
                $table_name,
                array('slug' => $slug, 'target' => $target),
                array('%s', '%s')
            );
            if ($inserted !== false) {
                return '<div class="updated"><p>' . esc_html__( 'Redirect saved.', 'custom-redirects-by-chris' ) . '</p></div>';
            } else {
                return '<div class="error"><p>' . esc_html__( 'Failed to save redirect. Please try again.', 'custom-redirects-by-chris' ) . '</p></div>';
            }            
        }
    }
}


// Added Meta for Post and Page

// Add meta box to post and page edit screens
function chrmrtns_add_redirect_meta_box() {
    add_meta_box(
        'chrmrtns_redirect_meta_box',
        'Custom Redirect',
        'chrmrtns_render_redirect_meta_box',
        ['post', 'page'],
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'chrmrtns_add_redirect_meta_box');

// Render the meta box content
function chrmrtns_render_redirect_meta_box($post) {
    // Get existing redirect target if it exists
    global $wpdb;
    $table_name = chrmrtns_get_table_name();
    $slug = wp_make_link_relative(get_permalink($post->ID)); // Get relative URL
    $existing_redirect = $wpdb->get_var($wpdb->prepare("SELECT target FROM $table_name WHERE slug = %s", $slug));

    echo '<label for="chrmrtns_redirect_target">' . esc_html__( 'Redirect Target:', 'custom-redirects-by-chris' ) . '</label>';
    echo '<input type="text" id="chrmrtns_redirect_target" name="chrmrtns_redirect_target" value="' . esc_attr($existing_redirect) . '" style="width: 100%;">';
    echo '<p class="description">' . esc_html__( 'Enter the target URL to redirect this post/page to.', 'custom-redirects-by-chris' ) . '</p>';
    
    // Checkbox to delete the redirect
    echo '<p>';
    echo '<input type="checkbox" id="chrmrtns_delete_redirect" name="chrmrtns_delete_redirect" value="1">';
    echo '<label for="chrmrtns_delete_redirect">' . esc_html__( 'Delete Redirect', 'custom-redirects-by-chris' ) . '</label>';    
    echo '</p>';
}

// Save the redirect when the post or page is saved
function chrmrtns_save_redirect($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    global $wpdb;
    $table_name = chrmrtns_get_table_name();
    $slug = wp_make_link_relative(get_permalink($post_id)); // Get relative URL

    // If the delete checkbox is checked, delete the redirect
    if (isset($_POST['chrmrtns_delete_redirect']) && $_POST['chrmrtns_delete_redirect'] == '1') {
        $wpdb->delete($table_name, ['slug' => $slug]);
        return; // Exit the function after deleting
    }


    if (isset($_POST['chrmrtns_redirect_target'])) {
        $target = sanitize_text_field($_POST['chrmrtns_redirect_target']);

        // Check if a redirect already exists for this slug
        $existing_redirect = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE slug = %s", $slug));

        if ($existing_redirect) {
            // Update existing redirect
            $wpdb->update(
                $table_name,
                ['target' => $target],
                ['id' => $existing_redirect]
            );
        } else {
            // Insert new redirect
            $wpdb->insert(
                $table_name,
                [
                    'slug' => $slug,
                    'target' => $target
                ]
            );
        }
    }
}
add_action('save_post', 'chrmrtns_save_redirect');
