<?php
// elo-display.php
// Function to add menu item under "Elo" with a custom icon
function add_elo_menu_item() {
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

// Function to render the menu page
function elo_menu_page() {
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

// Function to display the player list
function elo_display_player_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_players';

    $players = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    if ($players) {
        echo '<h2>Player List</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Elo</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ($players as $player) {
            echo '<tr>';
            echo '<td>' . esc_html($player['id']) . '</td>';
            echo '<td>' . esc_html($player['name']) . '</td>';
            echo '<td>' . esc_html($player['elo']) . '</td>';
            echo '<td>
                    <a href="?page=elo-menu&action=edit&player_id=' . esc_attr($player['id']) . '">Edit</a> | 
                    <a href="?page=elo-menu&action=delete&player_id=' . esc_attr($player['id']) . '" onclick="return confirm(\'Are you sure you want to delete this player?\')">Delete</a>
                </td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No players found.</p>';
    }
}

// Function to display the match form
function elo_display_match_form() {
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

// Function to display the add/edit player form
function elo_display_player_form() {
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
    <h2><?php echo $player_id ? 'Edit Player' : 'Add New Player'; ?></h2>
    <form method="post" action="?page=elo-menu">
        <input type="hidden" name="elo_player_id" value="<?php echo esc_attr($player_id); ?>">

        <label for="elo_player_name">Name:</label>
        <input type="text" id="elo_player_name" name="elo_player_name" value="<?php echo esc_attr($name); ?>" required>

        <label for="elo_player_elo">Elo:</label>
        <input type="number" id="elo_player_elo" name="elo_player_elo" value="<?php echo esc_attr($elo); ?>" required>

        <input type="submit" name="elo_submit" class="button button-primary" value="<?php echo $player_id ? 'Update Player' : 'Add Player'; ?>">
    </form>
    <?php
}
