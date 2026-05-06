<?php
/**
 * Template Name: Was koche ich heute
 */

namespace Kadence;

$paged = max(1, get_query_var('paged'));
$page_id = get_queried_object_id();

// Hole die ausgewählten Tags aus der URL
$selected_tags = isset($_GET['tags']) ? array_map('sanitize_text_field', (array)$_GET['tags']) : array();

// Erstelle die Query-Argumente
$query_args = array(
    'post_type' => alkipedia_get_supported_post_types(),
    'posts_per_page' => 20,
    'paged' => $paged,
);

// Füge Tag-Filter hinzu, wenn Tags ausgewählt sind
if (!empty($selected_tags)) {
    $query_args['tag_slug__and'] = $selected_tags;
}

// Zähle alle passenden Beiträge (für Pagination)
$count_args = array_merge($query_args, array('posts_per_page' => -1, 'fields' => 'ids'));
$total_query = new \WP_Query($count_args);
$total_found = count($total_query->posts);

// Hauptquery für die anzuzeigenden Beiträge
$query = new \WP_Query($query_args);

// Simuliere Archivverhalten ab Seite 2
if ($paged > 1) {
    global $wp_query;
    $wp_query->is_archive = true;
    $wp_query->is_page = false;
    $wp_query->is_singular = false;

    add_filter('body_class', function($classes) {
        $classes[] = 'archive';
        return $classes;
    });

    add_filter('kadence_is_loop_archive', '__return_true');
}

get_header();
?>
<style>
.affiliate-marker-disclosure {
    display: none !important;
}

/* Filter Bubble Styles */
.tag-checkbox {
    font-size: 80% !important;
    background-color: var(--global-palette7) !important;
    border-radius: 15px !important;
    color: var(--global-palette4) !important;
    border: 1px solid var(--global-palette2) !important;
    display: inline-flex !important;
    padding: 0.4em 0.6em !important;
    margin-top: 0.5em !important;
    text-decoration: none !important;
    transition: all 0.3s ease !important;
    align-items: center !important;
    cursor: pointer !important;
}

.tag-checkbox input[type="checkbox"] {
    margin-right: 8px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    width: 16px !important;
    height: 16px !important;
    border: 2px solid var(--global-palette2) !important;
    border-radius: 3px !important;
    outline: none !important;
    cursor: pointer !important;
    position: relative !important;
    background-color: var(--global-palette9) !important;
}

.tag-checkbox input[type="checkbox"]:checked {
    background-color: var(--global-palette2) !important;
}

.tag-checkbox input[type="checkbox"]:checked::after {
    content: '✓' !important;
    position: absolute !important;
    color: var(--global-palette9) !important;
    font-size: 12px !important;
    left: 1px !important;
    top: -4px !important;
}

.tag-checkbox:has(input:checked) {
    background-color: var(--global-palette6) !important;
    color: var(--global-palette9) !important;
}
</style>
<?php
do_action('kadence_hero_header');

