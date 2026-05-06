<?php
/**
 * Alkipedia 3.0 Theme Functions
 * 
 * Dieses Theme basiert auf dem Kadence Theme und wurde für Alkipedia angepasst.
 * Es enthält alle notwendigen Funktionen für ein Kochen und Ernährung Theme.
 * 
 * @package Alkipedia
 * @version 1.2.4
 * @author Jonas Zeschke
 */

// Unterdrücke Deprecation Warnings für PHP 8.1+ Kompatibilität
// Nur im Frontend ausführen, nicht im Admin
if (!is_admin() && version_compare(PHP_VERSION, '8.1.0', '>=')) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}


/* ========================================
   THEME SETUP & ENQUEUE SCRIPTS
   ======================================== */

/**
 * Setup Child Theme Styles
 * Lädt die Theme-spezifischen Stylesheets
 */
function alkipedia_enqueue_styles() {
	// Dynamische Versionsnummer basierend auf filemtime für Cache-Busting
	$css_version = filemtime(get_stylesheet_directory() . '/style.css');
	wp_enqueue_style( 'alkipedia-style', get_stylesheet_directory_uri() . '/style.css', false, $css_version  );
}

add_action( 'wp_enqueue_scripts', 'alkipedia_enqueue_styles', 100 );

/* ========================================
   CUSTOM POST TYPES CONFIGURATION
   ======================================== */

/**
 * Zentrale Konfiguration für alle Custom Post Types
 * Hier werden alle unterstützten Post-Typen definiert
 * 
 * @return array Array mit allen unterstützten Post-Typen
 */
function alkipedia_get_supported_post_types() {
    return array(
        'post',           // Standard-Posts
        'blog',           // Blog-Posts
        'tests',          // Test-Posts
        'cocktails',      // Cocktail-Rezepte
        'trinkspiel',     // Trinkspiele
        'bar-equipment'   // Bar-Equipment
    );
}

/**
 * Erweiterte Post-Typen (ohne Standard-Posts)
 * Für Fälle, wo nur Custom Post Types benötigt werden
 * 
 * @return array Array mit Custom Post Types (ohne Standard-Posts)
 */
function alkipedia_get_custom_post_types() {
    $all_types = alkipedia_get_supported_post_types();
    return array_filter($all_types, function($type) {
        return $type !== 'post';
    });
}

/**
 * Hilfsfunktion: Neuen Custom Post Type hinzufügen
 * Fügt einen neuen Post-Typ zur zentralen Konfiguration hinzu
 * 
 * @param string $post_type Der neue Post-Typ (z.B. 'neuer-cpt')
 * @param string $description Beschreibung für die Dokumentation
 */
function alkipedia_add_custom_post_type($post_type, $description = '') {
    // Diese Funktion ist für zukünftige Verwendung gedacht
    // Aktuell müssen neue Post-Types manuell in alkipedia_get_supported_post_types() hinzugefügt werden
    // TODO: Implementiere dynamische Hinzufügung von Post-Types
    error_log("Neuer Custom Post Type hinzugefügt: {$post_type} - {$description}");
}

/**
 * Erweiterte Taxonomie-Konfiguration
 * Definiert alle unterstützten Taxonomien für die verschiedenen Post-Types
 * 
 * @return array Array mit Post-Type => Taxonomien Mapping
 */
function alkipedia_get_taxonomy_mapping() {
    return array(
        'post' => array('post_tag', 'category', 'anlass', 'herkunft'),
        'blog' => array('post_tag', 'category', 'anlass', 'herkunft'),
        'tests' => array('post_tag', 'category', 'anlass', 'herkunft'),
        'cocktails' => array('post_tag', 'category', 'cocktail_tags', 'art', 'anlass', 'herkunft'),
        'trinkspiel' => array('post_tag', 'category', 'trinkspiel_tags', 'anlass', 'herkunft'),
        'bar-equipment' => array('post_tag', 'category', 'equipment_tags', 'anlass', 'herkunft')
    );
}

