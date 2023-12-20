<?php
// elo-elo.php

// Function to update Elo scores based on match result
function elo_update_elo_scores($winner_id, $loser_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_players';

    // Get current Elo scores of winner and loser
    $winner_elo = $wpdb->get_var($wpdb->prepare("SELECT elo FROM $table_name WHERE id = %d", $winner_id));
    $loser_elo = $wpdb->get_var($wpdb->prepare("SELECT elo FROM $table_name WHERE id = %d", $loser_id));

    // Constants for Elo calculation
    $k_factor = 32;
    $expected_winner_score = 1 / (1 + 10 ** (($loser_elo - $winner_elo) / 400));
    $expected_loser_score = 1 / (1 + 10 ** (($winner_elo - $loser_elo) / 400));

    // Calculate new Elo scores
    $new_winner_elo = $winner_elo + $k_factor * (1 - $expected_winner_score);
    $new_loser_elo = $loser_elo + $k_factor * (0 - $expected_loser_score);

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