if ($paged === 1) {
    global $wp_query;
    $wp_query->is_page = false;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
?>
<div id="primary" class="content-area">
    <div class="content-container site-container">
        <main id="main" class="site-main" role="main">
            <?php do_action('kadence_before_main_content'); ?>
            <div class="content-wrap">
                <article id="post-<?php echo esc_attr($page_id); ?>" <?php post_class('entry content-bg single-entry'); ?>>
                    <div class="entry-content-wrap">
                        <?php
                        if (kadence()->show_feature_above()) {
                            get_template_part('template-parts/content/entry_thumbnail', get_post_type());
                        }

                        do_action('kadence_single_before_inner_content');

                        if (kadence()->show_in_content_title()) {
                            get_template_part('template-parts/content/entry_header', get_post_type());
                        }

                        if (kadence()->show_feature_below()) {
                            get_template_part('template-parts/content/entry_thumbnail', get_post_type());
                        }

                        the_content();
                        ?>
                    </div>
                </article>
            </div>

            <!-- Filter-Formular -->
            <div class="content-wrap">
                <form method="get" class="recipe-filter" id="tag-filter-form" style="margin-bottom: 2em;">
                    <?php
                    // Definiere die Gruppen und ihre Titel
                    $groups = array(
                        'anlass' => 'Nach Anlass',
                        'zubereitung' => 'Nach Zubereitung',
                        'zutaten' => 'Nach Zutaten',
                        'saisonales' => 'Saisonales',
                        'ernaehrung_ziel' => 'Nach Ernährung & Ziel',
                        'herkunft' => 'Nach Herkunft'
                    );

                    // Hole alle Tags und sortiere sie in Gruppen
                    $tags = get_tags(array('hide_empty' => true));
                    $grouped_tags = array();
                    foreach ($tags as $tag) {
                        $group = get_field('tag_group', 'post_tag_' . $tag->term_id);
                        if (!$group) $group = 'zutaten'; // Fallback
                        if (!isset($grouped_tags[$group])) $grouped_tags[$group] = array();
                        $grouped_tags[$group][] = $tag;
                    }

                    // Zeige die Gruppen an
                    foreach ($groups as $group_key => $group_title) :
                        if (isset($grouped_tags[$group_key]) && !empty($grouped_tags[$group_key])) :
                    ?>
                        <div class="tag-group" style="margin-bottom: 1.5em;">
                            <h3 style="margin-bottom: 0.5em;"><?php echo esc_html($group_title); ?></h3>
                            <div class="tag-filter" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php
                                foreach ($grouped_tags[$group_key] as $tag) {
                                    $is_selected = in_array($tag->slug, $selected_tags);
                                    printf(
                                        '<label class="tag-checkbox" style="display: inline-flex; align-items: center; background: %s; padding: 8px 16px; border-radius: 20px; cursor: pointer; transition: all 0.3s ease; color: %s;">
                                            <input type="checkbox" name="tags[]" value="%s" %s style="margin-right: 8px;" data-tag-filter>
                                            %s
                                        </label>',
                                        $is_selected ? 'var(--global-palette4)' : 'var(--global-palette7)',
                                        $is_selected ? 'var(--global-palette9)' : 'var(--global-palette4)',
                                        esc_attr($tag->slug),
                                        checked($is_selected, true, false),
                                        esc_html($tag->name)
                                    );
                                }
                                ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>

                    <?php if (!empty($selected_tags)) : ?>
                        <button type="button" id="reset-filters" class="wp-block-button__link" style="display: inline-block;">Filter zurücksetzen</button>
                    <?php endif; ?>
                </form>
            </div>

            <header class="page-header ">
                <h2 class="entry-title" id="results-title">
                    <?php 
                    if (!empty($selected_tags)) {
                        echo implode(' + ', array_map(function($tag_slug) {
                            $tag = get_term_by('slug', $tag_slug, 'post_tag');
                            return $tag ? esc_html($tag->name) : '';
                        }, $selected_tags)) . ' Rezepte';
                    } else {
                        echo 'Alle Rezepte';
                    }
                    ?>
                </h2>
            </header>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('tag-filter-form');
                const checkboxes = document.querySelectorAll('[data-tag-filter]');
                const archiveContainer = document.getElementById('archive-container');
                const resultsTitle = document.getElementById('results-title');
                const resetButton = document.getElementById('reset-filters');
                const loadMoreButton = document.getElementById('load-more-button');
                let currentXHR = null;
                let currentPage = 1;
                let isLoading = false;
                let hasMore = true;

                // Funktion zum Aktualisieren der UI
                function updateUI(selectedTags) {
                    // Update checkbox styles
                    checkboxes.forEach(checkbox => {
                        const label = checkbox.closest('label');
                        if (label) {
                            label.style.background = selectedTags.includes(checkbox.value) ? 'var(--global-palette4)' : 'var(--global-palette7)';
                            label.style.color = selectedTags.includes(checkbox.value) ? 'var(--global-palette9)' : 'var(--global-palette4)';
                        }
                    });

                    // Show/hide reset button
                    if (resetButton) {
                        resetButton.style.display = selectedTags.length > 0 ? 'inline-block' : 'none';
                    }

                    // Update URL without page reload
                    const url = new URL(window.location);
                    if (selectedTags.length > 0) {
                        url.searchParams.delete('tags');
                        selectedTags.forEach(tag => url.searchParams.append('tags[]', tag));
                    } else {
                        url.searchParams.delete('tags');
                    }
                    window.history.pushState({}, '', url);
                }

                // Funktion zum Laden der gefilterten Ergebnisse
                function loadFilteredResults(selectedTags, page = 1, append = false) {
                    if (isLoading) return;
                    
                    isLoading = true;
                    loadMoreButton.textContent = 'Lade...';
                    loadMoreButton.disabled = true;

                    // Abbrechen vorheriger AJAX-Anfragen
                    if (currentXHR) {
                        currentXHR.abort();
                    }

                    // Loading-Zustand
                    if (!append) {
                        archiveContainer.style.opacity = '0.5';
                    }

                    // AJAX-Anfrage
                    currentXHR = new XMLHttpRequest();
                    currentXHR.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
                    currentXHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    currentXHR.onload = function() {
                        if (this.status >= 200 && this.status < 400) {
                            const response = JSON.parse(this.response);
                            
                            if (append) {
                                archiveContainer.insertAdjacentHTML('beforeend', response.content);
                            } else {
                                archiveContainer.innerHTML = response.content;
                                resultsTitle.innerHTML = response.title;
                                currentPage = 1;
                            }
                            
                            hasMore = response.hasMore;
                            loadMoreButton.style.display = hasMore ? 'block' : 'none';
                            loadMoreButton.textContent = 'Mehr laden';
                            loadMoreButton.disabled = false;
                            archiveContainer.style.opacity = '1';
                            isLoading = false;
                        }
                    };

                    const formData = new URLSearchParams();
                    formData.append('action', 'filter_recipes');
                    selectedTags.forEach(tag => formData.append('tags[]', tag));
                    formData.append('paged', page.toString());
                    currentXHR.send(formData.toString());
                }

                // Event-Listener für "Mehr laden" Button
                if (loadMoreButton) {
                    loadMoreButton.addEventListener('click', function() {
                        if (!isLoading && hasMore) {
                            const selectedTags = Array.from(checkboxes)
                                .filter(cb => cb.checked)
                                .map(cb => cb.value);
                            
                            currentPage++;
                            loadFilteredResults(selectedTags, currentPage, true);
                        }
                    });
                }

                // Event-Listener für Checkboxen
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const selectedTags = Array.from(checkboxes)
                            .filter(cb => cb.checked)
                            .map(cb => cb.value);
                        
                        hasMore = true;
                        updateUI(selectedTags);
                        loadFilteredResults(selectedTags);
                    });
                });

                // Event-Listener für Reset-Button
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        checkboxes.forEach(cb => cb.checked = false);
                        hasMore = true;
                        updateUI([]);
                        loadFilteredResults([]);
                    });
                }
            });
            </script>
