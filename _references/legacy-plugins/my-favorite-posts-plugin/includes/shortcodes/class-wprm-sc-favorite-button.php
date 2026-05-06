<?php
/**
 * Template shortcode for the favorite button.
 */

class WPRM_SC_Favorite_Button extends WPRM_Template_Shortcode {
    public static $shortcode = 'wprm-favorite-button';

    public static function init() {
        self::$attributes = array(
            'id' => array(
                'default' => '0',
            ),
            'style' => array(
                'default' => 'thumbnail', // thumbnail oder inline
            ),
        );

        parent::init();
    }

    public static function shortcode($atts) {
        $atts = parent::get_attributes($atts);

        // Preview im Template Editor
        if ($atts['is_template_editor_preview']) {
            return '<div style="background: rgba(0,0,0,0.5); color: white; padding: 4px 10px; display: inline-block; border-radius: 10px;">♡ Favorite Button</div>';
        }

        // Button-Klasse basierend auf Style
        $button_class = $atts['style'] === 'inline' ? 'my-favorite-post-inline-button' : 'wprm-recipe-favorite-button';
        
        // Wrapper-Klasse nur für Thumbnail-Style
        $wrapper_class = $atts['style'] === 'inline' ? '' : 'like-button-wrapper';
        
        // Button HTML
        $button_html = '<button class="' . esc_attr($button_class) . '" data-post-id="' . esc_attr($atts['id']) . '">♡</button>';
        
        // Wenn Thumbnail-Style, füge Wrapper hinzu
        if ($wrapper_class) {
            $button_html = '<div class="' . esc_attr($wrapper_class) . '">' . $button_html . '</div>';
        }

        return apply_filters(parent::get_hook(), $button_html, $atts);
    }
} 