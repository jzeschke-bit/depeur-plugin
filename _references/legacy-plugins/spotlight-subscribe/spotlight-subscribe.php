<?php
/*
Plugin Name: Depeur Spotlight
Description: Zeigt ein Spotlight-Aboformular an der Stelle eines Newsletter-Markers in Blogposts.
Version: 1.2
Author: Jonas Zeschke
*/

// Plugin-Konfiguration
define('SPOTLIGHT_PLUGIN_VERSION', '1.2');
define('SPOTLIGHT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPOTLIGHT_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin-Aktivierung
 */
function spotlight_plugin_activation() {
    // Standard-Einstellungen setzen
    $default_settings = array(
        // Allgemeine Einstellungen
        'auto_insert_enabled' => true,
        
        // Newsletter-Einstellungen
        'newsletter_enabled' => true,
        'newsletter_position' => 4,
        'newsletter_show_on_desktop' => true,
        'newsletter_show_on_mobile' => true,
        'newsletter_show_on_pages' => true,
        'newsletter_show_on_posts' => true,
        'newsletter_show_on_blog' => true,
        'newsletter_show_on_tests' => true,
        'newsletter_show_on_cocktails' => false,
        'newsletter_show_on_trinkspiel' => false,
        'newsletter_show_on_bar_equipment' => false,
        'newsletter_title' => 'Inspiration direkt in deinem Postfach!',
        'newsletter_subtitle' => 'Erhalte kostenlos jede Woche neue Rezepte, die wirklich schmecken. Direkt in deinem Postfach. Einfach, gesund und perfekt für deinen Alltag.',
        'newsletter_button_text' => 'Jetzt kostenlos anmelden',
        'newsletter_placeholder' => 'Deine E-Mail Adresse',
        'newsletter_image' => 'https://alkipedia.com/wp-content/uploads/Newsletter-Slide-In.jpg',
        'newsletter_success_url' => 'https://alkipedia.com/newsletter-danke/?subscribed=true',
        'newsletter_form_id' => '68319b10b61ee160f25775e2',
        'newsletter_form_action' => 'https://form.flodesk.com/forms/68319b10b61ee160f25775e2/submit',
        
        // App-Promotion Einstellungen
        'app_promo_enabled' => true,
        'app_promo_position' => 1,
        'app_promo_show_on_desktop' => true,
        'app_promo_show_on_mobile' => true,
        'app_promo_show_on_pages' => true,
        'app_promo_show_on_posts' => true,
        'app_promo_show_on_blog' => false,
        'app_promo_show_on_tests' => false,
        'app_promo_show_on_cocktails' => false,
        'app_promo_show_on_trinkspiel' => false,
        'app_promo_show_on_bar_equipment' => false,
        'app_promo_title' => 'FitTasteTic App',
        'app_promo_subtitle' => 'Rezepte für unterwegs',
        'app_promo_button_text' => 'Download',
        'app_promo_button_url' => 'https://fittastetic.app.link/download',
        'app_promo_image' => 'https://alkipedia.com/wp-content/uploads/2019/11/cropped-app_icon_red_orange.png',
    );
    
    add_option('spotlight_settings', $default_settings);
}
register_activation_hook(__FILE__, 'spotlight_plugin_activation');

/**
 * Plugin-Deaktivierung
 */
function spotlight_plugin_deactivation() {
    // Optional: Einstellungen beim Deaktivieren löschen
    // delete_option('spotlight_settings');
}
register_deactivation_hook(__FILE__, 'spotlight_plugin_deactivation');

/**
 * Admin-Menü hinzufügen
 */
function spotlight_admin_menu() {
    add_options_page(
        'Spotlight Subscribe Einstellungen',
        'Spotlight Subscribe',
        'manage_options',
        'spotlight-settings',
        'spotlight_admin_page'
    );
}
add_action('admin_menu', 'spotlight_admin_menu');

/**
 * Admin-Seite rendern
 */
function spotlight_admin_page() {
    // Einstellungen speichern
    if (isset($_POST['spotlight_save_settings'])) {
        spotlight_save_settings();
    }
    
    $settings = get_option('spotlight_settings', array());
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    
    <style>
        .spotlight-tabs {
            margin-bottom: 20px;
        }
        .spotlight-tabs .nav-tab {
            margin-right: 5px;
        }
        .spotlight-tab-content {
            display: none;
        }
        .spotlight-tab-content.active {
            display: block;
        }
        .spotlight-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .spotlight-section h3 {
            margin-top: 0;
            color: #23282d;
        }
        .device-options {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .device-option {
            flex: 1;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .device-option h4 {
            margin-top: 0;
            color: #23282d;
        }
    </style>
    
    <div class="wrap">
        <h1>Spotlight Subscribe Einstellungen</h1>
        
        <div class="spotlight-tabs">
            <a href="?page=spotlight-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                Allgemein
            </a>
            <a href="?page=spotlight-settings&tab=newsletter" class="nav-tab <?php echo $active_tab === 'newsletter' ? 'nav-tab-active' : ''; ?>">
                Newsletter
            </a>
            <a href="?page=spotlight-settings&tab=app" class="nav-tab <?php echo $active_tab === 'app' ? 'nav-tab-active' : ''; ?>">
                App-Promotion
            </a>
        </div>
        
        <form method="post" action="" id="spotlight-settings-form">
            <!-- Hidden fields will be populated by JavaScript with current database values -->
            <input type="hidden" name="spotlight_settings[auto_insert_enabled]" id="hidden_auto_insert_enabled" value="<?php echo esc_attr(spotlight_get_setting('auto_insert_enabled', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_enabled]" id="hidden_newsletter_enabled" value="<?php echo esc_attr(spotlight_get_setting('newsletter_enabled', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_position]" id="hidden_newsletter_position" value="<?php echo esc_attr(spotlight_get_setting('newsletter_position', 4)); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_desktop]" id="hidden_newsletter_show_on_desktop" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_desktop', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_mobile]" id="hidden_newsletter_show_on_mobile" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_mobile', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_pages]" id="hidden_newsletter_show_on_pages" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_pages', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_posts]" id="hidden_newsletter_show_on_posts" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_posts', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_blog]" id="hidden_newsletter_show_on_blog" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_blog', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_tests]" id="hidden_newsletter_show_on_tests" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_tests', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_cocktails]" id="hidden_newsletter_show_on_cocktails" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_cocktails', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_trinkspiel]" id="hidden_newsletter_show_on_trinkspiel" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_trinkspiel', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_show_on_bar_equipment]" id="hidden_newsletter_show_on_bar_equipment" value="<?php echo esc_attr(spotlight_get_setting('newsletter_show_on_bar_equipment', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_title]" id="hidden_newsletter_title" value="<?php echo esc_attr(spotlight_get_setting('newsletter_title', 'Inspiration direkt in deinem Postfach!')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_subtitle]" id="hidden_newsletter_subtitle" value="<?php echo esc_attr(spotlight_get_setting('newsletter_subtitle', 'Erhalte kostenlos jede Woche neue Rezepte, die wirklich schmecken. Direkt in deinem Postfach. Einfach, gesund und perfekt für deinen Alltag.')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_button_text]" id="hidden_newsletter_button_text" value="<?php echo esc_attr(spotlight_get_setting('newsletter_button_text', 'Jetzt kostenlos anmelden')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_placeholder]" id="hidden_newsletter_placeholder" value="<?php echo esc_attr(spotlight_get_setting('newsletter_placeholder', 'Deine E-Mail Adresse')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_image]" id="hidden_newsletter_image" value="<?php echo esc_attr(spotlight_get_setting('newsletter_image', 'https://alkipedia.com/wp-content/uploads/Newsletter-Slide-In.jpg')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_success_url]" id="hidden_newsletter_success_url" value="<?php echo esc_attr(spotlight_get_setting('newsletter_success_url', 'https://alkipedia.com/newsletter-danke/?subscribed=true')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_form_id]" id="hidden_newsletter_form_id" value="<?php echo esc_attr(spotlight_get_setting('newsletter_form_id', '68319b10b61ee160f25775e2')); ?>">
            <input type="hidden" name="spotlight_settings[newsletter_form_action]" id="hidden_newsletter_form_action" value="<?php echo esc_attr(spotlight_get_setting('newsletter_form_action', 'https://form.flodesk.com/forms/68319b10b61ee160f25775e2/submit')); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_enabled]" id="hidden_app_promo_enabled" value="<?php echo esc_attr(spotlight_get_setting('app_promo_enabled', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_position]" id="hidden_app_promo_position" value="<?php echo esc_attr(spotlight_get_setting('app_promo_position', 1)); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_desktop]" id="hidden_app_promo_show_on_desktop" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_desktop', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_mobile]" id="hidden_app_promo_show_on_mobile" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_mobile', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_pages]" id="hidden_app_promo_show_on_pages" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_pages', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_posts]" id="hidden_app_promo_show_on_posts" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_posts', true) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_blog]" id="hidden_app_promo_show_on_blog" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_blog', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_tests]" id="hidden_app_promo_show_on_tests" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_tests', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_cocktails]" id="hidden_app_promo_show_on_cocktails" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_cocktails', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_trinkspiel]" id="hidden_app_promo_show_on_trinkspiel" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_trinkspiel', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_show_on_bar_equipment]" id="hidden_app_promo_show_on_bar_equipment" value="<?php echo esc_attr(spotlight_get_setting('app_promo_show_on_bar_equipment', false) ? '1' : '0'); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_title]" id="hidden_app_promo_title" value="<?php echo esc_attr(spotlight_get_setting('app_promo_title', 'FitTasteTic App')); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_subtitle]" id="hidden_app_promo_subtitle" value="<?php echo esc_attr(spotlight_get_setting('app_promo_subtitle', 'Rezepte für unterwegs')); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_button_text]" id="hidden_app_promo_button_text" value="<?php echo esc_attr(spotlight_get_setting('app_promo_button_text', 'Download')); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_button_url]" id="hidden_app_promo_button_url" value="<?php echo esc_attr(spotlight_get_setting('app_promo_button_url', 'https://fittastetic.app.link/download')); ?>">
            <input type="hidden" name="spotlight_settings[app_promo_image]" id="hidden_app_promo_image" value="<?php echo esc_attr(spotlight_get_setting('app_promo_image', 'https://alkipedia.com/wp-content/uploads/2019/11/cropped-app_icon_red_orange.png')); ?>">
            
            <?php if ($active_tab === 'general'): ?>
                <!-- Allgemeine Einstellungen Tab -->
                <div class="spotlight-tab-content active">
                    <div class="spotlight-section">
                        <h3>Allgemeine Einstellungen</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Automatisches Einfügen</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[auto_insert_enabled]" value="1" 
                                                <?php checked(isset($settings['auto_insert_enabled']) ? $settings['auto_insert_enabled'] : true); ?>>
                                            Automatisches Einfügen aktivieren
                                        </label>
                                        <br><small>Wenn deaktiviert, müssen Spotlight-Elemente manuell eingefügt werden.</small>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Verwendung</h3>
                        <p>Spotlight-Elemente können automatisch oder manuell eingefügt werden:</p>
                        <ul>
                            <li><strong>Automatisch:</strong> Elemente werden basierend auf den Einstellungen automatisch eingefügt</li>
                            <li><strong>Manuell:</strong> Verwende Shortcodes in Beiträgen</li>
                        </ul>
                        
                        <h4>Shortcodes</h4>
                        <ul>
                            <li><code>[spotlight_newsletter]</code> - Newsletter-Formular anzeigen</li>
                            <li><code>[spotlight_app_promo]</code> - App-Promotion anzeigen</li>
                            <li><code>[spotlight_both]</code> - Beide Elemente anzeigen</li>
                        </ul>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'newsletter'): ?>
                <!-- Newsletter Tab -->
                <div class="spotlight-tab-content active">
                    <div class="spotlight-section">
                        <h3>Newsletter Aktivierung & Position</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Newsletter aktivieren</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_enabled]" value="1" 
                                                <?php checked(isset($settings['newsletter_enabled']) ? $settings['newsletter_enabled'] : true); ?>>
                                            Newsletter-Formular aktivieren
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Position</th>
                                <td>
                                    <label>
                                        Nach welchem Absatz soll der Newsletter erscheinen? 
                                        <input type="number" name="spotlight_settings[newsletter_position]" 
                                            value="<?php echo esc_attr(isset($settings['newsletter_position']) ? $settings['newsletter_position'] : 4); ?>" 
                                            min="1" max="20" style="width: 80px;">
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Device-spezifische Anzeige</h3>
                        <div class="device-options">
                            <div class="device-option">
                                <h4>Desktop</h4>
                                <label>
                                    <input type="checkbox" name="spotlight_settings[newsletter_show_on_desktop]" value="1" 
                                        <?php checked(isset($settings['newsletter_show_on_desktop']) ? $settings['newsletter_show_on_desktop'] : true); ?>>
                                    Auf Desktop anzeigen
                                </label>
                            </div>
                            <div class="device-option">
                                <h4>Mobile</h4>
                                <label>
                                    <input type="checkbox" name="spotlight_settings[newsletter_show_on_mobile]" value="1" 
                                        <?php checked(isset($settings['newsletter_show_on_mobile']) ? $settings['newsletter_show_on_mobile'] : true); ?>>
                                    Auf Mobile anzeigen
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Anzeige auf Post-Typen</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Post-Typen</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_pages]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_pages']) ? $settings['newsletter_show_on_pages'] : true); ?>>
                                            Seiten (Pages)
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_posts]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_posts']) ? $settings['newsletter_show_on_posts'] : true); ?>>
                                            Standard-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_blog]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_blog']) ? $settings['newsletter_show_on_blog'] : true); ?>>
                                            Blog-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_tests]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_tests']) ? $settings['newsletter_show_on_tests'] : true); ?>>
                                            Test-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_cocktails]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_cocktails']) ? $settings['newsletter_show_on_cocktails'] : false); ?>>
                                            Cocktails
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_trinkspiel]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_trinkspiel']) ? $settings['newsletter_show_on_trinkspiel'] : false); ?>>
                                            Trinkspiele
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[newsletter_show_on_bar_equipment]" value="1" 
                                                <?php checked(isset($settings['newsletter_show_on_bar_equipment']) ? $settings['newsletter_show_on_bar_equipment'] : false); ?>>
                                            Bar-Equipment
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Newsletter-Inhalt</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Titel</th>
                                <td>
                                    <input type="text" name="spotlight_settings[newsletter_title]" 
                                        value="<?php echo esc_attr(isset($settings['newsletter_title']) ? $settings['newsletter_title'] : 'Inspiration direkt in deinem Postfach!'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Untertitel</th>
                                <td>
                                    <textarea name="spotlight_settings[newsletter_subtitle]" 
                                        style="width: 100%; height: 80px;"><?php echo esc_textarea(isset($settings['newsletter_subtitle']) ? $settings['newsletter_subtitle'] : 'Erhalte kostenlos jede Woche neue Rezepte, die wirklich schmecken. Direkt in deinem Postfach. Einfach, gesund und perfekt für deinen Alltag.'); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Button-Text</th>
                                <td>
                                    <input type="text" name="spotlight_settings[newsletter_button_text]" 
                                        value="<?php echo esc_attr(isset($settings['newsletter_button_text']) ? $settings['newsletter_button_text'] : 'Jetzt kostenlos anmelden'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">E-Mail Placeholder</th>
                                <td>
                                    <input type="text" name="spotlight_settings[newsletter_placeholder]" 
                                        value="<?php echo esc_attr(isset($settings['newsletter_placeholder']) ? $settings['newsletter_placeholder'] : 'Deine E-Mail Adresse'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Newsletter-Bild URL</th>
                                <td>
                                                                    <input type="url" name="spotlight_settings[newsletter_image]" 
                                    value="<?php echo esc_url(isset($settings['newsletter_image']) ? $settings['newsletter_image'] : 'https://alkipedia.com/wp-content/uploads/Newsletter-Slide-In.jpg'); ?>" 
                                    style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Erfolgs-URL</th>
                                <td>
                                                                    <input type="url" name="spotlight_settings[newsletter_success_url]" 
                                    value="<?php echo esc_url(isset($settings['newsletter_success_url']) ? $settings['newsletter_success_url'] : 'https://alkipedia.com/newsletter-danke/?subscribed=true'); ?>" 
                                    style="width: 100%;">
                                    <br><small>URL nach erfolgreicher Anmeldung</small>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Formular-ID (Flodesk)</th>
                                <td>
                                    <input type="text" name="spotlight_settings[newsletter_form_id]" 
                                        value="<?php echo esc_attr(isset($settings['newsletter_form_id']) ? $settings['newsletter_form_id'] : '68319b10b61ee160f25775e2'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Formular-Action URL</th>
                                <td>
                                    <input type="url" name="spotlight_settings[newsletter_form_action]" 
                                        value="<?php echo esc_url(isset($settings['newsletter_form_action']) ? $settings['newsletter_form_action'] : 'https://form.flodesk.com/forms/68319b10b61ee160f25775e2/submit'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'app'): ?>
                <!-- App-Promotion Tab -->
                <div class="spotlight-tab-content active">
                    <div class="spotlight-section">
                        <h3>App-Promotion Aktivierung & Position</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">App-Promotion aktivieren</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_enabled]" value="1" 
                                                <?php checked(isset($settings['app_promo_enabled']) ? $settings['app_promo_enabled'] : true); ?>>
                                            App-Promotion aktivieren
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Position</th>
                                <td>
                                    <label>
                                        Nach welchem Absatz soll die App-Promotion erscheinen? 
                                        <input type="number" name="spotlight_settings[app_promo_position]" 
                                            value="<?php echo esc_attr(isset($settings['app_promo_position']) ? $settings['app_promo_position'] : 1); ?>" 
                                            min="1" max="20" style="width: 80px;">
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Device-spezifische Anzeige</h3>
                        <div class="device-options">
                            <div class="device-option">
                                <h4>Desktop</h4>
                                <label>
                                    <input type="checkbox" name="spotlight_settings[app_promo_show_on_desktop]" value="1" 
                                        <?php checked(isset($settings['app_promo_show_on_desktop']) ? $settings['app_promo_show_on_desktop'] : true); ?>>
                                    Auf Desktop anzeigen
                                </label>
                            </div>
                            <div class="device-option">
                                <h4>Mobile</h4>
                                <label>
                                    <input type="checkbox" name="spotlight_settings[app_promo_show_on_mobile]" value="1" 
                                        <?php checked(isset($settings['app_promo_show_on_mobile']) ? $settings['app_promo_show_on_mobile'] : true); ?>>
                                    Auf Mobile anzeigen
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>Anzeige auf Post-Typen</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Post-Typen</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_pages]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_pages']) ? $settings['app_promo_show_on_pages'] : true); ?>>
                                            Seiten (Pages)
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_posts]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_posts']) ? $settings['app_promo_show_on_posts'] : true); ?>>
                                            Standard-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_blog]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_blog']) ? $settings['app_promo_show_on_blog'] : false); ?>>
                                            Blog-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_tests]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_tests']) ? $settings['app_promo_show_on_tests'] : false); ?>>
                                            Test-Posts
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_cocktails]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_cocktails']) ? $settings['app_promo_show_on_cocktails'] : false); ?>>
                                            Cocktails
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_trinkspiel]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_trinkspiel']) ? $settings['app_promo_show_on_trinkspiel'] : false); ?>>
                                            Trinkspiele
                                        </label><br>
                                        
                                        <label>
                                            <input type="checkbox" name="spotlight_settings[app_promo_show_on_bar_equipment]" value="1" 
                                                <?php checked(isset($settings['app_promo_show_on_bar_equipment']) ? $settings['app_promo_show_on_bar_equipment'] : false); ?>>
                                            Bar-Equipment
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="spotlight-section">
                        <h3>App-Promotion Inhalt</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">App-Titel</th>
                                <td>
                                    <input type="text" name="spotlight_settings[app_promo_title]" 
                                        value="<?php echo esc_attr(isset($settings['app_promo_title']) ? $settings['app_promo_title'] : 'FitTasteTic App'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">App-Untertitel</th>
                                <td>
                                    <input type="text" name="spotlight_settings[app_promo_subtitle]" 
                                        value="<?php echo esc_attr(isset($settings['app_promo_subtitle']) ? $settings['app_promo_subtitle'] : 'Rezepte für unterwegs'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Button-Text</th>
                                <td>
                                    <input type="text" name="spotlight_settings[app_promo_button_text]" 
                                        value="<?php echo esc_attr(isset($settings['app_promo_button_text']) ? $settings['app_promo_button_text'] : 'Download'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Download-URL</th>
                                <td>
                                    <input type="url" name="spotlight_settings[app_promo_button_url]" 
                                        value="<?php echo esc_url(isset($settings['app_promo_button_url']) ? $settings['app_promo_button_url'] : 'https://fittastetic.app.link/download'); ?>" 
                                        style="width: 100%;">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">App-Icon URL</th>
                                <td>
                                                                    <input type="url" name="spotlight_settings[app_promo_image]" 
                                    value="<?php echo esc_url(isset($settings['app_promo_image']) ? $settings['app_promo_image'] : 'https://alkipedia.com/wp-content/uploads/2019/11/cropped-app_icon_red_orange.png'); ?>" 
                                    style="width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php submit_button('Einstellungen speichern', 'primary', 'spotlight_save_settings'); ?>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            // Hidden fields are already populated with current database values via PHP
            
            // Update hidden fields when form is submitted
            $('#spotlight-settings-form').on('submit', function() {
                // Read current form values and update hidden fields accordingly
                updateHiddenFieldsFromForm();
            });
            
            // Function to read form values and update hidden fields
            function updateHiddenFieldsFromForm() {
                // Get current active tab
                var activeTab = '<?php echo $active_tab; ?>';
                
                // General settings - only update if on general tab
                if (activeTab === 'general') {
                    $('#hidden_auto_insert_enabled').val($('input[name="spotlight_settings[auto_insert_enabled]"]:checked').length > 0 ? '1' : '0');
                }
                
                // Newsletter settings - only update if on newsletter tab
                if (activeTab === 'newsletter') {
                    var newsletterEnabled = $('input[name="spotlight_settings[newsletter_enabled]"]:checked').length > 0;
                    $('#hidden_newsletter_enabled').val(newsletterEnabled ? '1' : '0');
                    
                    var newsletterPosition = $('input[name="spotlight_settings[newsletter_position]"]').val();
                    if (newsletterPosition) {
                        $('#hidden_newsletter_position').val(newsletterPosition);
                    }
                    
                    var newsletterShowOnDesktop = $('input[name="spotlight_settings[newsletter_show_on_desktop]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_desktop').val(newsletterShowOnDesktop ? '1' : '0');
                    
                    var newsletterShowOnMobile = $('input[name="spotlight_settings[newsletter_show_on_mobile]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_mobile').val(newsletterShowOnMobile ? '1' : '0');
                    
                    var newsletterShowOnPages = $('input[name="spotlight_settings[newsletter_show_on_pages]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_pages').val(newsletterShowOnPages ? '1' : '0');
                    
                    var newsletterShowOnPosts = $('input[name="spotlight_settings[newsletter_show_on_posts]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_posts').val(newsletterShowOnPosts ? '1' : '0');
                    
                    var newsletterShowOnBlog = $('input[name="spotlight_settings[newsletter_show_on_blog]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_blog').val(newsletterShowOnBlog ? '1' : '0');
                    
                    var newsletterShowOnTests = $('input[name="spotlight_settings[newsletter_show_on_tests]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_tests').val(newsletterShowOnTests ? '1' : '0');
                    
                    var newsletterShowOnCocktails = $('input[name="spotlight_settings[newsletter_show_on_cocktails]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_cocktails').val(newsletterShowOnCocktails ? '1' : '0');
                    
                    var newsletterShowOnTrinkspiel = $('input[name="spotlight_settings[newsletter_show_on_trinkspiel]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_trinkspiel').val(newsletterShowOnTrinkspiel ? '1' : '0');
                    
                    var newsletterShowOnBarEquipment = $('input[name="spotlight_settings[newsletter_show_on_bar_equipment]"]:checked').length > 0;
                    $('#hidden_newsletter_show_on_bar_equipment').val(newsletterShowOnBarEquipment ? '1' : '0');
                    
                    var newsletterTitle = $('input[name="spotlight_settings[newsletter_title]"]').val();
                    if (newsletterTitle) {
                        $('#hidden_newsletter_title').val(newsletterTitle);
                    }
                    
                    var newsletterSubtitle = $('textarea[name="spotlight_settings[newsletter_subtitle]"]').val();
                    if (newsletterSubtitle) {
                        $('#hidden_newsletter_subtitle').val(newsletterSubtitle);
                    }
                    
                    var newsletterButtonText = $('input[name="spotlight_settings[newsletter_button_text]"]').val();
                    if (newsletterButtonText) {
                        $('#hidden_newsletter_button_text').val(newsletterButtonText);
                    }
                    
                    var newsletterPlaceholder = $('input[name="spotlight_settings[newsletter_placeholder]"]').val();
                    if (newsletterPlaceholder) {
                        $('#hidden_newsletter_placeholder').val(newsletterPlaceholder);
                    }
                    
                    var newsletterImage = $('input[name="spotlight_settings[newsletter_image]"]').val();
                    if (newsletterImage) {
                        $('#hidden_newsletter_image').val(newsletterImage);
                    }
                    
                    var newsletterSuccessUrl = $('input[name="spotlight_settings[newsletter_success_url]"]').val();
                    if (newsletterSuccessUrl) {
                        $('#hidden_newsletter_success_url').val(newsletterSuccessUrl);
                    }
                    
                    var newsletterFormId = $('input[name="spotlight_settings[newsletter_form_id]"]').val();
                    if (newsletterFormId) {
                        $('#hidden_newsletter_form_id').val(newsletterFormId);
                    }
                    
                    var newsletterFormAction = $('input[name="spotlight_settings[newsletter_form_action]"]').val();
                    if (newsletterFormAction) {
                        $('#hidden_newsletter_form_action').val(newsletterFormAction);
                    }
                }
                
                // App-Promotion settings - only update if on app tab
                if (activeTab === 'app') {
                    var appPromoEnabled = $('input[name="spotlight_settings[app_promo_enabled]"]:checked').length > 0;
                    $('#hidden_app_promo_enabled').val(appPromoEnabled ? '1' : '0');
                    
                    var appPromoPosition = $('input[name="spotlight_settings[app_promo_position]"]').val();
                    if (appPromoPosition) {
                        $('#hidden_app_promo_position').val(appPromoPosition);
                    }
                    
                    var appPromoShowOnDesktop = $('input[name="spotlight_settings[app_promo_show_on_desktop]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_desktop').val(appPromoShowOnDesktop ? '1' : '0');
                    
                    var appPromoShowOnMobile = $('input[name="spotlight_settings[app_promo_show_on_mobile]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_mobile').val(appPromoShowOnMobile ? '1' : '0');
                    
                    var appPromoShowOnPages = $('input[name="spotlight_settings[app_promo_show_on_pages]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_pages').val(appPromoShowOnPages ? '1' : '0');
                    
                    var appPromoShowOnPosts = $('input[name="spotlight_settings[app_promo_show_on_posts]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_posts').val(appPromoShowOnPosts ? '1' : '0');
                    
                    var appPromoShowOnBlog = $('input[name="spotlight_settings[app_promo_show_on_blog]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_blog').val(appPromoShowOnBlog ? '1' : '0');
                    
                    var appPromoShowOnTests = $('input[name="spotlight_settings[app_promo_show_on_tests]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_tests').val(appPromoShowOnTests ? '1' : '0');
                    
                    var appPromoShowOnCocktails = $('input[name="spotlight_settings[app_promo_show_on_cocktails]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_cocktails').val(appPromoShowOnCocktails ? '1' : '0');
                    
                    var appPromoShowOnTrinkspiel = $('input[name="spotlight_settings[app_promo_show_on_trinkspiel]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_trinkspiel').val(appPromoShowOnTrinkspiel ? '1' : '0');
                    
                    var appPromoShowOnBarEquipment = $('input[name="spotlight_settings[app_promo_show_on_bar_equipment]"]:checked').length > 0;
                    $('#hidden_app_promo_show_on_bar_equipment').val(appPromoShowOnBarEquipment ? '1' : '0');
                    
                    var appPromoTitle = $('input[name="spotlight_settings[app_promo_title]"]').val();
                    if (appPromoTitle) {
                        $('#hidden_app_promo_title').val(appPromoTitle);
                    }
                    
                    var appPromoSubtitle = $('input[name="spotlight_settings[app_promo_subtitle]"]').val();
                    if (appPromoSubtitle) {
                        $('#hidden_app_promo_subtitle').val(appPromoSubtitle);
                    }
                    
                    var appPromoButtonText = $('input[name="spotlight_settings[app_promo_button_text]"]').val();
                    if (appPromoButtonText) {
                        $('#hidden_app_promo_button_text').val(appPromoButtonText);
                    }
                    
                    var appPromoButtonUrl = $('input[name="spotlight_settings[app_promo_button_url]"]').val();
                    if (appPromoButtonUrl) {
                        $('#hidden_app_promo_button_url').val(appPromoButtonUrl);
                    }
                    
                    var appPromoImage = $('input[name="spotlight_settings[app_promo_image]"]').val();
                    if (appPromoImage) {
                        $('#hidden_app_promo_image').val(appPromoImage);
                    }
                }
            }
        });
        </script>
    </div>
    <?php
}

