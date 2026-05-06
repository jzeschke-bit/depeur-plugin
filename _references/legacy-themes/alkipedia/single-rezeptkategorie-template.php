<?php
/**
 * Template Name: Rezeptkategorie
 * Beschreibung: Seite 1 zeigt 4 Beiträge im Page-Stil. Seite 2+ nutzt Kadence-Archiv + korrekte Pagination.
 */

namespace Kadence;

$paged = max(1, get_query_var('paged'));
$page_id = get_queried_object_id();

// Hole Tags aus verschiedenen ACF-Feldern und Taxonomien
$grouped_terms = alkipedia_get_multi_taxonomy_terms_from_acf($page_id);

// Fallback auf 'low-carb' Tag wenn nichts ausgewählt
if (empty($grouped_terms)) {
    $default_tag = get_term_by('slug', 'low-carb', 'post_tag');
    if ($default_tag) {
        $grouped_terms = array('post_tag' => array($default_tag->term_id));
    }
}

// Hole die Tag-Slugs für Body-Classes und andere Verwendungen
$rezept_tag_slugs = array();
foreach ($grouped_terms as $taxonomy => $term_ids) {
    foreach ($term_ids as $term_id) {
        $term = get_term($term_id, $taxonomy);
        if ($term && !is_wp_error($term)) {
            $rezept_tag_slugs[] = $term->slug;
        }
    }
}

// Zähle alle passenden Beiträge (für spätere Pagination)
$query_args = array(
    'post_type' => alkipedia_get_supported_post_types(),
    'posts_per_page' => -1,
    'fields' => 'ids',
);

// Füge Multi-Taxonomie-Filter hinzu, wenn gültige Terms vorhanden sind
if (!empty($grouped_terms)) {
    $taxonomy_query_args = alkipedia_build_multi_taxonomy_query($grouped_terms);
    $query_args = array_merge($query_args, $taxonomy_query_args);
}

$total_query = new \WP_Query($query_args);
$total_found = count($total_query->posts);

// Berechne Offset + Limit
if ($paged === 1) {
    $posts_per_page = 4;
    $offset = 0;
} else {
    $posts_per_page = 21;
    $offset = 4 + ($paged - 2) * 21;
}

$args = array(
    'post_type' => alkipedia_get_supported_post_types(),
    'posts_per_page' => $posts_per_page,
    'offset' => $offset,
);

// Füge Multi-Taxonomie-Filter hinzu, wenn gültige Terms vorhanden sind
if (!empty($grouped_terms)) {
    $taxonomy_query_args = alkipedia_build_multi_taxonomy_query($grouped_terms);
    $args = array_merge($args, $taxonomy_query_args);
}

$query = new \WP_Query($args);

// Simuliere Archivverhalten ab Seite 2
if ($paged > 1) {
    global $wp_query;
    $wp_query->is_archive = true;
    $wp_query->is_page = false;
    $wp_query->is_singular = false;

    add_filter('body_class', function($classes) use ($grouped_terms) {
        $classes[] = 'archive';
        $classes[] = 'tag';
        // Füge Klassen für alle Terms aus allen Taxonomien hinzu
        foreach ($grouped_terms as $taxonomy => $term_ids) {
            foreach ($term_ids as $term_id) {
                $term = get_term($term_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $classes[] = 'tag-' . sanitize_html_class($term->slug);
                    // Füge auch Taxonomie-spezifische Klassen hinzu
                    $classes[] = sanitize_html_class($taxonomy) . '-' . sanitize_html_class($term->slug);
                }
            }
        }
        return $classes;
    });

    add_filter('kadence_is_loop_archive', '__return_true');
    
    // RankMath Meta Title für paginierte Seiten
    add_filter('rank_math/frontend/title', function($title) use ($paged, $page_id) {
        $custom_title = get_field('rezeptkategorie_titel', $page_id) ?: get_the_title($page_id);
        return $custom_title . ' - Seite ' . $paged;
    });
}

