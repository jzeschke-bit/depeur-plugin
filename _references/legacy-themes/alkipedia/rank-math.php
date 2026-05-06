<?php
/**
 * Alkipedia 3.0 - Rank Math SEO Integration
 * 
 * Diese Datei enthält alle Rank Math SEO-spezifischen Anpassungen
 * für das Alkipedia Theme, einschließlich Schema.org Markup und
 * Canonical URL Optimierungen.
 * 
 * @package Alkipedia
 * @version 1.2.1
 * @author Jonas Zeschke
 */

function custom_rankmath_schema_data( $entity ) {
    // Ensure the author key exists and is an array
    if ( ! isset( $entity['author'] ) || ! is_array( $entity['author'] ) ) {
        return $entity;
    }

    // Get the post’s ID and subsequently the author’s ID
    $post_id   = get_the_ID();
    $author_id = get_post_field( 'post_author', $post_id );

    // Fetch the job title from ACF field
    $job_title = get_field( 'author_jobtitle', 'user_' . $author_id );

    // Add the job title to the schema
    if ( $job_title ) {
        $entity['author']['jobTitle'] = $job_title;
    }

    // Fetch the alumni of and URL data from ACF
    $alumni_of = get_field( 'author_alumniof', 'user_' . $author_id );
    $alumni_of_url = get_field( 'author_alumniof_url', 'user_' . $author_id );

    // Add the alumni of and URL data to the schema
    if ( $alumni_of && $alumni_of_url ) {
        $entity['author']['alumniOf'] = [
            "@type" => "EducationalOrganization",
            "name" => $alumni_of,
            "sameAs" => $alumni_of_url
        ];
    }

    // Process the knowsAbout fields
    $knowsAbout_fields = ['author_knowabout', 'author_knowabout_2', 'author_knowabout_3', 'author_knowabout_4'];
    $knowsAbout_values = [];
    foreach($knowsAbout_fields as $field) {
        $value = get_field($field, 'user_' . $author_id);
        if (!empty($value)) {
            $knowsAbout_values[] = $value;
        }
    }

    if (!empty($knowsAbout_values)) {
        $entity['author']['knowsAbout'] = $knowsAbout_values;
    }

    return $entity;
}
add_filter( 'rank_math/snippet/rich_snippet_article_entity', 'custom_rankmath_schema_data', 20 );


//custom schema
//publishing principles
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    if ( isset( $data['publisher'] ) ) {
        $data['publisher']['publishingPrinciples'] = ['https://fittastetic.com/veroeffentlichungs-prinzipien/'];
    }
    return $data;
}, 99, 2 );


// Fix Canonical URL für paginierte Rezeptkategorie-Seiten
// Verhindert, dass alle paginierten Seiten auf Seite 1 zeigen
add_filter('rank_math/frontend/canonical', function($canonical) {
    // Prüfe ob wir auf einer Rezeptkategorie-Seite sind
    if (is_page() && get_page_template_slug() === 'single-rezeptkategorie-template.php') {
        $paged = max(1, get_query_var('paged'));
        
        // Nur für paginierte Seiten (ab Seite 2)
        if ($paged > 1) {
            // Hole die aktuelle URL und stelle sicher, dass sie auf die paginierte Seite zeigt
            $current_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            // Entferne Query-Parameter falls vorhanden (behalte nur den sauberen Pfad)
            $canonical = strtok($current_url, '?');
            
            return $canonical;
        }
    }
    
    return $canonical;
});