/**
 * Erweitere Taxonomie-Mapping für neue Post-Types
 * Ermöglicht das dynamische Hinzufügen von Taxonomien für neue Post-Types
 * 
 * @param string $post_type Der Post-Type
 * @param array $taxonomies Array der Taxonomien für diesen Post-Type
 */
function alkipedia_add_taxonomy_mapping($post_type, $taxonomies) {
    // Diese Funktion ist für zukünftige Verwendung gedacht
    // Aktuell muss das Mapping manuell in alkipedia_get_taxonomy_mapping() erweitert werden
    error_log("Neue Taxonomie-Mapping hinzugefügt: {$post_type} => " . implode(', ', $taxonomies));
}

/**
 * Hole alle verfügbaren Taxonomien für die unterstützten Post-Types
 * 
 * @return array Array mit allen verfügbaren Taxonomien
 */
function alkipedia_get_all_supported_taxonomies() {
    $mapping = alkipedia_get_taxonomy_mapping();
    $all_taxonomies = array();
    
    foreach ($mapping as $post_type => $taxonomies) {
        $all_taxonomies = array_merge($all_taxonomies, $taxonomies);
    }
    
    return array_unique($all_taxonomies);
}

/**
 * Validiere und konvertiere Tag-IDs für verschiedene Taxonomien
 * 
 * @param mixed $tag_ids Die Tag-IDs (können aus verschiedenen Taxonomien stammen)
 * @param array $post_types Die Post-Types für die gesucht werden soll
 * @return array Array mit 'taxonomy' => 'term_ids' Mapping
 */
function alkipedia_validate_and_group_tag_ids($tag_ids, $post_types = null) {
    if (empty($tag_ids)) {
        return array();
    }
    
    // Konvertiere einzelne ID in Array
    if (!is_array($tag_ids)) {
        $tag_ids = array($tag_id);
    }
    
    // Wenn keine Post-Types angegeben, verwende alle unterstützten
    if ($post_types === null) {
        $post_types = alkipedia_get_supported_post_types();
    }
    
    $mapping = alkipedia_get_taxonomy_mapping();
    $grouped_terms = array();
    
    foreach ($tag_ids as $tag_id) {
        if (!$tag_id || !is_numeric($tag_id)) {
            continue;
        }
        
        // Prüfe in welcher Taxonomie dieser Term existiert
        foreach ($post_types as $post_type) {
            if (!isset($mapping[$post_type])) {
                continue;
            }
            
            foreach ($mapping[$post_type] as $taxonomy) {
                $term = get_term($tag_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    if (!isset($grouped_terms[$taxonomy])) {
                        $grouped_terms[$taxonomy] = array();
                    }
                    $grouped_terms[$taxonomy][] = $tag_id;
                    break 2; // Term gefunden, weiter zum nächsten Tag
                }
            }
        }
    }
    
    return $grouped_terms;
}

/**
 * Erweiterte ACF-Feld-Unterstützung für Multi-Taxonomien
 * Sammelt Tags aus verschiedenen ACF-Feldern und Taxonomien
 * 
 * @param int $page_id Die Page-ID
 * @return array Array mit 'taxonomy' => 'term_ids' Mapping
 */