/**
 * Einstellungen speichern
 */
function spotlight_save_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['spotlight_settings'])) {
        $settings = array_map('sanitize_text_field', $_POST['spotlight_settings']);
        
        // Checkbox-Werte korrekt setzen
        $checkbox_fields = array(
            'auto_insert_enabled',
            'newsletter_enabled', 'newsletter_show_on_desktop', 'newsletter_show_on_mobile',
            'newsletter_show_on_pages', 'newsletter_show_on_posts', 'newsletter_show_on_blog', 'newsletter_show_on_tests',
            'newsletter_show_on_cocktails', 'newsletter_show_on_trinkspiel', 'newsletter_show_on_bar_equipment',
            'app_promo_enabled', 'app_promo_show_on_desktop', 'app_promo_show_on_mobile',
            'app_promo_show_on_pages', 'app_promo_show_on_posts', 'app_promo_show_on_blog', 'app_promo_show_on_tests',
            'app_promo_show_on_cocktails', 'app_promo_show_on_trinkspiel', 'app_promo_show_on_bar_equipment'
        );
        
        // Text-Felder sanitieren
        $text_fields = array(
            'newsletter_title', 'newsletter_subtitle', 'newsletter_button_text', 'newsletter_placeholder',
            'newsletter_image', 'newsletter_success_url', 'newsletter_form_id', 'newsletter_form_action',
            'app_promo_title', 'app_promo_subtitle', 'app_promo_button_text', 'app_promo_button_url', 'app_promo_image'
        );
        
        foreach ($text_fields as $field) {
            if (isset($settings[$field])) {
                $settings[$field] = sanitize_text_field($settings[$field]);
            }
        }
        
        foreach ($checkbox_fields as $field) {
            // Für versteckte Felder: '1' oder '0' String zu Boolean konvertieren
            if (isset($settings[$field])) {
                $settings[$field] = ($settings[$field] === '1' || $settings[$field] === true);
            } else {
                $settings[$field] = false;
            }
        }
        
        update_option('spotlight_settings', $settings);
        echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
    }
}

