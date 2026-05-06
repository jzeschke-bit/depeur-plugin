<?php
/*
Plugin Name: Depeur Favoriten
Description: Ermöglicht es Benutzern, Beiträge zu liken.
Version: 1.0
Author: Dein Name
*/

if (!defined('ABSPATH')) {
    exit; // Verhindert direkten Zugriff
}

// Inkludiere die Hauptklasse
require_once plugin_dir_path(__FILE__) . 'includes/class-my-favorite-posts.php';

// Initialisiere das Plugin
function initialize_my_favorite_posts_plugin() {
    $plugin = new My_Favorite_Posts();
    $plugin->init();
}
add_action('plugins_loaded', 'initialize_my_favorite_posts_plugin');