function alkipedia_get_multi_taxonomy_terms_from_acf($page_id) {
    $grouped_terms = array();
    
    // Standard rezept_tag Feld (für Rückwärtskompatibilität)
    $rezept_tag_ids = get_field('rezept_tag', $page_id);
    if (!empty($rezept_tag_ids)) {
        $standard_terms = alkipedia_validate_and_group_tag_ids($rezept_tag_ids);
        $grouped_terms = array_merge_recursive($grouped_terms, $standard_terms);
    }
    
    // Zusätzliche Taxonomie-spezifische ACF-Felder
    $taxonomy_fields = array(
        'post_tag' => 'rezept_post_tags',
        'category' => 'rezept_categories', 
        'cocktail_tags' => 'rezept_cocktail_tags',
        'art' => 'rezept_art_tags',
        'anlass' => 'rezept_anlass_tags',
        'herkunft' => 'rezept_herkunft_tags',
        'trinkspiel_tags' => 'rezept_trinkspiel_tags',
        'equipment_tags' => 'rezept_equipment_tags'
    );
    
    foreach ($taxonomy_fields as $taxonomy => $field_name) {
        $field_terms = get_field($field_name, $page_id);
        if (!empty($field_terms)) {
            if (!is_array($field_terms)) {
                $field_terms = array($field_terms);
            }
            
            // Validiere die Terms für diese spezifische Taxonomie
            $valid_terms = array();
            foreach ($field_terms as $term_id) {
                if ($term_id && is_numeric($term_id)) {
                    $term = get_term($term_id, $taxonomy);
                    if ($term && !is_wp_error($term)) {
                        $valid_terms[] = $term_id;
                    }
                }
            }
            
            if (!empty($valid_terms)) {
                $grouped_terms[$taxonomy] = $valid_terms;
            }
        }
    }
    
    return $grouped_terms;
}

/**
 * Erstelle Query-Argumente für Multi-Taxonomie-Suche
 * 
 * @param array $grouped_terms Array mit 'taxonomy' => 'term_ids' Mapping
 * @param array $post_types Die Post-Types für die gesucht werden soll
 * @return array Query-Argumente für WP_Query
 */
function alkipedia_build_multi_taxonomy_query($grouped_terms, $post_types = null) {
    if (empty($grouped_terms)) {
        return array();
    }
    
    if ($post_types === null) {
        $post_types = alkipedia_get_supported_post_types();
    }
    
    $query_args = array();
    
    // Für jede Taxonomie die entsprechenden Query-Parameter setzen
    foreach ($grouped_terms as $taxonomy => $term_ids) {
        if (empty($term_ids)) {
            continue;
        }
        
        if ($taxonomy === 'post_tag') {
            // Standard WordPress Tags
            $query_args['tag__and'] = $term_ids;
        } else {
            // Custom Taxonomien
            if (!isset($query_args['tax_query'])) {
                $query_args['tax_query'] = array();
            }
            
            $query_args['tax_query'][] = array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_ids,
                'operator' => 'AND'
            );
        }
    }
    
    // Wenn mehrere Taxonomien vorhanden sind, verwende AND-Verknüpfung
    if (count($grouped_terms) > 1 && isset($query_args['tax_query'])) {
        $query_args['tax_query']['relation'] = 'AND';
    }
    
    return $query_args;
}

/* ========================================
   ACTION HOOKS
   ======================================== */

// Custom Post Type Author Integration
add_action( 'pre_get_posts', 'wpse107459_add_cpt_author' );

// Language Link Management
add_action('wp_head', 'LanguageLink');
add_action('kadence_footer_navigation', 'lang_tag', 100);

/* ========================================
   FILTER HOOKS
   ======================================== */

// Erlaubt anonyme Kommentare für bessere Benutzerinteraktion
add_filter( 'rest_allow_anonymous_comments', '__return_true' );

/**
 * Related Posts für Custom Post Types erweitern
 * Erweitert die Kadence Related Posts Query um Custom Post Types
 * Mit intelligenter Fallback-Logik: Tags-basiert, dann Random
 */