/**
 * Einstellungen abrufen
 */
function spotlight_get_setting($key, $default = false) {
    $settings = get_option('spotlight_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Prüfen ob Newsletter auf aktuellem Post-Type angezeigt werden soll
 */
function spotlight_should_show_newsletter_on_current_post_type() {
    $post_type = get_post_type();
    
    switch ($post_type) {
        case 'page':
            return spotlight_get_setting('newsletter_show_on_pages', true);
        case 'post':
            return spotlight_get_setting('newsletter_show_on_posts', true);
        case 'blog':
            return spotlight_get_setting('newsletter_show_on_blog', true);
        case 'tests':
            return spotlight_get_setting('newsletter_show_on_tests', true);
        case 'cocktails':
            return spotlight_get_setting('newsletter_show_on_cocktails', false);
        case 'trinkspiel':
            return spotlight_get_setting('newsletter_show_on_trinkspiel', false);
        case 'bar-equipment':
            return spotlight_get_setting('newsletter_show_on_bar_equipment', false);
        default:
            return false;
    }
}

/**
 * Prüfen ob App-Promotion auf aktuellem Post-Type angezeigt werden soll
 */
function spotlight_should_show_app_promo_on_current_post_type() {
    $post_type = get_post_type();
    
    switch ($post_type) {
        case 'page':
            return spotlight_get_setting('app_promo_show_on_pages', true);
        case 'post':
            return spotlight_get_setting('app_promo_show_on_posts', true);
        case 'blog':
            return spotlight_get_setting('app_promo_show_on_blog', false);
        case 'tests':
            return spotlight_get_setting('app_promo_show_on_tests', false);
        case 'cocktails':
            return spotlight_get_setting('app_promo_show_on_cocktails', false);
        case 'trinkspiel':
            return spotlight_get_setting('app_promo_show_on_trinkspiel', false);
        case 'bar-equipment':
            return spotlight_get_setting('app_promo_show_on_bar_equipment', false);
        default:
            return false;
    }
}

/**
 * Prüfen ob Element auf aktuellem Device angezeigt werden soll
 */
function spotlight_should_show_on_current_device($element_type = 'newsletter') {
    $is_mobile = wp_is_mobile();
    
    if ($element_type === 'newsletter') {
        return $is_mobile ? 
            spotlight_get_setting('newsletter_show_on_mobile', true) : 
            spotlight_get_setting('newsletter_show_on_desktop', true);
    } else {
        return $is_mobile ? 
            spotlight_get_setting('app_promo_show_on_mobile', true) : 
            spotlight_get_setting('app_promo_show_on_desktop', true);
    }
}

// Register ACF Fields
function spotlight_register_acf_fields() {
    if(function_exists('acf_add_local_field_group')):

        // Feld-Definitionen für alle Post Types
        $newsletter_field = array(
            'key' => 'field_show_newsletter',
            'label' => 'Newsletter-Formular anzeigen',
            'name' => 'show_newsletter_form',
            'type' => 'true_false',
            'instructions' => 'Aktiviert das Newsletter-Formular auf dieser Seite',
            'ui' => 1,
            'show_column' => true,
            'show_column_weight' => 1000,
        );

        $newsletter_position_field = array(
            'key' => 'field_newsletter_position',
            'label' => 'Newsletter Position',
            'name' => 'newsletter_position',
            'type' => 'number',
            'instructions' => 'Nach welchem Absatz soll der Newsletter erscheinen? (Standard: 4)',
            'default_value' => 4,
            'min' => 1,
            'max' => 20,
            'step' => 1,
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_show_newsletter',
                        'operator' => '==',
                        'value' => '1',
                    )
                )
            )
        );

        $app_promo_field = array(
            'key' => 'field_show_app_promo',
            'label' => 'App-Promotion anzeigen',
            'name' => 'show_app_promo',
            'type' => 'true_false',
            'instructions' => 'Aktiviert die App-Promotion auf dieser Seite',
            'ui' => 1,
            'show_column' => true,
            'show_column_weight' => 1001,
        );

        // Feldgruppe für normale Seiten (beide standardmäßig aktiviert)
        acf_add_local_field_group(array(
            'key' => 'group_spotlight_options_pages',
            'title' => 'Spotlight Promotions',
            'fields' => array(
                array_merge($newsletter_field, array(
                    'default_value' => 1,
                )),
                $newsletter_position_field,
                array_merge($app_promo_field, array(
                    'default_value' => 1,
                )),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ),
                ),
                array(
                    array(
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'single-rezeptkategorie-template.php',
                    ),
                ),
            ),
            'position' => 'side',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        ));

        // Feldgruppe für Blog und Tests (Newsletter standardmäßig aktiv, App deaktiviert)
        acf_add_local_field_group(array(
            'key' => 'group_spotlight_options_cpt',
            'title' => 'Spotlight Promotions',
            'fields' => array(
                array_merge($newsletter_field, array(
                    'default_value' => 1,
                )),
                $newsletter_position_field,
                array_merge($app_promo_field, array(
                    'default_value' => 0,
                )),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'blog',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'tests',
                    ),
                ),
            ),
            'position' => 'side',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        ));

    endif;
}
add_action('acf/init', 'spotlight_register_acf_fields');

