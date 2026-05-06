<?php
/**
 * The main archive template file for inner content.
 *
 * @package kadence
 */

namespace Kadence;

$queried_object_id = get_queried_object_id();
$static_page_id = null;

if (is_category()) {
    $static_page_id = get_field('static_page', 'category_' . $queried_object_id);
} elseif (is_author()) {
    $static_page_id = get_field('static_page_for_author', 'user_' . $queried_object_id);
}

$has_static_page = (!get_query_var('paged') || get_query_var('paged') == 1) && $static_page_id;

// Wenn keine statische Seite ausgewählt ist, führen Sie den Hook für die Hero-Sektion aus
if (!$has_static_page) {
    do_action('kadence_hero_header');
}
?>
<div id="primary" class="content-area">
    <div class="content-container site-container">
        <main id="main" class="site-main" role="main">
            <?php
            do_action('kadence_before_main_content');

            if ($has_static_page) {
                $page = get_post($static_page_id);
                if ($page) {
                    echo apply_filters('the_content', $page->post_content);
                } else {
                    echo "Inhalt nicht gefunden.";
                }
            } else {
                if (kadence()->show_in_content_title()) {
                    get_template_part('template-parts/content/archive_header');
                }
                if (have_posts()) {
                    ?>
                    <div id="archive-container" class="<?php echo esc_attr(implode(' ', get_archive_container_classes())); ?>"<?php echo (get_archive_infinite_attributes() ? " data-infinite-scroll='" . esc_attr(get_archive_infinite_attributes()) . "'" : ''); ?>>
                        <?php
                        while (have_posts()) {
                            the_post();
                            do_action('kadence_loop_entry');
							
                        }
                        ?>
                    </div>
                    <?php
                    get_template_part('template-parts/content/pagination');
                } else {
                    get_template_part('template-parts/content/error');
                }
            }

            do_action('kadence_after_main_content');
            ?>
        </main><!-- #main -->
        <?php
        get_sidebar();
        ?>
    </div>
</div><!-- #primary -->