function alkipedia_extend_related_posts_for_cpt( $args ) {
    // Unterstützte Post-Typen für Related Posts
    $supported_post_types = alkipedia_get_supported_post_types();
    
    // Erweitere die Query um alle unterstützten Post-Typen
    $args['post_type'] = $supported_post_types;
    
    // Hole die aktuellen Tags des Posts
    $current_tags = wp_get_post_tags( get_the_ID() );
    
    // Wenn Tags vorhanden sind, verwende sie für die Suche
    if ( !empty( $current_tags ) ) {
        $tag_ids = array();
        foreach ( $current_tags as $tag ) {
            $tag_ids[] = $tag->term_id;
        }
        
        // Suche nach Posts mit den gleichen Tags
        $args['tag__in'] = $tag_ids;
        
        // Wenn nicht genug Posts mit Tags gefunden werden, erweitere die Suche
        $tag_query = new WP_Query( $args );
        $found_posts = $tag_query->post_count;
        
        // Wenn weniger als 3 Posts gefunden wurden, füge Random-Fallback hinzu
        if ( $found_posts < 3 ) {
            // Entferne Tag-Filter für Fallback
            unset( $args['tag__in'] );
            
            // Füge Random-Order hinzu
            $args['orderby'] = 'rand';
            
            // Erhöhe die Anzahl der Posts, um sicherzustellen, dass wir genug haben
            $args['posts_per_page'] = max( 6, $args['posts_per_page'] );
        }
    } else {
        // Wenn keine Tags vorhanden sind, verwende Random-Order
        $args['orderby'] = 'rand';
    }
    
    return $args;
}
add_filter( 'kadence_related_posts_carousel_args', 'alkipedia_extend_related_posts_for_cpt' );


/* ========================================
 *  Shortcode fix
   ======================================== */

remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'shortcode_unautop');


/* ========================================
   COMMENTED OUT FUNCTIONS (für zukünftige Verwendung)
   ======================================== */

/* Custom Recipe JSON File - Aktuell deaktiviert
function se35728943_change_post_per_page( $args, $request ) {
    $max = max( (int) $request->get_param( 'custom_per_page' ), 200 );
    $args['posts_per_page'] = $max;    
    return $args;
}
*/

/* WordPress Admin Bar deaktivieren - Aktuell deaktiviert
add_filter( 'show_admin_bar', '__return_false' );  */


/*Custom Recipe Json File
function se35728943_change_post_per_page( $args, $request ) {
    $max = max( (int) $request->get_param( 'custom_per_page' ), 200 );
    $args['posts_per_page'] = $max;    
    return $args;
}
*/

/* Turn off the WordPress Admin Bar for all users
add_filter( 'show_admin_bar', '__return_false' );  */

/* ========================================
   CUSTOM POST TYPE FUNCTIONS
   ======================================== */

/**
 * CPT Author Integration
 * Erweitert Author-Archive um Custom Post Types
 * 
 * @param WP_Query $query Die WordPress Query
 */
function wpse107459_add_cpt_author( $query ) {
    if ( !is_admin() && $query->is_author() && $query->is_main_query() ) {
        $query->set( 'post_type', alkipedia_get_supported_post_types() );
    }
}

/**
 * CPT Tag Integration
 * Erweitert Tag-Archive um Custom Post Types
 * 
 * @param WP_Query $query Die WordPress Query
 */
function alkipedia_add_cpt_to_tag_archives( $query ) {
    if ( !is_admin() && $query->is_tag() && $query->is_main_query() ) {
        $query->set( 'post_type', alkipedia_get_supported_post_types() );
    }
}
add_action( 'pre_get_posts', 'alkipedia_add_cpt_to_tag_archives' );

/* ========================================
   MULTILINGUAL SUPPORT
   ======================================== */

/**
 * Language Link Management - hrefLang Tags
 * Fügt hreflang-Tags für mehrsprachige Inhalte hinzu
 * Unterstützt sowohl Archive- als auch Einzelseiten
 */
function LanguageLink() {
	if ( is_archive()  ) {
		// Hole den aktuellen Taxonomy Term für Archive-Seiten
		$term = get_queried_object();  
		$valueEN = get_field( 'link_en', $term );
		$valueDE = get_field( 'link_de', $term );
	  
		if( $valueEN && !empty($valueEN) ) { ?>
			<link rel="alternate" hreflang="en" href="<?php echo esc_url($valueEN); ?>">				   
		<?php if( $valueDE && !empty($valueDE) ) {?>
			<link rel="alternate" hreflang="de" href="<?php echo esc_url($valueDE); ?>">
		<?php }
		} 
	} else {
		// Alle anderen Seiten (Einzelseiten, etc.)
		$link_en = get_field('link_en');
		$link_de = get_field('link_de');
		
		if( $link_en && !empty($link_en) ){?>
			<link rel="alternate" hreflang="en" href="<?php echo esc_url($link_en); ?>">				   
		<?php if( $link_de && !empty($link_de) ){?>
			<link rel="alternate" hreflang="de" href="<?php echo esc_url($link_de); ?>">
		<?php }
		}
	}
}