function spotlight_enqueue_assets() {
    // Generate a version number based on file modification time to prevent caching
    $css_version = filemtime(plugin_dir_path(__FILE__) . 'assets/subscribe.css');
    $js_version = filemtime(plugin_dir_path(__FILE__) . 'assets/subscribe.js');
    
    // Enqueue our plugin assets with dynamic versions
    wp_enqueue_style('spotlight-subscribe-css', plugin_dir_url(__FILE__) . 'assets/subscribe.css', [], $css_version);
    wp_enqueue_script('spotlight-subscribe-js', plugin_dir_url(__FILE__) . 'assets/subscribe.js', [], $js_version, true);
}
add_action('wp_enqueue_scripts', 'spotlight_enqueue_assets');

function spotlight_insert_elements($content, $show_newsletter, $show_app_promo) {
    // Newsletter form HTML mit konfigurierbaren Inhalten
    $newsletter_form_id = spotlight_get_setting('newsletter_form_id', '68319b10b61ee160f25775e2');
    $newsletter_form_action = spotlight_get_setting('newsletter_form_action', 'https://form.flodesk.com/forms/68319b10b61ee160f25775e2/submit');
    $newsletter_success_url = spotlight_get_setting('newsletter_success_url', '/newsletter-danke/?subscribed=true');
    $newsletter_image = spotlight_get_setting('newsletter_image', '/wp-content/uploads/Newsletter-Slide-In.jpg');
    $newsletter_title = spotlight_get_setting('newsletter_title', 'Inspiration direkt in deinem Postfach!');
    $newsletter_subtitle = spotlight_get_setting('newsletter_subtitle', 'Erhalte kostenlos jede Woche neue Rezepte, die wirklich schmecken. Direkt in deinem Postfach. Einfach, gesund und perfekt für deinen Alltag.');
    $newsletter_button_text = spotlight_get_setting('newsletter_button_text', 'Jetzt kostenlos anmelden');
    $newsletter_placeholder = spotlight_get_setting('newsletter_placeholder', 'Deine E-Mail Adresse');
    
    $newsletter_form_html = '<div class="spotlight-subscribe-wrapper">
            <button class="spotlight-close-button" aria-label="Newsletter-Formular schließen">×</button>
            <div class="ff-' . esc_attr($newsletter_form_id) . '" data-ff-el="root" data-ff-version="3" data-ff-type="inline" data-ff-name="inlineImage">
                <div class="ff-' . esc_attr($newsletter_form_id) . '__container">
                    <form class="ff-' . esc_attr($newsletter_form_id) . '__wrapper" 
                        action="' . esc_url($newsletter_form_action) . '" 
                        method="post" 
                        data-ff-el="form" 
                        data-ff-embed="inline"
                        data-ff-layout-type="inline"
                        data-success-url="' . esc_url($newsletter_success_url) . '">
                        <div class="ff-' . esc_attr($newsletter_form_id) . '__left">
                            <div class="ff-' . esc_attr($newsletter_form_id) . '__image">
                                <img src="' . esc_url($newsletter_image) . '" alt="Newsletter Anmeldung" />
                            </div>
                        </div>
                        <div class="ff-' . esc_attr($newsletter_form_id) . '__right">
                            <div class="ff-' . esc_attr($newsletter_form_id) . '__title">
                                <div><strong>' . esc_html($newsletter_title) . '</strong></div>
                            </div>
                            <div class="ff-' . esc_attr($newsletter_form_id) . '__subtitle">
                                <div>' . esc_html($newsletter_subtitle) . '</div>
                            </div>
                            <div class="ff-' . esc_attr($newsletter_form_id) . '__content fd-form-content" data-ff-el="content">
                                <div class="ff-' . esc_attr($newsletter_form_id) . '__fields" data-ff-el="fields">
                                    <div class="ff-' . esc_attr($newsletter_form_id) . '__field fd-form-group">
                                        <input id="ff-' . esc_attr($newsletter_form_id) . '-email" 
                                            class="ff-' . esc_attr($newsletter_form_id) . '__control fd-form-control" 
                                            type="email" 
                                            maxlength="255" 
                                            name="email" 
                                            placeholder="' . esc_attr($newsletter_placeholder) . '" 
                                            data-ff-tab="email::submit"
                                            data-ff-validate="true"
                                            required />
                                    </div>
                                    <input type="text" maxlength="255" name="confirm_email_address" style="display: none" />
                                </div>
                                <div class="ff-' . esc_attr($newsletter_form_id) . '__footer" data-ff-el="footer">
                                    <button type="submit" 
                                        class="ff-' . esc_attr($newsletter_form_id) . '__button fd-btn kt-btn button kt-btn-size-normal kt-btn-style-primary" 
                                        data-ff-el="submit" 
                                        data-ff-tab="submit">
                                        <span>' . esc_html($newsletter_button_text) . '</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="spotlight-overlay"></div>
            <div class="spotlight-scroll-space"></div>
        </div>
        <script>
            window.fd("form:handle", {
                formId: "' . esc_js($newsletter_form_id) . '",
                rootEl: ".ff-' . esc_js($newsletter_form_id) . '",
                embedType: "inline"
            });
        </script>';

    // App promotion HTML mit konfigurierbaren Inhalten
    $app_promo_title = spotlight_get_setting('app_promo_title', 'FitTasteTic App');
    $app_promo_subtitle = spotlight_get_setting('app_promo_subtitle', 'Rezepte für unterwegs');
    $app_promo_button_text = spotlight_get_setting('app_promo_button_text', 'Download');
    $app_promo_button_url = spotlight_get_setting('app_promo_button_url', 'https://fittastetic.app.link/download');
    $app_promo_image = spotlight_get_setting('app_promo_image', '/wp-content/uploads/2019/11/cropped-app_icon_red_orange.png');
    
    $app_promo_html = '<div class="spotlight-app-promo">
        <div class="app-promo-container">
            <div class="app-promo-icon">
                <img src="' . esc_url($app_promo_image) . '" alt="' . esc_attr($app_promo_title) . ' Icon" />
            </div>
            <div class="app-promo-content">
                <b>' . esc_html($app_promo_title) . '</b>
                <p>' . esc_html($app_promo_subtitle) . '</p>
            </div>
            <div class="app-promo-cta">
                <a href="' . esc_url($app_promo_button_url) . '" class="app-store-button">' . esc_html($app_promo_button_text) . '</a>
            </div>
        </div>
    </div>';

    // Split content into paragraphs
    $parts = explode('</p>', $content);
    
    // Get custom newsletter position if set, otherwise use default from settings
    $newsletter_position = get_field('newsletter_position');
    if (!$newsletter_position) {
        $newsletter_position = spotlight_get_setting('newsletter_position', 4);
    }
    $newsletter_position = $newsletter_position - 1; // Convert to 0-based index
    
    // Get app promo position from settings
    $app_promo_position = spotlight_get_setting('app_promo_position', 1);
    $app_promo_position = $app_promo_position - 1; // Convert to 0-based index
    
    // If we have enough paragraphs and elements to show
    if (count($parts) >= 2) {
        // Add app promo at custom position if enabled
        if ($show_app_promo) {
            $insert_position = min($app_promo_position, count($parts) - 1);
            $parts[$insert_position] = $app_promo_html . $parts[$insert_position];
        }
        
        // Add newsletter form at custom position if enabled
        if ($show_newsletter) {
            // If position is beyond available paragraphs, add to last paragraph
            $insert_position = min($newsletter_position, count($parts) - 1);
            $parts[$insert_position] = $newsletter_form_html . $parts[$insert_position];
        }
        
        // Reassemble the content
        $content = implode('</p>', $parts);
    }
    // If we don't have enough paragraphs but want to show elements
    else {
        // Add elements at the beginning of the content
        if ($show_app_promo) {
            $content = $app_promo_html . $content;
        }
        if ($show_newsletter) {
            $content = $newsletter_form_html . $content;
        }
    }
    
    return $content;
}

