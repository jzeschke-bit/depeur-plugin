<?php

class My_Favorite_Posts {

    public function init() {
        // Assets einbinden (CSS, JS)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Ajax-Handler registrieren
        add_action('wp_ajax_nopriv_my_favorite_post', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_my_favorite_post', array($this, 'handle_ajax_request'));

        // Shortcodes registrieren
        add_shortcode('thumbnail_favorite_button', array($this, 'render_thumbnail_favorite_button'));
        add_shortcode('inline_favorite_button', array($this, 'render_inline_favorite_button'));
        add_shortcode('favorite_posts_archive', array($this, 'render_favorite_posts_archive'));
		
        // WPRM Integration
        add_filter('wprm_recipe_image_container', array($this, 'add_favorite_button_to_wprm'), 10, 2);
        
        // Debug: Direktes Einfügen des Buttons in das Rezept Template
        add_filter('wprm_recipe_template_html', array($this, 'add_favorite_button_to_recipe_template'), 10, 2);
		
		// Button auf Archivseiten automatisch einfügen
    }

    /**
     * Fügt den Favoriten-Button direkt in das Rezept Template ein
     */
    public function add_favorite_button_to_recipe_template($html, $recipe) {
        if (!$recipe) {
            return $html;
        }
        
        // Erstelle den Button HTML
        $button_html = do_shortcode('[wprm-favorite-button id="' . $recipe->id() . '"]');

        // Füge den Button nach dem Bild ein
        $image_closing_tag = '</div>';
        $image_container_pos = strpos($html, 'wprm-recipe-image-container');
        
        if ($image_container_pos !== false) {
            $closing_tag_pos = strpos($html, $image_closing_tag, $image_container_pos);
            if ($closing_tag_pos !== false) {
                $html = substr_replace($html, $button_html . $image_closing_tag, $closing_tag_pos, strlen($image_closing_tag));
            }
        }

        return $html;
    }

    /**
     * Fügt den Favoriten-Button zu WPRM Rezeptbildern hinzu
     */
    public function add_favorite_button_to_wprm($image_container, $recipe_id) {
        if (!$recipe_id) {
            return $image_container;
        }

        // Button HTML für WPRM Rezepte über Shortcode
        $button_html = do_shortcode('[wprm-favorite-button id="' . $recipe_id . '"]');

        // Füge den Button nach dem Bild ein
        if (strpos($image_container, '</div>') !== false) {
            $image_container = str_replace('</div>', $button_html . '</div>', $image_container);
        } else {
            $image_container .= $button_html;
        }

        return $image_container;
    }

    public function enqueue_assets() {
        // CSS-Datei einbinden mit Cache-Busting durch filemtime
        wp_enqueue_style(
            'my-favorite-posts-style', 
            plugins_url('../assets/css/my-favorite-posts-style.css', __FILE__), 
            array(), 
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/my-favorite-posts-style.css')
        );

        // JavaScript-Datei einbinden mit Cache-Busting durch filemtime
        wp_enqueue_script(
            'my-favorite-posts-script', 
            plugins_url('../assets/js/my-favorite-posts-script.js', __FILE__), 
            array('jquery'), 
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/my-favorite-posts-script.js'), 
            true
        );

        // Lokalisierung der Ajax-URL für die JavaScript-Datei
        wp_localize_script('my-favorite-posts-script', 'MyFavoritePosts', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function handle_ajax_request() {
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $is_wprm = isset($_POST['is_wprm']) ? (bool)$_POST['is_wprm'] : false;

        if ($post_id) {
            // Wenn es ein WPRM Rezept ist, hole den übergeordneten Beitrag
            if ($is_wprm && function_exists('get_post_parent')) {
                $recipe_post = get_post($post_id);
                if ($recipe_post && $recipe_post->post_type === 'wprm_recipe') {
                    // Suche nach dem übergeordneten Beitrag des Rezepts
                    $parent_posts = get_posts(array(
                        'post_type' => 'post',
                        'posts_per_page' => 1,
                        'meta_query' => array(
                            array(
                                'key' => '_wprm_recipe_roundup_recipes',
                                'value' => $post_id,
                                'compare' => 'LIKE'
                            )
                        )
                    ));

                    if (!empty($parent_posts)) {
                        $post_id = $parent_posts[0]->ID;
                    }
                }
            }

            $favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();
            $like_count = get_post_meta($post_id, '_my_favorite_post_likes', true);
            $like_count = !empty($like_count) ? intval($like_count) : 0;

            if (in_array($post_id, $favorites)) {
                // Entfernen des Likes
                $favorites = array_diff($favorites, array($post_id));
                setcookie('my_favorite_posts', implode(',', $favorites), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
                
                // Like-Anzahl verringern
                if ($like_count > 0) {
                    $like_count--;
                    update_post_meta($post_id, '_my_favorite_post_likes', $like_count);
                }

                wp_send_json_success(array('message' => 'Beitrag aus Favoriten entfernt.'));
            } else {
                // Hinzufügen des Likes
                $favorites[] = $post_id;
                setcookie('my_favorite_posts', implode(',', $favorites), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

                // Like-Anzahl erhöhen
                $like_count++;
                update_post_meta($post_id, '_my_favorite_post_likes', $like_count);

                wp_send_json_success(array('message' => 'Beitrag zu Favoriten hinzugefügt.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Ungültiger Beitrag.'));
        }
    }

    public function render_thumbnail_favorite_button($atts) {
        /* Nicht anzeigen auf CPT-Archivseiten
        if (is_post_type_archive() && get_post_type() !== 'post') {
            return ''; // Button ausblenden
        }
 		*/
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
        ), $atts, 'thumbnail_favorite_button');

        $post_id = intval($atts['post_id']);
        $favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();
        
        $liked = in_array($post_id, $favorites) ? ' liked' : '';
        $button_text = in_array($post_id, $favorites) ? '♥' : '♡';

        // Button HTML für Thumbnails
        return '<div class="like-button-wrapper" style="position: absolute; top: 10px; right: 10px; z-index: 10;">' .
               '<button class="my-favorite-post-button' . $liked . '" data-post-id="' . $post_id . '">' . $button_text . '</button>' .
               '</div>';
    }

    public function render_inline_favorite_button($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
        ), $atts, 'inline_favorite_button');

        $post_id = intval($atts['post_id']);
        $favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();

        $liked = in_array($post_id, $favorites) ? ' liked' : '';
        $button_text = in_array($post_id, $favorites) ? '♥' : '♡';

        // Button HTML für Inline-Button ohne zusätzlichen Text
        return '<button class="my-favorite-post-inline-button' . $liked . '" data-post-id="' . $post_id . '">' . $button_text . '</button>';
    }

    public function render_favorite_posts_archive() {
        $favorites = isset($_COOKIE['my_favorite_posts']) ? explode(',', $_COOKIE['my_favorite_posts']) : array();

        if (empty($favorites)) {
            return '<p>Du hast noch keine Beiträge gespeichert.</p>';
        }

        $query = new WP_Query(array(
            'post_type' => array('post', 'blog', 'tests', 'cocktails', 'trinkspiel', 'bar-equipment'),
            'post__in' => $favorites,
            'orderby' => 'post__in',
            'posts_per_page' => -1,
        ));

        ob_start();

        if ($query->have_posts()) {
            echo '<div id="favorite-posts-archive">';
            while ($query->have_posts()) {
                $query->the_post();
                get_template_part('template-parts/content', get_post_format());
            }
            echo '</div>';
        } else {
            echo '<p>Keine gespeicherten Beiträge gefunden.</p>';
        }

        wp_reset_postdata();

        return ob_get_clean();
    }
}
