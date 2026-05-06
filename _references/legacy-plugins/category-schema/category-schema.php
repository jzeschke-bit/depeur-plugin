<?php
/**
 * Plugin Name: Category Schema
 * Plugin URI: https://yourwebsite.com
 * Description: Adds structured data to recipe category pages.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: category-schema
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function custom_rank_math_json_ld( $data ) {
    if ( is_archive() || is_category() || is_tag() ) {
        // Custom implementation for archive, category, and tag pages
        $queried_object_id = get_queried_object_id();
        $recipe_id = get_field('WPRM', 'category_' . $queried_object_id);
        $recipe_id = intval($recipe_id);

        if ( $recipe_id ) {
            $recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
            if ( $recipe && 'other' !== $recipe->type() && WPRM_Metadata::should_output_metadata_for( $recipe->id() ) ) {
                // Custom recipe metadata handling
                $metadata = WPRM_Metadata::sanitize_metadata( WPRM_Metadata::get_metadata( $recipe ) );

                if ( $metadata ) {
                    // Ensure context is not set to avoid conflicts
                    unset( $metadata['@context'] );

                    // Check for a CollectionPage schema in the data
                    $collection_page_key = false;
                    foreach ( $data as $key => $schema ) {
                        if ( isset( $schema['@type'] ) && 'CollectionPage' === $schema['@type'] ) {
                            $collection_page_key = $key;
                            break;
                        }
                    }

                    // Set the recipe metadata as part of the CollectionPage, if it exists
                    if ( false !== $collection_page_key ) {
                        if ( isset( $data[ $collection_page_key ]['@id'] ) ) {
                            $metadata['isPartOf'] = array( '@id' => $data[ $collection_page_key ]['@id'] );
                        }
                    }

                    // Add the recipe metadata to the data array
                    $data['recipe'] = $metadata;
                }
            }
        }
    } else {
        // For post, page, or any other singular content type
        // Ensure the default WPRM Rank Math integration is still applied
        $data = WPRM_Metadata_Rank_Math::rank_math_json_ld( $data, null );
    }

    return $data;
}
add_filter( 'rank_math/json_ld', 'custom_rank_math_json_ld', 99, 1 );


// Anpassen der Metadaten für WPRM-Recipe
function custom_wprm_author_metadata($metadata, $recipe) {
    // Only proceed if the author metadata is present
    if (isset($metadata['author'])) {
        // Get the author's ID
        $author_id = get_post_field('post_author', $recipe->ID());

        // Fetch various details from ACF fields and WordPress fields
        $job_title = get_field('author_jobtitle', 'user_' . $author_id);
        $author_url = get_author_posts_url($author_id);
        $author_description = get_the_author_meta('description', $author_id);
        $reviewer_alumni = get_field('author_alumniof', 'user_' . $author_id);
        $reviewer_alumni_url = get_field('author_alumniof_url', 'user_' . $author_id);

        // Update the author details
        $metadata['author']['jobTitle'] = $job_title;
        $metadata['author']['url'] = $author_url;
        $metadata['author']['description'] = $author_description;

        // Alumni structured data
        $metadata['author']['alumniOf'] = array(
            "@type" => "EducationalOrganization",
            "name" => $reviewer_alumni,
            "sameAs" => $reviewer_alumni_url
        );

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
            $metadata['author']['knowsAbout'] = $knowsAbout_values;
        }

        // Process the sameAs fields from ACF
        $sameAs_fields = ['same_as', 'same_as_2'];
        $sameAs_values = [];
        foreach ($sameAs_fields as $field) {
            $value = get_field($field, 'user_' . $author_id);
            if (!empty($value)) {
                $sameAs_values[] = $value;
            }
        }

        if (!empty($sameAs_values)) {
            $metadata['author']['sameAs'] = $sameAs_values;
        }
    }

    // Return the modified metadata
    return $metadata;
}

add_filter('wprm_recipe_metadata', 'custom_wprm_author_metadata', 10, 2);

