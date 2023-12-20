<?php
/*
Plugin Name: Backgammon Club
Description: Backgammon club management
Version: 1.0
Author: Matthew Gruman
*/
// Function to create the database table on plugin activation

function elo_display_table_shortcode()
{
    ob_start(); // Start output buffering

    // Display the player list
    elo_display_player_list();

    $content = ob_get_clean(); // Get the buffered content

    return $content;
}

// Register the shortcode
add_shortcode('elo_table', 'elo_display_table_shortcode');
function elo_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_players';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        elo int NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to create the database table on plugin activation
register_activation_hook(__FILE__, 'elo_create_table');

// Function to add menu item under "Elo" with a custom icon
function add_elo_menu_item()
{
    add_menu_page(
        'Elo Players',
        'Elo',
        'manage_options',
        'elo-menu',
        'elo_menu_page',
        'dashicons-media-spreadsheet',
        30
    );
}

// Hook to add menu item
add_action('admin_menu', 'add_elo_menu_item');

// Function to render the menu page
function elo_menu_page()
{
    ?>
    <div class="wrap">
        <h1>Elo Players</h1>
        <?php
        // Handle form submissions for adding/editing players
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            elo_handle_form_submission();
        }

        // Display the player list and add/edit form
        elo_display_player_list();
        elo_display_match_form(); // Display the match form
        elo_display_player_form();
        ?>
    </div>
    <?php
}
function elo_display_match_form()
{
    global $wpdb;

    // Get all players for dropdown
    $all_players = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}elo_players", ARRAY_A);
    ?>
    <h2>Record Match Result</h2>
    <form method="post" action="?page=elo-menu&action=record-match">
        <label for="elo_winner">Winner:</label>
        <select id="elo_winner" name="elo_winner">
            <?php
            foreach ($all_players as $player) {
                echo '<option value="' . esc_attr($player['id']) . '">' . esc_html($player['name']) . '</option>';
            }
            ?>
        </select>

        <label for="elo_loser">Loser:</label>
        <select id="elo_loser" name="elo_loser">
            <?php
            foreach ($all_players as $player) {
                echo '<option value="' . esc_attr($player['id']) . '">' . esc_html($player['name']) . '</option>';
            }
            ?>
        </select>

        <input type="submit" name="elo_submit_match" class="button button-primary" value="Record Match Result">
    </form>
    <?php
}


function elo_handle_form_submission()
{
    global $wpdb;

    if (isset($_POST['elo_submit'])) {
        // Handle player form submission
        $player_id = isset($_POST['elo_player_id']) ? intval($_POST['elo_player_id']) : 0;
        $name = sanitize_text_field($_POST['elo_player_name']);
        $elo = intval($_POST['elo_player_elo']);

        if ($player_id) {
            // Update existing player
            $wpdb->update(
                $wpdb->prefix . 'elo_players',
                array('name' => $name, 'elo' => $elo),
                array('id' => $player_id),
                array('%s', '%d'),
                array('%d')
            );

            echo '<div class="updated"><p>Player updated successfully!</p></div>';
        } else {
            // Insert new player
            $wpdb->insert(
                $wpdb->prefix . 'elo_players',
                array('name' => $name, 'elo' => $elo),
                array('%s', '%d')
            );

            echo '<div class="updated"><p>New player added successfully!</p></div>';
        }


    } elseif (isset($_POST['elo_submit_match'])) {
        $winner_id = intval($_POST['elo_winner']);
        $loser_id = intval($_POST['elo_loser']);

        // Call the function to update Elo scores
        elo_update_elo_scores($winner_id, $loser_id);

    } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['player_id'])) {
        error_log("Here");
        // Handle player deletion
        $player_id = intval($_GET['player_id']);

        // Delete player from the database
        $wpdb->delete(
            $wpdb->prefix . 'elo_players',
            array('id' => $player_id),
            array('%d')
        );

        echo '<div class="updated"><p>Player deleted successfully!</p></div>';
    }
}



// Function to handle form submissions for adding/editing players