function spotlight_insert_form($content) {
    // Prüfe ob automatisches Einfügen aktiviert ist
    if (!spotlight_get_setting('auto_insert_enabled', true)) {
        return $content;
    }
    
    // Prüfe Newsletter-Einstellungen
    $show_newsletter = false;
    if (spotlight_get_setting('newsletter_enabled', true) && 
        spotlight_should_show_newsletter_on_current_post_type() && 
        spotlight_should_show_on_current_device('newsletter')) {
        $show_newsletter = true;
    }
    
    // Prüfe App-Promotion-Einstellungen
    $show_app_promo = false;
    if (spotlight_get_setting('app_promo_enabled', true) && 
        spotlight_should_show_app_promo_on_current_post_type() && 
        spotlight_should_show_on_current_device('app')) {
        $show_app_promo = true;
    }
    
    // For pages or custom template, check ACF fields (these override settings)
    if (is_page() || get_page_template_slug() === 'single-rezeptkategorie-template.php') {
        $acf_newsletter = get_field('show_newsletter_form');
        $acf_app_promo = get_field('show_app_promo');
        
        // ACF fields override settings if they are set
        if ($acf_newsletter !== null) {
            $show_newsletter = $acf_newsletter && spotlight_should_show_on_current_device('newsletter');
        }
        if ($acf_app_promo !== null) {
            $show_app_promo = $acf_app_promo && spotlight_should_show_on_current_device('app');
        }
    }
    // For blog and tests, check ACF fields
    else if (is_single() && (get_post_type() === 'blog' || get_post_type() === 'tests')) {
        $acf_newsletter = get_field('show_newsletter_form');
        $acf_app_promo = get_field('show_app_promo');
        
        // ACF fields override settings if they are set
        if ($acf_newsletter !== null) {
            $show_newsletter = $acf_newsletter && spotlight_should_show_on_current_device('newsletter');
        }
        if ($acf_app_promo !== null) {
            $show_app_promo = $acf_app_promo && spotlight_should_show_on_current_device('app');
        }
    }
    
    // Only process if at least one element should be shown
    if ($show_newsletter || $show_app_promo) {
        return spotlight_insert_elements($content, $show_newsletter, $show_app_promo);
    }
    
    // Return unmodified content for all other cases
    return $content;
}