/**
 * Footer Language Links
 * Fügt Sprachauswahl-Links im Footer hinzu
 * Unterstützt sowohl Archive- als auch Einzelseiten
 */
function lang_tag() {
	 if ( is_archive()  ) {
		 // Hole den aktuellen Taxonomy Term für Archive-Seiten
		$term = get_queried_object();  
	 	$valueEN = get_field( 'link_en', $term );
		$valueDE = get_field( 'link_de', $term );
		 
		 if( $valueEN && !empty($valueEN) ) { ?>
		<hr class="wp-block-separator">  
		<span><?php esc_html_e( 'Language', 'kadence' ); ?></span><br>
		<span>
			<a lang="de" hreflang="de" href="<?php echo esc_url($valueDE); ?>" 
				role="option" 
				data-value="<?php esc_html_e( 'German', 'kadence' ); ?>">
					<?php esc_html_e( 'German', 'kadence' ); ?></a> | 
			<a lang="en" hreflang="en" href="<?php echo esc_url($valueEN); ?>" 					
			   role="option" 
			   data-value="<?php esc_html_e( 'English', 'kadence' ); ?>">
					<?php esc_html_e( 'English', 'kadence' ); ?></a>
	    </span>
	<hr class="wp-block-separator">  
		<?php } 
	} else {
		// Alle anderen Seiten (Einzelseiten, etc.)
		$link_en = get_field('link_en');
		$link_de = get_field('link_de');
		
		if( $link_en && !empty($link_en) ):
			?> 
		<hr class="wp-block-separator">  
		<span><?php esc_html_e( 'Language', 'kadence' ); ?></span><br>
		<span>
			<a lang="de" hreflang="de" href="<?php echo esc_url($link_de); ?>" role="option" 
				data-value="<?php esc_html_e( 'German', 'kadence' ); ?>">
					<?php esc_html_e( 'German', 'kadence' ); ?></a> | 
			<a lang="en" hreflang="en" href="<?php echo esc_url($link_en); ?>" role="option" 
			   data-value="<?php esc_html_e( 'English', 'kadence' ); ?>">
					<?php esc_html_e( 'English', 'kadence' ); ?></a>
	    </span>
	<hr class="wp-block-separator">  
		<?php endif;
	 }
}


/* ========================================
   STATIC PAGE INTEGRATION
   ======================================== */

/**
 * Custom CSS für Static Pages
 * Entfernt den oberen Margin für statische Seiten in Kategorien und Author-Archiven
 * Wird verwendet, wenn eine statische Seite als Intro für eine Kategorie/Author definiert ist
 */
function custom_css_for_static_page() {
    $static_page_id = null;

    // Prüfe Kategorie-Seiten (nur erste Seite)
    if (is_category() && (!get_query_var('paged') || get_query_var('paged') == 1)) {
        $term_id = get_queried_object_id();
        $static_page_id = get_field('static_page', 'category_' . $term_id);
    } 
    // Prüfe Author-Seiten (nur erste Seite)
    elseif (is_author() && (!get_query_var('paged') || get_query_var('paged') == 1)) {
        $author_id = get_queried_object_id();
        $static_page_id = get_field('static_page_for_author', 'user_' . $author_id);
    }

    // Wenn eine statische Seite gefunden wurde, entferne den oberen Margin
    if ($static_page_id) {
        echo '<style>
            .content-area {
                margin-top: 0rem!important;
            }
        </style>';
    }
}

