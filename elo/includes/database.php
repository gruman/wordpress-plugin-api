<?php
// elo-database.php

// Function to create the database table on plugin activation
function elo_create_table() {
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

// Function to handle form submissions for adding/editing players and recording match results
// Function to handle form submissions for adding/editing players and recording match results
function elo_handle_form_submission() {
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
        // Handle match result form submission
        // ...

    } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['player_id'])) {
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