// Function to display the player list
function elo_display_player_list()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_players';

    $players = $wpdb->get_results("SELECT * FROM $table_name ORDER BY elo DESC", ARRAY_A);

    if ($players) {
        if (is_admin()) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th style="text-align: left">ID</th><th style="text-align: left">Name</th><th style="text-align: left">Elo</th><th>Action</th></tr></thead>';
            echo '<tbody>';
            foreach ($players as $player) {
                echo '<tr>';
                echo '<td>' . esc_html($player['id']) . '</td>';
                echo '<td>' . esc_html($player['name']) . '</td>';
                echo '<td>' . esc_html($player['elo']) . '</td>';
                // Check if the current user can manage options (admin)
                echo '<td>';
                echo '<a href="?page=elo-menu&action=edit&player_id=' . esc_attr($player['id']) . '">Edit</a> | ';
                echo '<a href="?page=elo-menu&action=delete&player_id=' . esc_attr($player['id']) . '" onclick="return confirm(\'Are you sure you want to delete this player?\')">Delete</a>';
                echo '</td>';
            }
            echo '</tr>';


            echo '</tbody>';
            echo '</table>';
        } else {

            echo '<table style="width: 100%">';
            echo '<thead><tr><th  style="text-align: left">Name</th><th  style="text-align: left">Elo</th></tr></thead>';
            echo '<tbody>';
            foreach ($players as $player) {
                echo '<tr>';
                echo '<td>' . esc_html($player['name']) . '</td>';
                echo '<td>' . esc_html($player['elo']) . '</td>';

            }
            echo '</tr>';


            echo '</tbody>';
            echo '</table>';
        }
    } else {
        echo '<p>No players found.</p>';
    }
}
// Function to display the add/edit player form
function elo_display_player_form()
{
    global $wpdb;

    $player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;

    if ($player_id) {
        // Get player details for editing
        $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}elo_players WHERE id = %d", $player_id), ARRAY_A);
        $name = $player['name'];
        $elo = $player['elo'];
    } else {
        // Set default values for a new player
        $name = '';
        $elo = 0;
    }
    ?>
    <h2>
        <?php echo $player_id ? 'Edit Player' : 'Add New Player'; ?>
    </h2>
    <form method="post" action="?page=elo-menu">
        <input type="hidden" name="elo_player_id" value="<?php echo esc_attr($player_id); ?>">
        <label for="elo_player_name">Name:</label>
        <input type="text" id="elo_player_name" name="elo_player_name" value="<?php echo esc_attr($name); ?>" required>
        <label for="elo_player_elo">Elo:</label>
        <input type="number" id="elo_player_elo" name="elo_player_elo" value="<?php echo esc_attr($elo); ?>" required>
        <input type="submit" name="elo_submit" class="button button-primary"
            value="<?php echo $player_id ? 'Update Player' : 'Add Player'; ?>">
    </form>
    <?php
}

function elo_update_elo_scores($winner_id, $loser_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_players';

    // Get current Elo scores
    $winner_elo = $wpdb->get_var($wpdb->prepare("SELECT elo FROM $table_name WHERE id = %d", $winner_id));
    $loser_elo = $wpdb->get_var($wpdb->prepare("SELECT elo FROM $table_name WHERE id = %d", $loser_id));

    // Calculate new Elo scores (you may need to adjust this based on your Elo calculation logic)
    $k_factor = 32; // Adjust this based on your Elo system
    $expected_winner = 1 / (1 + 10 ** (($loser_elo - $winner_elo) / 400));
    $expected_loser = 1 - $expected_winner;

    $new_winner_elo = $winner_elo + $k_factor * (1 - $expected_winner);
    $new_loser_elo = $loser_elo + $k_factor * (0 - $expected_loser);

    // Update Elo scores in the database
    $wpdb->update(
        $table_name,
        array('elo' => $new_winner_elo),
        array('id' => $winner_id),
        array('%d'),
        array('%d')
    );

    $wpdb->update(
        $table_name,
        array('elo' => $new_loser_elo),
        array('id' => $loser_id),
        array('%d'),
        array('%d')
    );
}

function elo_enqueue_styles()
{
    wp_enqueue_style('elo-styles', plugin_dir_url(__FILE__) . 'assets/styles.css');

    wp_enqueue_style('wp-admin');
}

// Hook to enqueue styles
add_action('admin_enqueue_scripts', 'elo_enqueue_styles');