add_action('wp_head', 'custom_css_for_static_page');


/* ========================================
   PAGINATION & TAXONOMY
   ======================================== */

/**
 * Pagination für Kategorie-Seiten
 * Ermöglicht saubere URLs für paginierte Seiten (z.B. /kategorie/page/2/)
 */
function alkipedia_page_pagination_rewrite() {
    add_rewrite_rule(
        '^(.+?)/page/([0-9]+)/?$',
        'index.php?pagename=$matches[1]&paged=$matches[2]',
        'top'
    );
}
add_action('init', 'alkipedia_page_pagination_rewrite');

/**
 * Seiten-Kategorien für bessere Übersicht
 * Erstellt eine interne Taxonomie für Seiten zur besseren Organisation im Admin
 */
function custom_page_taxonomy() {
    register_taxonomy(
        'page_category',
        'page',
        array(
            'label' => 'Seiten-Kategorie',
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
        )
    );
}
add_action('init', 'custom_page_taxonomy');

/* ========================================
   TAG GROUPING SYSTEM (für "Was koche ich heute" Template)
   ======================================== */

// Hooks für Tag-Gruppierung System
add_action('acf/init', 'register_tag_group_field');
add_filter('manage_edit-post_tag_columns', 'add_tag_group_column');
add_filter('manage_post_tag_custom_column', 'display_tag_group_column', 10, 3);
add_filter('manage_edit-post_tag_sortable_columns', 'make_tag_group_column_sortable');
add_action('pre_get_terms', 'sort_tags_by_group');
add_action('wp_ajax_filter_recipes', 'filter_recipes_handler');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes_handler');

/**
 * ACF Felder für Tag-Gruppierung
 * Erstellt ein ACF-Feld für die Gruppierung von Tags in verschiedene Kategorien
 * Wird hauptsächlich für das "Was koche ich heute" Template verwendet
 */
function register_tag_group_field() {
    if(function_exists('acf_add_local_field_group')):

        acf_add_local_field_group(array(
            'key' => 'group_tag_settings',
            'title' => 'Tag-Einstellungen',
            'fields' => array(
                array(
                    'key' => 'field_tag_group',
                    'label' => 'Tag-Gruppe',
                    'name' => 'tag_group',
                    'type' => 'select',
                    'instructions' => 'Wähle die Gruppe für diesen Tag',
                    'required' => 1,
                    'choices' => array(
                        'anlass' => 'Anlass',
                        'zubereitung' => 'Zubereitung',
                        'zutaten' => 'Zutaten',
                        'saisonales' => 'Saisonales',
                        'ernaehrung_ziel' => 'Ernährung & Ziel',
                        'herkunft' => 'Herkunft'
                    ),
                    'default_value' => 'zutaten',
                    'return_format' => 'value'
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'post_tag',
                    ),
                ),
            ),
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        ));

    endif;
}
add_action('acf/init', 'register_tag_group_field');

/**
 * Füge Gruppenspalte zur Tag-Tabelle hinzu
 * Erweitert die Admin-Tabelle für Tags um eine "Gruppe"-Spalte
 * 
 * @param array $columns Die bestehenden Spalten
 * @return array Die erweiterten Spalten
 */
function add_tag_group_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'name') {
            $new_columns['tag_group'] = 'Gruppe';
        }
    }
    return $new_columns;
}
add_filter('manage_edit-post_tag_columns', 'add_tag_group_column');

/**
 * Zeige den Gruppeninhalt in der Spalte
 * Zeigt die zugewiesene Gruppe für jeden Tag in der Admin-Tabelle an
 * 
 * @param string $content Der Spalteninhalt
 * @param string $column_name Der Name der Spalte
 * @param int $term_id Die Term ID
 * @return string Der formatierte Gruppenname oder '—' wenn keine Gruppe zugewiesen
 */