<?php } ?>

<?php if ($query->have_posts()) : ?>
    <div id="archive-container" class="content-wrap grid-cols post-archive grid-sm-col-2 grid-lg-col-2">
        <?php 
        while ($query->have_posts()) : 
            $query->the_post();
            do_action('kadence_loop_entry');
        endwhile; 
        ?>
    </div>

    <div class="content-wrap" style="text-align: center; margin-top: 2em;">
        <?php if ($total_found > 20) : ?>
            <button id="load-more-button" class="wp-block-button__link">Mehr laden</button>
        <?php endif; ?>
    </div>

    <?php
    if (kadence()->option('post_author_box')) {
        // Stelle sicher, dass wir den richtigen Post-Kontext für die Autor-Box haben
        global $post;
        $temp_post = $post;
        $post = get_post($page_id);
        setup_postdata($post);
        
        echo '<div class="content-wrap alignwide" style="margin-top: 3em; margin-bottom: 30px;">';
        get_template_part('template-parts/content/entry_author', 'post');
        echo '</div>';
        
        // Stelle den ursprünglichen Post-Kontext wieder her
        $post = $temp_post;
        wp_reset_postdata();
    }
    ?>

<?php else : ?>
    <div class="content-wrap">
        <p>Keine Rezepte gefunden, die alle ausgewählten Tags enthalten. Bitte wähle weniger oder andere Tags aus.</p>
    </div>
<?php endif; ?>

<?php if ($paged === 1) : ?>
        </main>
        <?php get_sidebar(); ?>
    </div>
</div>
<?php else : ?>
        </main>
        <?php get_sidebar(); ?>
    </div>
</div>
<?php endif;

get_footer();
