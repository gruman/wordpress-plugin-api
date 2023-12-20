<?php
/*
Plugin Name: Random Name
Description: Displays a greeting message above or below the title. Settings available under "Random" menu.
Version: 1.0
Author: Matthew Gruman
*/

// Function to get the random name from the API
function get_random_name() {
    $api_url = 'https://random-generators.vercel.app/names';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log('Error fetching random name: ' . $response->get_error_message());
        return 'Guest';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (!$data || empty($data->name)) {
        error_log('Invalid or empty response from the API.');
        return 'Guest';
    }

    return $data->name;
}

// Function to display the greeting message based on settings
function display_hello_message($content) {
    $greeting_position = get_option('random_name_greeting_position', 'bottom'); // Default is 'bottom'

    if (is_single() || is_front_page()) {
        $name = get_random_name();
        $message = 'Hello ' . esc_html($name) . '!';

        if ($greeting_position === 'top') {
            $content = '<p>' . $message . '</p>' . $content;
        } else {
            $content .= '<p>' . $message . '</p>';
        }
    }

    return $content;
}

// Function to add settings page under "Random" menu
function add_random_name_settings_page() {
    add_menu_page(
        'Random Name Settings',
        'Random',
        'manage_options',
        'random-name-settings',
        'random_name_settings_page',
        'dashicons-randomize',
        30
    );
}

// Function to render the settings page
function random_name_settings_page() {
    ?>
    <div class="wrap">
        <h1>Random Name Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('random_name_settings_group'); ?>
            <?php do_settings_sections('random-name-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Function to register settings and fields
function random_name_register_settings() {
    register_setting('random_name_settings_group', 'random_name_greeting_position', array('sanitize_callback' => 'sanitize_text_field'));

    add_settings_section('random_name_settings_section', 'Greeting Position', '', 'random-name-settings');

    add_settings_field(
        'random_name_greeting_position',
        'Choose the position:',
        'random_name_render_greeting_position_field',
        'random-name-settings',
        'random_name_settings_section'
    );
}

// Function to render the radio button for greeting position
function random_name_render_greeting_position_field() {
    $greeting_position = get_option('random_name_greeting_position', 'bottom');
    ?>
    <label>
        <input type="radio" name="random_name_greeting_position" value="top" <?php checked('top', $greeting_position); ?>>
        Display at the top
    </label>
    <br>
    <label>
        <input type="radio" name="random_name_greeting_position" value="bottom" <?php checked('bottom', $greeting_position); ?>>
        Display at the bottom
    </label>
    <?php
}

// Hook to add menu item and settings page
add_action('admin_menu', 'add_random_name_settings_page');
add_action('admin_init', 'random_name_register_settings');

// Hook to display the greeting message
add_filter('the_content', 'display_hello_message');