function display_tag_group_column($content, $column_name, $term_id) {
    if ($column_name !== 'tag_group') {
        return $content;
    }

    $group = get_field('tag_group', 'post_tag_' . $term_id);
    $group_names = array(
        'anlass' => 'Anlass',
        'zubereitung' => 'Zubereitung',
        'zutaten' => 'Zutaten',
        'saisonales' => 'Saisonales',
        'ernaehrung_ziel' => 'Ernährung & Ziel',
        'herkunft' => 'Herkunft'
    );

    return isset($group_names[$group]) ? $group_names[$group] : '—';
}
add_filter('manage_post_tag_custom_column', 'display_tag_group_column', 10, 3);

/**
 * Mache die Gruppenspalte sortierbar
 * Ermöglicht das Sortieren der Tags nach Gruppen in der Admin-Tabelle
 * 
 * @param array $sortable Die sortierbaren Spalten
 * @return array Die erweiterten sortierbaren Spalten
 */
function make_tag_group_column_sortable($sortable) {
    $sortable['tag_group'] = 'tag_group';
    return $sortable;
}
add_filter('manage_edit-post_tag_sortable_columns', 'make_tag_group_column_sortable');

/**
 * Implementiere die Sortierung nach Gruppen
 * Sortiert Tags in der Admin-Tabelle nach ihrer zugewiesenen Gruppe
 * 
 * @param WP_Term_Query $query Die Term Query
 */
function sort_tags_by_group($query) {
    // Nur im Admin-Bereich ausführen
    if (!is_admin()) {
        return;
    }

    // Prüfe ob get_current_screen() verfügbar ist (nur im Admin)
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'post_tag') {
        return;
    }

    $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
    if ($orderby === 'tag_group') {
        $query->query_vars['meta_key'] = 'tag_group';
        $query->query_vars['orderby'] = 'meta_value';
    }
}
add_action('pre_get_terms', 'sort_tags_by_group');

/**
 * AJAX Filter für "Was koche ich heute" Template
 * Verarbeitet AJAX-Requests für das Filtern von Rezepten nach Tags
 * Unterstützt sowohl eingeloggte als auch anonyme Benutzer
 */
function filter_recipes_handler() {
    $selected_tags = isset($_POST['tags']) ? array_map('sanitize_text_field', (array)$_POST['tags']) : array();
    $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;

    // Query-Argumente für die Rezeptsuche
    $args = array(
        'post_type' => alkipedia_get_supported_post_types(),
        'posts_per_page' => 20,
        'paged' => $paged,
    );

    // Füge Tag-Filter hinzu, wenn Tags ausgewählt sind
    if (!empty($selected_tags)) {
        $args['tag_slug__and'] = $selected_tags;
    }

    // Führe Query aus
    $query = new WP_Query($args);

    // Generiere HTML-Output für die gefilterten Rezepte
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            do_action('kadence_loop_entry');
        }
    } else {
        echo '<p>Keine Rezepte gefunden, die alle ausgewählten Tags enthalten. Bitte wähle weniger oder andere Tags aus.</p>';
    }
    $content = ob_get_clean();

    // Generiere Titel basierend auf ausgewählten Tags
    $title = '';
    if (!empty($selected_tags)) {
        $title = implode(' + ', array_map(function($tag_slug) {
            $tag = get_term_by('slug', $tag_slug, 'post_tag');
            return $tag ? esc_html($tag->name) : '';
        }, $selected_tags)) . ' Rezepte';
    } else {
        $title = 'Alle Rezepte';
    }

    // Sende JSON-Response zurück
    wp_send_json(array(
        'content' => $content,
        'title' => $title,
        'hasMore' => $query->max_num_pages > $paged
    ));
}
add_action('wp_ajax_filter_recipes', 'filter_recipes_handler');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes_handler');


/* ========================================
   WPRM (WP Recipe Maker) SHORTCODES
   ======================================== */

/**
 * WPRM Shortcode für Call-to-Action Buttons
 * Erstellt einen anpassbaren CTA-Button für Rezepte
 * 
 * @package WPRM
 * @extends WPRM_Template_Shortcode
 */
