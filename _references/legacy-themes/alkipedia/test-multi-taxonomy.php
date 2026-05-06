<?php
/**
 * Test-Datei für Multi-Taxonomie-Support
 * Diese Datei demonstriert die neuen Funktionen für die Unterstützung
 * von Custom Post Type Tags und anderen Taxonomien
 * 
 * WICHTIG: Diese Datei sollte nach dem Testen gelöscht werden!
 */

// Nur im Admin-Bereich ausführen
if (!is_admin()) {
    return;
}

// Test-Funktion für die neuen Multi-Taxonomie-Funktionen
function test_alkipedia_multi_taxonomy_support() {
    echo '<div class="notice notice-info"><p><strong>Alkipedia Multi-Taxonomie Test</strong></p></div>';
    
    // Test 1: Taxonomie-Mapping
    echo '<h3>1. Taxonomie-Mapping Test</h3>';
    $mapping = alkipedia_get_taxonomy_mapping();
    echo '<pre>' . print_r($mapping, true) . '</pre>';
    
    // Test 2: Alle unterstützten Taxonomien
    echo '<h3>2. Alle unterstützten Taxonomien</h3>';
    $all_taxonomies = alkipedia_get_all_supported_taxonomies();
    echo '<pre>' . print_r($all_taxonomies, true) . '</pre>';
    
    // Test 3: Tag-Validierung (simuliert)
    echo '<h3>3. Tag-Validierung Test</h3>';
    $test_tag_ids = array(1, 2, 3); // Simulierte Tag-IDs
    $grouped_terms = alkipedia_validate_and_group_tag_ids($test_tag_ids);
    echo '<pre>' . print_r($grouped_terms, true) . '</pre>';
    
    // Test 4: Query-Argumente
    echo '<h3>4. Query-Argumente Test</h3>';
    $query_args = alkipedia_build_multi_taxonomy_query($grouped_terms);
    echo '<pre>' . print_r($query_args, true) . '</pre>';
    
    echo '<div class="notice notice-success"><p><strong>Test abgeschlossen!</strong> Die Multi-Taxonomie-Funktionen sind bereit.</p></div>';
}

// Füge Test-Button zum Admin-Menü hinzu
add_action('admin_menu', function() {
    add_management_page(
        'Alkipedia Multi-Taxonomie Test',
        'Taxonomie Test',
        'manage_options',
        'alkipedia-taxonomy-test',
        'test_alkipedia_multi_taxonomy_support'
    );
});