// Archiv-Titel überschreiben
add_filter('get_the_archive_title', function($title) use ($paged, $page_id) {
    $custom = get_field('rezeptkategorie_titel', $page_id) ?: get_the_title($page_id);
    return $custom . ' – Seite ' . $paged;
});

get_header();
do_action('kadence_hero_header');

// Gemeinsame Struktur für alle Seiten
?>
<div id="primary" class="content-area">
  <div class="content-container site-container">
    <main id="main" class="site-main" role="main">
<?php

if ($paged === 1) {
    global $wp_query;
    $wp_query->is_page = false;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
?>
      <?php do_action('kadence_before_main_content'); ?>
      <div class="content-wrap">
        <article id="post-<?php echo esc_attr($page_id); ?>" <?php post_class('entry content-bg single-entry'); ?>>
          <div class="entry-content-wrap">
            <?php
            if ( kadence()->show_feature_above() ) {
              get_template_part('template-parts/content/entry_thumbnail', get_post_type());
            }

            do_action('kadence_single_before_inner_content');

            if ( kadence()->show_in_content_title() ) {
              get_template_part('template-parts/content/entry_header', get_post_type());
            }

            if ( kadence()->show_feature_below() ) {
              get_template_part('template-parts/content/entry_thumbnail', get_post_type());
            }

            the_content();

            if ('post' === get_post_type() && kadence()->option('post_tags')) {
              get_template_part('template-parts/content/entry_footer', get_post_type());
            }

            do_action('kadence_single_after_inner_content');
            ?>
          </div>
        </article>
      </div>

      <div class="entry-related-inner-content category-related">
          <h2 class="entry-title">
            <?php echo 'Weitere ' . esc_html( get_field('rezeptkategorie_titel', $page_id) ?: get_the_title($page_id) ) . ' die dir gefallen könnten'; ?>
          </h2>
		  </div>
		  
<?php } ?>

<?php if ( $query->have_posts() ) : ?>
      <div id="archive-container" class="content-wrap grid-cols post-archive <?php echo $paged === 1 ? 'grid-sm-col-2 grid-lg-col-2' : 'grid-sm-col-2 grid-lg-col-3'; ?>">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
          <?php do_action('kadence_loop_entry'); ?>
        <?php endwhile; ?>
      </div>

      <?php
      // Pagination korrekt simulieren
      $original_query = $wp_query;
      $wp_query = new \WP_Query();
      $wp_query->found_posts = $total_found;
      
      // Berechne die Gesamtanzahl der Seiten
      if ($total_found <= 10) {
          $wp_query->max_num_pages = 1;
      } else {
          $remaining_posts = $total_found - 4;
          $wp_query->max_num_pages = 1 + ceil($remaining_posts / 21);
      }
      
      $wp_query->query_vars['paged'] = $paged;
      get_template_part('template-parts/content/pagination');
      $wp_query = $original_query;
      ?>

<?php else : ?>
      <?php get_template_part('template-parts/content/error'); ?>
<?php endif; ?>

<?php if ($paged === 1) : ?>
      <?php
      if (kadence()->option('post_author_box')) {
        // Stelle sicher, dass wir den richtigen Post-Kontext für die Autor-Box haben
        global $post;
        $temp_post = $post;
        $post = get_post($page_id);
        setup_postdata($post);
        
        get_template_part('template-parts/content/entry_author', 'post');
        
        // Stelle den ursprünglichen Post-Kontext wieder her
        $post = $temp_post;
        wp_reset_postdata();
      }
      if (kadence()->show_comments()) {
        // Stelle sicher, dass wir den richtigen Post-Kontext für Kommentare haben
        global $post;
        $temp_post = $post;
        $post = get_post($page_id);
        setup_postdata($post);
        
        comments_template();
        
        // Stelle den ursprünglichen Post-Kontext wieder her
        $post = $temp_post;
        wp_reset_postdata();
      }
      do_action('kadence_after_main_content');
      ?>
<?php endif; ?>
    </main>
    <?php get_sidebar(); ?>
  </div>
</div>
<?php

get_footer();