class WPRM_SC_Alkipedia_CTA extends WPRM_Template_Shortcode {
	public static $shortcode = 'wprm-alkipedia-cta';

	public static function init() {
		$atts = array(
			'id' => array(
				'default' => '0',
			),
			'text' => array(
				'default' => 'Jetzt dein Kochbuch sichern!',
			),
			'link' => array(
				'default' => '/kochbuch',
			),
		);

		self::$attributes = $atts;

		parent::init(); // registriert Shortcode + Template Editor Block
	}

	/**
	 * Shortcode Output
	 * Generiert den HTML-Output für den CTA-Button
	 * 
	 * @param array $atts Die Shortcode-Attribute
	 * @return string Der HTML-Output
	 */
	public static function shortcode( $atts ) {
		$atts = parent::get_attributes( $atts );

		$output = '<div class="wprm-alkipedia-cta">';
		$output .= '<a href="' . esc_url( $atts['link'] ) . '" class="alkipedia-cta-button">';
		$output .= esc_html( $atts['text'] );
		$output .= '</a>';
		$output .= '</div>';

		return apply_filters( parent::get_hook(), $output, $atts );
	}
}

// Initialisieren der CTA Shortcode Klasse
WPRM_SC_Alkipedia_CTA::init();
 
/**
 * WPRM Shortcode für Favorite Buttons
 * Erstellt einen Favoriten-Button für Rezepte mit Cookie-basierter Speicherung
 * 
 * @package WPRM
 * @extends WPRM_Template_Shortcode
 */
class WPRM_SC_Favorite_Button extends WPRM_Template_Shortcode {
    public static $shortcode = 'wprm-favorite-button';

    /**
     * Initialisierung der Shortcode-Attribute
     * Definiert die verfügbaren Parameter für den Shortcode
     */
    public static function init() {
        $atts = array(
            'id' => array(
                'default' => '0',
            ),
            'style' => array(
                'default' => 'default',
                'type' => 'dropdown',
                'options' => array(
                    'default' => 'Standard',
                    'inline' => 'Inline',
                ),
            ),
        );

        self::$attributes = $atts;
        parent::init();
    }

    /**
     * Shortcode Output
     * Generiert den HTML-Output für den Favoriten-Button
     * Verwendet Cookie-basierte Speicherung für Favoriten
     * 
     * @param array $atts Die Shortcode-Attribute
     * @return string Der HTML-Output oder leerer String bei Fehlern
     */
    public static function shortcode($atts) {
        $atts = parent::get_attributes($atts);
        
        // Hole das Rezept-Objekt über WPRM's Methode
        $recipe = WPRM_Template_Shortcodes::get_recipe($atts['id']);
        if (!$recipe) {
            return '';
        }

        // Hole die Parent-URL
        $parent_url = $recipe->permalink();
        if (!$parent_url) {
            return '';
        }

        // Hole die Parent Post ID aus der URL
        $parent_post_id = url_to_postid($parent_url);
        if (!$parent_post_id) {
            return '';
        }
        
        // Hole den Favoriten-Status aus dem Cookie
        $favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();
        $liked = in_array($parent_post_id, $favorites) ? ' liked' : '';
        $button_text = in_array($parent_post_id, $favorites) ? '♥' : '♡';

        // Generiere Button HTML basierend auf dem Style
        if ($atts['style'] === 'inline') {
            $output = '<button class="my-favorite-post-inline-button' . $liked . '" data-post-id="' . esc_attr($parent_post_id) . '" data-is-wprm="true">' . $button_text . '</button>';
        } else {
            $output = '<div class="like-button-wrapper">' .
                     '<button class="wprm-recipe-favorite-button' . $liked . '" data-post-id="' . esc_attr($parent_post_id) . '" data-is-wprm="true">' . $button_text . '</button>' .
                     '</div>';
        }

        return $output;
    }
}

// Initialisieren der Favorite Button Shortcode Klasse
WPRM_SC_Favorite_Button::init();