// Remove any existing filter to prevent duplication
remove_filter('the_content', 'spotlight_insert_form', 20);

// Add our filter with high priority to ensure it runs after other filters
add_filter('the_content', 'spotlight_insert_form', 99);

/**
 * Shortcode für Newsletter-Formular
 */
function spotlight_newsletter_shortcode($atts) {
    if (!spotlight_get_setting('newsletter_enabled', true)) {
        return '';
    }
    
    ob_start();
    spotlight_insert_elements('', true, false);
    return ob_get_clean();
}
add_shortcode('spotlight_newsletter', 'spotlight_newsletter_shortcode');

/**
 * Shortcode für App-Promotion
 */
function spotlight_app_promo_shortcode($atts) {
    if (!spotlight_get_setting('app_promo_enabled', true)) {
        return '';
    }
    
    ob_start();
    spotlight_insert_elements('', false, true);
    return ob_get_clean();
}
add_shortcode('spotlight_app_promo', 'spotlight_app_promo_shortcode');

/**
 * Shortcode für beide Elemente
 */
function spotlight_both_shortcode($atts) {
    $show_newsletter = spotlight_get_setting('newsletter_enabled', true);
    $show_app_promo = spotlight_get_setting('app_promo_enabled', true);
    
    if (!$show_newsletter && !$show_app_promo) {
        return '';
    }
    
    ob_start();
    spotlight_insert_elements('', $show_newsletter, $show_app_promo);
    return ob_get_clean();
}
add_shortcode('spotlight_both', 'spotlight_both_shortcode');

// Optional: Add support for specific Kadence template parts if needed
function spotlight_maybe_filter_template_content($content) {
    if (is_page() && function_exists('get_field')) {
        return apply_filters('the_content', $content);
    }
    return $content;
}
add_filter('kadence_single_content', 'spotlight_maybe_filter_template_content', 15);

// Optional: Add Gutenberg Block for the marker
function spotlight_register_block() {
    register_block_type('spotlight-subscribe/marker', array(
        'editor_script' => 'spotlight-marker',
        'render_callback' => function() {
            return '<div class="newsletter-marker"></div>';
        }
    ));
}
add_action('init', 'spotlight_register_block');
