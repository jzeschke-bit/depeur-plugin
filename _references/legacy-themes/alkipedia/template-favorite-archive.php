<?php
/**
 * Template Name: Favorite Archive
 *
 * @package kadence
 */

namespace Kadence;

get_header();

/**
 * Hook for Hero Section
 */
do_action( 'kadence_hero_header' );

// Hier entfernen wir nur den automatischen Titel, aber der Inhalt der Seite bleibt erhalten
if ( have_posts() ) : 
    while ( have_posts() ) : the_post();
        ?>
        <div class="page-content" style="background-color: var(--global-palette7); margin: 0rem 0 0;">
            <?php the_content(); ?>
        </div><!-- .page-content -->
        <?php
    endwhile;
endif;

?>
<div id="primary" class="content-area full-width-content" style="background-color: var(--global-palette8);">
	<div class="content-container site-container">
		<main id="main" class="site-main" role="main">
			<?php
			do_action( 'kadence_before_main_content' );

			if ( kadence()->show_in_content_title() ) {
				get_template_part( 'template-parts/content/archive_header' );
			}

			$favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();
			
			if (!empty($favorites)) {
				$query = new \WP_Query(array(
					'post_type' => alkipedia_get_supported_post_types(),
					'post__in' => $favorites,
					'orderby' => 'post__in',
					'posts_per_page' => -1,
				));
			}

			if ( isset($query) && $query->have_posts() ) {
				?>
				<div id="archive-container" class="<?php echo esc_attr( implode( ' ', get_archive_container_classes() ) ); ?>" style="background-color: var(--global-palette8); padding: var(--global-lg-spacing) 0;">
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						do_action( 'kadence_loop_entry' );
					}
					?>
				</div>
				<?php
				get_template_part( 'template-parts/content/pagination' );
			} else {
				get_template_part( 'template-parts/content/error' );
			}
			do_action( 'kadence_after_main_content' );
			?>
		</main><!-- #main -->
	</div>
</div><!-- #primary -->

<?php
get_footer();
?>
