<?php
/**
 * @wordpress-plugin
 * Plugin Name:     RunCloud Hub
 * Description:     RunCloud server-side caching for WordPress, Nginx FastCGI/Proxy Cache and Redis Object Cache
 * Author:          RunCloud
 * Author URI:      https://runcloud.io/
 * Version:         1.4.8
 * Requires at least: 4.9
 * License:         GPL-2.0 or later
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     runcloud-hub
 * Domain Path:     /languages
 */

/*
Copyright 2020 RunCloud.io
All rights reserved.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version
2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
with this program. If not, visit: https://www.gnu.org/licenses/
 */

/* 
More informations:

RunCloud Hub
https://blog.runcloud.io/runcloud-hub/

RunCache - Nginx FastCGI/Proxy Cache
https://blog.runcloud.io/nginx-fastcgi-cache/

Redis Object Cache
https://blog.runcloud.io/redis-object-cache/


*/

if (!defined('WPINC') || defined('RUNCLOUD_HUB_HOOK')) {
    exit;
}

/**
 * constant
 */
define('RUNCLOUD_HUB_FILE', __FILE__);
define('RUNCLOUD_HUB_HOOK', plugin_basename(RUNCLOUD_HUB_FILE));
define('RUNCLOUD_HUB_PATH', realpath(plugin_dir_path(RUNCLOUD_HUB_FILE)) . '/');
define('RUNCLOUD_HUB_PATH_LANG', RUNCLOUD_HUB_PATH . 'languages/');
define('RUNCLOUD_HUB_PATH_VIEW', RUNCLOUD_HUB_PATH . 'partials/');
define('RUNCLOUD_HUB_PATH_RSC', RUNCLOUD_HUB_PATH . 'assets/');
define('RUNCLOUD_HUB_PATH_LIB', RUNCLOUD_HUB_PATH . 'library/');
if (!defined('RUNCLOUD_HUB_VERSION')) {
    define('RUNCLOUD_HUB_VERSION', '1.4.8');
}
if (!defined('RUNCLOUD_HUB_VERSION_PREV')) {
    define('RUNCLOUD_HUB_VERSION_PREV', '1.4.7');
}

final class RunCloud_Hub
{
    // reference
    private static $name       = 'RunCloud Hub';
    private static $slug       = 'runcloud-hub';
    private static $islug      = 'runcloud';

    private static $db_version = 'runcloud_hub_version';
    private static $db_setting = 'runcloud_hub_config';
    private static $db_stats   = 'runcloud_hub_stats';
    private static $db_type    = 'runcloud_hub_type';
    private static $db_notice  = 'runcloud_hub_notice';
    private static $db_preload = 'runcloud_hub_preload';
    private static $db_network = 'runcloud_hub_network';
    private static $db_cloudflare = 'runcloud_hub_cloudflare';

    // version
    private static $version      = RUNCLOUD_HUB_VERSION;
    private static $version_prev = RUNCLOUD_HUB_VERSION_PREV;

    // later
    private static $hook       = '';
    private static $cache_path = '';
    private static $ordfirst;
    private static $ordlast;

    // url
    private static $plugin_url        = '';
    private static $plugin_url_assets = '';

    // view
    private static $checked = [];
    private static $value   = [];

    // is cond
    private static $is_run_preload = false;

    // purge prefix
    private static $purge_prefix_var = 'runcache-purge';
    private static $purge_prefix_all = 'runcache-purgeall';

    // done purge 
    private static $done_purgeall     = false;
    private static $done_purgesiteall = false;

    // helper
    private static $updated_posts = [];
    private static $updated_terms = [];

    // misc
    private static $dropin_file   = 'object-cache.php';
    private static $dropin_source = 'wp-object-cache.php';
    private static $dropin_config = 'rcwp-redis-config.php';
    private static $transientk    = 'runcloudhub';
    private static $magictoken    = '__rcmagictoken';
    private static $magicuserid   = '__rcmagicuserid';
    private static $magicexpiry   = '__rcmagicexpiry';
    private static $optpage       = false;
    private static $onfirehooks   = false;

    // api
    private static $endpoint = 'https://manage.runcloud.io/api/runcloud-hub';

    // update
    private static $trunk_url = 'https://manage.runcloud.io/resources/runcloud-hub/latest_version';
    private static $beta_url  = 'https://manage.runcloud.io/resources/runcloud-hub/beta_version';

    /**
     * is_wp_ssl.
     */
    private static function is_wp_ssl()
    {
        $scheme = parse_url(get_site_url(), PHP_URL_SCHEME);
        return ('https' === $scheme ? true : false);
    }

    /**
     * is_wp_cli.
     */
    private static function is_wp_cli()
    {
        return (defined('WP_CLI') && WP_CLI);
    }

    /**
     * is_defined_halt.
     */
    private static function is_defined_halt()
    {
        return defined('RUNCLOUD_HUB_HALT');
    }

    /**
     * define_halt.
     */
    private static function define_halt()
    {
        if (!self::is_defined_halt() && !self::is_wp_cli()) {
            define('RUNCLOUD_HUB_HALT', true);
        }
    }

    /**
     * is_apache.
     */
    private static function is_apache()
    {
        if (false !== strpos($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
            return true;
        }

        if ('apache2handler' === php_sapi_name()) {
            return true;
        }

        return false;
    }

    /**
     * is_nginx.
     */
    private static function is_nginx()
    {
        if (self::is_containerized()) {
            return true;
        }

        if (isset($GLOBALS['is_nginx']) && (bool) $GLOBALS['is_nginx']) {
            return true;
        }

        if (false !== strpos($_SERVER['SERVER_SOFTWARE'], 'nginx')) {
            return true;
        }

        return false;
    }

    /**
     * is_containerized.
     */
    private static function is_containerized()
    {
        return (defined('RCWP_IS_CONTAINERIZED') && RCWP_IS_CONTAINERIZED);
    }

    /**
     * is_srcache.
     */
    private static function is_srcache()
    {
        if (is_multisite()) {
            $blog_id = self::get_main_site_id();
            switch_to_blog($blog_id);
        }

        $type = get_option(self::$db_type);

        if (is_multisite()) {
            restore_current_blog();
        }

        return ( $type === 'sr' || $type === 'srcache' ? true : false );
    }

    /**
     * switch_cache_type.
     */
    private static function switch_cache_type( $status = false )
    {
        if ( self::is_srcache() ) {
            $updated = self::update_cache_type('fastcgi');
            $debug_message = sprintf( esc_html__('Switching cache purger type from %s to %s', 'runcloud-hub'), 'sr', 'fastcgi' );
        }
        else {
            $updated = self::update_cache_type('sr');
            $debug_message = sprintf( esc_html__('Switching cache purger type from %s to %s', 'runcloud-hub'), 'fastcgi', 'sr' );
        }
        self::debug(__METHOD__, $debug_message);

        if ( $updated ) {
            $req_status = [
                'code' => 200,
                'status' => esc_html__('Switching cache purger type was successful.', 'runcloud-hub'),
            ];
        }
        else {
            $req_status = [
                'code' => 0,
                'status' => esc_html__('Switching cache purger type was failed.', 'runcloud-hub'),
            ];
        }
        return $req_status;
    }

    /**
     * update_cache_type.
     */
    public static function update_cache_type($type, &$message = '') {
        $cache_type = get_option(self::$db_type);
        if ( $cache_type === $type ) {
            $message = sprintf( esc_html__('Updating the cache type is not needed. Cache type: %s', 'runcloud-hub'), $type );
            self::debug(__METHOD__, $message);
            return true;
        }

        $updated = update_option(self::$db_type, $type);
        if ($updated) {
            $message = sprintf( esc_html__('Updating the cache type was successful. Cache type: %s', 'runcloud-hub'), $type );
        }
        else {
            $message = esc_html__('Updating the cache type was failed.', 'runcloud-hub');
        }
        self::debug(__METHOD__, $message);

        return $updated;
    }

    /**
     * is_ajax.
     */
    private static function is_ajax() 
    {
        if ( wp_doing_ajax() ) {
            return true;
        }

        if ( ! empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && 'xmlhttprequest' === strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) ) {
            return true;
        }

        return false;
    }

    /**
     * is_fl_builder.
     */
    private static function is_fl_builder() 
    {
        if ( isset($_POST) && isset($_POST['fl_builder']) ) {
            return true;
        }

        if ( isset($_GET) && isset($_GET['fl_builder']) ) {
            return true;
        }

        return false;
    }

    /**
     * fastcgi_close.
     */
    private static function fastcgi_close()
    {
        if ((php_sapi_name() === 'fpm-fcgi')
            && function_exists('fastcgi_finish_request')) {
            @session_write_close();
            @fastcgi_finish_request();
        }
    }

    /**
     * close_exit.
     */
    private static function close_exit($content = '')
    {
        if (!empty($content)) {
            if (is_array($content)) {
                header('Content-Type: application/json');
                echo json_encode($content, JSON_UNESCAPED_SLASHES);
            }
            else {
                echo esc_html($content);
            }
        }
        self::fastcgi_close();
        exit;
    }

    /**
     * register_locale.
     */
    private static function register_locale()
    {
        add_action(
            'plugins_loaded',
            function () {
                load_plugin_textdomain(
                    'runcloud-hub',
                    false,
                    RUNCLOUD_HUB_PATH_LANG
                );
            },
            0
        );
    }

    public static function wp_cache_delete() {
        wp_cache_delete('uninstall_plugins', 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
        wp_cache_delete(self::get_main_site_id().'-alloptions', 'site-options');
        wp_cache_delete(self::get_main_site_id().'-notoptions', 'site-options');
        wp_cache_delete(self::get_main_site_id().'-active_sitewide_plugins', 'site-options');
    }

    /**
     * register_init.
     */
    public static function register_init()
    {
        self::register_locale();
        self::register_wpcli_hooks();

        self::$hook              = RUNCLOUD_HUB_HOOK;
        self::$plugin_url        = plugin_dir_url(RUNCLOUD_HUB_FILE);
        self::$plugin_url_assets = self::$plugin_url . 'assets/';
        self::$cache_path        = (defined('RUNCLOUD_HUB_PATH_CACHE') ? trailingslashit(RUNCLOUD_HUB_PATH_CACHE) : WP_CONTENT_DIR . '/cache/' . self::$slug . '/');

        self::$ordfirst = -PHP_INT_MAX;
        self::$ordlast  = PHP_INT_MAX;

        $__varfunc_maybe_clear_alloptions_cache = function ($option) {
            if (!wp_installing()) {
                $alloptions = wp_load_alloptions();

                if (isset($alloptions[$option])) {
                    self::wp_cache_delete();
                }
            }
        };

        add_action('added_option', $__varfunc_maybe_clear_alloptions_cache);
        add_action('updated_option', $__varfunc_maybe_clear_alloptions_cache);
        add_action('deleted_option', $__varfunc_maybe_clear_alloptions_cache);

        self::reset_purge_action();

        if (defined('RUNCLOUD_HUB_OPTPAGE') && (bool) RUNCLOUD_HUB_OPTPAGE) {
            self::$optpage = true;
        }

        define('RUNCLOUD_HUB_INIT', true);
    }

    /**
     * register_cron_schedules.
     */
    private static function register_cron_schedules()
    {
        add_action(
            'cron_schedules',
            function ($schedules) {
                $name             = self::$slug . '-minute';
                $schedules[$name] = [
                    'interval' => MINUTE_IN_SECONDS,
                    'display'  => $name,
                ];

                $name             = self::$slug . '-hour';
                $schedules[$name] = [
                    'interval' => HOUR_IN_SECONDS,
                    'display'  => $name,
                ];

                $name             = self::$slug . '-day';
                $schedules[$name] = [
                    'interval' => DAY_IN_SECONDS,
                    'display'  => $name,
                ];

                $name             = self::$slug . '-week';
                $schedules[$name] = [
                    'interval' => WEEK_IN_SECONDS,
                    'display'  => $name,
                ];

                $name             = self::$slug . '-month';
                $schedules[$name] = [
                    'interval' => MONTH_IN_SECONDS,
                    'display'  => $name,
                ];

                $name             = self::$slug . '-year';
                $schedules[$name] = [
                    'interval' => YEAR_IN_SECONDS,
                    'display'  => $name,
                ];
                return $schedules;
            },
            self::$ordlast
        );
    }

    /**
     * default_settings.
     */
    private static function default_settings()
    {
        $options = [
            'purge_permalink_onn'                 => 1,
            'purge_theme_switch_onn'              => 1,
            'purge_general_onn'                   => 1,
            'purge_customize_onn'                 => 0,
            'purge_upgrader_onn'                  => 0,
            'purge_plugin_onn'                    => 0,
            'purge_navmenu_onn'                   => 0,
            'purge_sidebar_onn'                   => 0,
            'purge_widget_onn'                    => 0,
            'homepage_post_onn'                   => 1,
            'homepage_removed_onn'                => 1,
            'content_publish_onn'                 => 1,
            'content_comment_approved_onn'        => 1,
            'archives_content_onn'                => 1,
            'archives_category_onn'               => 1,
            'archives_cpt_onn'                    => 1,
            'archives_author_onn'                 => 0,
            'archives_feed_onn'                   => 0,
            'redis_cache_onn'                     => 0,
            'redis_prefix'                        => '',
            'redis_maxttl_onn'                    => 1,
            'redis_maxttl_int'                    => 1,
            'redis_maxttl_unt'                    => 86400,
            'redis_maxttl_var'                    => 86400,
            'redis_ignored_groups_onn'            => 1,
            'redis_ignored_groups_mch'            => [
                'counts',
                'plugins',
                'themes',
                'comment',
                'wc_session_id',
                'bp_notifications',
                'bp_messages',
                'bp_pages',
            ],
            'redis_debug_onn'                     => 0,
            'rcapi_key'                           => '',
            'rcapi_secret'                        => '',
            'rcapi_webapp_id'                     => '',
            'rcapi_magiclink_onn'                 => 1,
            'stats_onn'                           => 1,
            'stats_schedule_onn'                  => 1,
            'stats_schedule_int'                  => 1,
            'stats_schedule_unt'                  => 86400,
            'stats_schedule_var'                  => 86400,
            'stats_transfer_onn'                  => 1,
            'stats_transfer_var'                  => 'daily',
            'stats_health_onn'                    => 1,
            'stats_health_var'                    => 'hourly',
            'allow_cloudflare_onn'                => 0,
            'exclude_url_onn'                     => 1,
            'exclude_url_mch'                     => [
                '/.well-known.*',
                '/wp-admin/',
                'wp-.*.php',
                'index.php',
                '/wp-json/',
                '/feed/',
                'sitemap.*.xml',
                '/login.*',
                '/cart.*',
                '/checkout.*',
                '/my-account.*',
            ],
            'exclude_cookie_onn'                  => 1,
            'exclude_cookie_mch'                  => [
                'wordpress_sec_[a-f0-9]+',
                'wordpress_logged_in',
                'woocommerce_cart_hash',
                'woocommerce_items_in_cart',
                'wp_woocommerce_session',
            ],
            'exclude_auto_onn'                    => 1,
            'exclude_browser_onn'                 => 0,
            'exclude_browser_mch'                 => [],
            'exclude_visitorip_onn'               => 0,
            'exclude_visitorip_mch'               => [],
            'ignore_query_onn'                    => 1,
            'ignore_query_mch'                    => [
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_expid',
                'fb_action_ids',
                'fb_action_types',
                'fb_source',
                'fbclid',
                'gclid',
                'age-verified',
                'ao_noptimize',
                'usqp',
                'cn-reloaded',
                '_ga',
            ],
            'allow_query_onn'                     => 0,
            'allow_query_mch'                     => [],
            'exclude_query_onn'                   => 0,
            'exclude_query_mch'                   => [],
            'schedule_purge_onn'                  => 0,
            'schedule_purge_int'                  => 12,
            'schedule_purge_unt'                  => 3600,
            'schedule_purge_var'                  => 43200,
            'url_path_onn'                        => 0,
            'url_path_mch'                        => [],
            'html_footprint_onn'                  => 0,
            'cache_key_extra_onn'                 => 0,
            'cache_key_extra_var'                 => '',
            'preload_speed'                       => 12,
            'preload_post_onn'                    => 0,
            'preload_onn'                         => 0,
            'preload_schedule_onn'                => 0,
            'preload_schedule_int'                => 1,
            'preload_schedule_unt'                => 86400,
            'preload_schedule_var'                => 86400,
            'preload_path_onn'                    => 0,
            'preload_path_mch'                    => [],
            'cloudflare_onn'                      => 0,
            'cloudflare_email'                    => '',
            'cloudflare_apikey'                   => '',
            'cloudflare_purgeall_onn'             => 0,
            'cloudflare_purgeurl_onn'             => 0,
            'beta_tester_onn'                     => 0,
        ];

        return $options;
    }

    /**
     * can_manage_network_options.
     */
    private static function can_manage_network_options()
    {
        return (is_multisite() && current_user_can(apply_filters('capability', 'manage_network_options')));
    }

    /**
     * can_manage_network_plugins.
     */
    private static function can_manage_network_plugins()
    {
        return (is_multisite() && current_user_can(apply_filters('capability_network', 'manage_network_plugins')));
    }

    /**
     * can_manage_options.
     */
    private static function can_manage_options()
    {
        return (current_user_can(apply_filters('capability', 'manage_options')));
    }

    /**
     * is_client_mode.
     */
    public static function is_client_mode()
    {
        return ( defined('RUNCLOUD_HUB_CLIENT_MODE') && RUNCLOUD_HUB_CLIENT_MODE ? true : false );
    }

    /**
     * get_main_site_id.
     */
    public static function get_main_site_id()
    {
        return get_main_site_id();
    }

    /**
     * get_main_site_url.
     */
    public static function get_main_site_url()
    {
        return get_site_url(self::get_main_site_id());
    }

    /**
     * is_main_site.
     */
    public static function is_main_site()
    {
        return is_main_site();
    }

    /**
     * is_subdirectory.
     */
    public static function is_subdirectory()
    {
        if ( is_multisite() ) {
            return false;
        }
        $home_url = untrailingslashit(self::home_url());
        $home_part = wp_parse_url($home_url);
        $home_subdirectory = (!empty($home_part['path']) ? true : false);

        $site_url = untrailingslashit(get_site_url());
        $site_part = wp_parse_url($site_url);
        $site_subdirectory = (!empty($site_part['path']) ? true : false);

        return ($home_subdirectory && $site_subdirectory ? true : false);
    }

    /**
     * get_absolute_url.
     */
    private static function get_absolute_url( $url ) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = self::home_url(ltrim($url, '/'));
        }
        return $url;
    }

    /**
     * home_url.
     */
    private static function home_url( $path = '', $scheme = null ) {
        return self::get_home_url( null, $path, $scheme );
    }

    /**
     * get_home_url.
     */
    private static function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
        global $pagenow;

        $orig_scheme = $scheme;

        if ( empty( $blog_id ) || ! is_multisite() ) {
            $url = get_option( 'home' );
        } else {
            switch_to_blog( $blog_id );
            $url = get_option( 'home' );
            restore_current_blog();
        }

        if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
            if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
                $scheme = 'https';
            } else {
                $scheme = parse_url( $url, PHP_URL_SCHEME );
            }
        }

        $url = set_url_scheme( $url, $scheme );

        if ( $path && is_string( $path ) ) {
            $url .= '/' . ltrim( $path, '/' );
        }

        return $url;
    }

    /**
     * remove_protocol.
     */
    private static function remove_protocol($host)
    {
        return preg_replace('@^(https?://|//)@', '', trim($host));
    }

    /**
     * remove_www.
     */
    private static function remove_www($host)
    {
        return preg_replace('/^www\./', '', $host);
    }

    /**
     * is_num.
     */
    public static function is_num($str)
    {
        return preg_match('@^\d+$@', $str);
    }

    /**
     * install_options.
     */
    private static function install_options($all_site = false)
    {
        $__varfunc_install = function ($blog_id) {

            $rcapi_key = self::get_setting('rcapi_key');
            $rcapi_secret = self::get_setting('rcapi_secret');
            $rcapi_webapp_id = self::get_setting('rcapi_webapp_id');

            $options                 = self::default_settings();
            $options['redis_prefix'] = self::unique_key($options['redis_prefix'], $blog_id);
            add_option(self::$db_setting, $options);

            if (!empty($rcapi_key)) {
                self::update_setting_var('rcapi_key', $rcapi_key);
            }
            if (!empty($rcapi_secret)) {
                self::update_setting_var('rcapi_secret', $rcapi_secret);
            }
            if (!empty($rcapi_webapp_id)) {
                self::update_setting_var('rcapi_webapp_id', $rcapi_webapp_id);
            }

            self::register_cron_hooks();
        };

        if (is_multisite() && self::can_manage_network_options() && $all_site) {
            foreach (get_sites(array('number' => 2000)) as $site) {
                switch_to_blog($site->blog_id);

                $__varfunc_install($site->blog_id);

                restore_current_blog();
            }
        } else {
            $__varfunc_install(get_current_blog_id());
        }
    }

    /**
     * uninstall_options.
     */
    private static function uninstall_options($all_site = false)
    {

        self::wp_cache_delete();
        if ( !self::$onfirehooks ) {
            if (is_multisite()) {
                self::late_purge_cache_redis_all();
            }
            else {
                self::late_purge_cache_redis();
            }
        }

        $__varfunc_uninstall = function () {
            delete_option(self::$db_setting);
            self::register_cron_hooks(false);
        };

        if (is_multisite() && self::can_manage_network_options() && $all_site) {
            foreach (get_sites(array('number' => 2000)) as $site) {
                switch_to_blog($site->blog_id);

                $__varfunc_uninstall();

                restore_current_blog();
            }
        } else {
            $__varfunc_uninstall();
        }
    }

    /**
     * stats_is_enabled.
     */
    private static function stats_is_enabled() {
        $options = self::get_setting();

        if (empty($options['stats_onn'])) {
            return false;
        }

        if (empty($options['stats_transfer_onn']) && empty($options['stats_health_onn'])) {
            return false;
        }

        return true;
    }

    /**
     * redis_is_connect.
     */
    public static function redis_is_connect()
    {
        global $wp_object_cache;
        if ( method_exists( $wp_object_cache, 'redis_status' ) ) {
            return $wp_object_cache->redis_status();
        }
        return false;
    }

    /**
     * redis_is_enabled.
     */
    public static function redis_is_enabled()
    {
        if (!is_multisite()) {
            return self::get_setting('redis_cache_onn');
        }
        else {
            if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                    $blog_id = self::get_main_site_id();
                    return self::get_setting('redis_cache_onn', $blog_id);
                }
                else {
                    return self::get_setting('redis_cache_onn');
                }
            }
            else {
                $blog_id = self::get_main_site_id();
                return self::get_setting('redis_cache_onn', $blog_id);
            }
        }

        return false;
    }

    /**
     * redis_can_enable.
     */
    public static function redis_can_enable()
    {
        if (!is_multisite()) {
            return true;
        }
        else {
            if (self::is_main_site()) {
                return true;
            }
            else {
                if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                    if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                        return false;
                    }
                    else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * add_redis_stats.
     */
    public static function add_redis_stats() {
        $options = self::get_setting();
        if (!$options['redis_debug_onn']) {
            return;
        }

        if (!self::can_manage_options()) {
            return;
        }

        if (!self::redis_is_enabled()) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        if (function_exists('wp_is_json_request') && wp_is_json_request()) {
            return;
        }

        // jetpack stats
        if (isset($_GET['noheader'])) {
            return;
        }

        $output = '';
        if (self::redis_is_connect()) {
            global $wp_object_cache;
            if (method_exists($wp_object_cache, 'info')) {
                $info = $wp_object_cache->info();
                $info_hits = isset($info->hits) ? $info->hits : '';
                $info_misses = isset($info->misses) ? $info->misses : '';
                $info_ratio = isset($info->ratio) ? $info->ratio : '';
                $info_bytes = isset($info->bytes) ? size_format( $info->bytes, 2 ) : '';
                $output = sprintf(esc_html__('Redis Object Cache: Hits %s, Misses %s, Hit Ratio %s%s, Size %s', 'runcloud-hub'), $info_hits, $info_misses, $info_ratio, '%', $info_bytes);
            }
            else {
                $output = esc_html__('Redis Object Cache: Could not get statistics info', 'runcloud-hub');
            }
        }
        else {
            $output = esc_html__('Redis Object Cache: Not connected', 'runcloud-hub');
        }

        if (!empty($output)) {
            echo '<style>.rc-redis-debug{background:#23282d;color:#ccc;text-align:center;padding:10px;font-size:14px;line-height:20px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;}.wp-admin .rc-redis-debug{margin-left:160px;}.wp-admin.folded .rc-redis-debug{margin-left:36px;}</style>';
            echo '<div class="rc-redis-debug">';
            echo esc_html($output);
            echo '</div>';
        }
    }

    /**
     * unique_key.
     */
    public static function unique_key($salt = '', $blog_id = '')
    {
        if (empty($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        if (empty($salt)) {
            $salt = self::get_hash(get_site_url() . md5(time() . $blog_id), 14);
        }

        $key = self::get_hash($salt . $blog_id, 14);
        return $key;
    }

    /**
     * get_dropin_source_file.
     */
    private static function get_dropin_source_file()
    {
        return RUNCLOUD_HUB_PATH_LIB . self::$dropin_source;
    }

    /**
     * get_dropin_file.
     */
    private static function get_dropin_file()
    {
        return WP_CONTENT_DIR . '/' . self::$dropin_file;
    }

    /**
     * get_dropin_config_file.
     */
    private static function get_dropin_config_file()
    {
        return WP_CONTENT_DIR . '/' . self::$dropin_config;
    }

    /**
     * is_dropin_exists.
     */
    private static function is_dropin_exists()
    {
        $file = self::get_dropin_file();
        if (is_file($file) && is_readable($file)) {
            clearstatcache(true, $file);
            return true;
        }
        return false;
    }

    /**
     * is_dropin_config_exist.
     */
    private static function is_dropin_config_exist()
    {
        $file = self::get_dropin_config_file();
        if (is_file($file) && is_readable($file)) {
            clearstatcache(true, $file);
            return true;
        }
        return false;
    }

    /**
     * is_dropin_hub.
     */
    private static function is_dropin_hub()
    {
        if ( !self::is_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( self::get_dropin_file() );

        if (strpos($dropin['Description'], 'RunCloud Hub') === false) {
            return false;
        }

        return true;
    }

    /**
     * is_dropin_valid.
     */
    private static function is_dropin_valid()
    {
        if ( !self::is_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( self::get_dropin_file() );
        $source = get_plugin_data( self::get_dropin_source_file() );

        if (strpos($dropin['Description'], 'RunCloud Hub') === false) {
            return false;
        }

        if (strcmp( $dropin['Version'], $source['Version'] ) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * is_dropin_active.
     */
    private static function is_dropin_active()
    {
        if ( defined('RCWP_REDIS_DISABLED') && RCWP_REDIS_DISABLED ) {
            return false;
        }

        if ( !self::is_dropin_exists() ) {
            return false;
        }

        $dropin = get_plugin_data( self::get_dropin_file() );

        if (strpos($dropin[ 'Description' ], 'RunCloud Hub') === false) {
            return false;
        }

        if ( defined('RCWP_REDIS_DROPIN') && RCWP_REDIS_DROPIN ) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * is_dropin_need_install.
     */
    private static function is_dropin_need_install()
    {
        if ( defined('RUNCLOUD_HUB_INSTALL_DROPIN') && !RUNCLOUD_HUB_INSTALL_DROPIN ) {
            return false;
        }

        if ( self::is_dropin_exists() ) {
            return false;
        }

        if (!is_multisite()) {
            return (self::get_setting('redis_cache_onn') ? true : false);
        }
        else {
            if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                    $blog_id = self::get_main_site_id();
                    return (self::get_setting('redis_cache_onn', $blog_id) ? true : false);
                }
                else {
                    return (self::get_setting('redis_cache_onn') ? true : false);
                }
            }
            else {
                $blog_id = self::get_main_site_id();
                return (self::get_setting('redis_cache_onn', $blog_id) ? true : false);
            }
        }

        return true;
    }

    /**
     * is_dropin_need_remove.
     */
    private static function is_dropin_need_remove()
    {
        $is_exist = self::is_dropin_exists() && self::is_dropin_hub() ? true : false;

        $is_needed = true;
        if (!is_multisite()) {
            $is_needed = (self::get_setting('redis_cache_onn') ? true : false);
        }
        else {
            if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                    $blog_id = self::get_main_site_id();
                    $is_needed = (self::get_setting('redis_cache_onn', $blog_id) ? true : false);
                }
            }
            else {
                $blog_id = self::get_main_site_id();
                $is_needed = (self::get_setting('redis_cache_onn', $blog_id) ? true : false);
            }
        }

        if ($is_exist && !$is_needed) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * is_dropin_need_update.
     */
    private static function is_dropin_need_update()
    {
        if ( defined('RCWP_REDIS_DISABLED') && RCWP_REDIS_DISABLED ) {
            return false;
        }

        if ( !self::is_dropin_exists() ) {
            return false;
        }

        // drop-in is enabled by other plugin
        if ( self::is_dropin_exists() && !self::is_dropin_hub() ) {
            return false;
        }

        if ( self::is_dropin_hub() && self::is_dropin_valid() ) {
            return false;
        }

        return true;
    }

    /**
     * is_dropin_need_replace.
     */
    private static function is_dropin_need_replace()
    {
        if ( self::is_dropin_exists() && !self::is_dropin_hub() ) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * is_dropin_need_install.
     */
    private static function is_dropin_config_need_install()
    {
        if ( defined('RUNCLOUD_HUB_INSTALL_DROPIN') && !RUNCLOUD_HUB_INSTALL_DROPIN ) {
            return false;
        }

        if ( ! self::is_dropin_active() ) {
            return false;
        }

        if ( self::is_dropin_config_exist() ) {
            return false;
        }

        return true;
    }

    /**
     * is_dropin_config_need_update.
     */
    private static function is_dropin_config_need_update()
    {
        if ( ! self::is_dropin_active() ) {
            return false;
        }

        $file_dropin_config = self::get_dropin_config_file();
        if ( !file_exists( $file_dropin_config ) ) {
            return true;
        }

        $config = file_exists($file_dropin_config) ? @include($file_dropin_config) : [];

        $main_blog_id = is_multisite() ? self::get_main_site_id() : '';
        $options = self::get_setting('', $main_blog_id);

        if (!isset($config['prefix'], $config['maxttl'])) {
            return true;
        }

        if ($config['prefix'] !== $options['redis_prefix']) {
            return true;
        }
        if ($config['maxttl'] !== (int) $options['redis_maxttl_var']) {
            return true;
        }

        $main_status_ok = false;
        $blog_status_ok = false;

        $main_status = $options['redis_cache_onn'];
        if ($main_status === 1 && isset($config['status']) && $config['status'] === 1) {
            $main_status_ok = true;
        }
        elseif ($main_status !== 1 && isset($config['status']) && $config['status'] !== 1) {
            $main_status_ok = true;
        }

        if (is_multisite() && defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
            if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                $blog_status_ok = true;
            }
            else {
                $blog_id = get_current_blog_id();
                $blog_status = self::get_setting('redis_cache_onn', $blog_id);
                $url = self::home_url();
                $part = wp_parse_url($url);
                $host = $part['host'];
                if ($blog_status === 1 && isset($config['status_sites'][$host]) && $config['status_sites'][$host] === 1) {
                    $blog_status_ok = true;
                }
                elseif ($blog_status !== 1 && isset($config['status_sites'][$host]) && $config['status_sites'][$host] !== 1) {
                    $blog_status_ok = true;
                }
            }
        }
        else {
            $blog_status_ok = true;
        }

        if ($main_status_ok && $blog_status_ok) {
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * try_fix_dropin.
     */
    public static function try_fix_dropin()
    {
        self::debug(__METHOD__, 'event run');
        if ( self::is_ourscreen() ) {
            $req_status = null;
            if (self::is_dropin_need_install() || self::is_dropin_need_update()) {
                $req_status = self::install_dropin(true, true);
            }
            elseif (self::is_dropin_need_remove()) {
                $req_status = self::uninstall_dropin(true, true);
            }
            if (self::is_dropin_config_need_install() || self::is_dropin_config_need_update()) {
                $req_status = self::install_dropin_config(true, true);
            }
            if (!empty($req_status)) {
                update_option(self::$db_notice, $req_status);
            }
        }
    }

    /**
     * try_setup_dropin.
     */
    public static function try_setup_dropin()
    {
        self::debug(__METHOD__, 'event run');
        if (self::is_dropin_need_install() || self::is_dropin_need_update()) {
            self::install_dropin(true);
        }
        elseif (self::is_dropin_need_remove()) {
            self::uninstall_dropin(false);
        }
        if (self::is_dropin_config_need_install() || self::is_dropin_config_need_update()) {
            self::install_dropin_config(true);
        }
    }

    /**
     * update_setting_redis.
     */
    public static function update_setting_redis($value = 1)
    {

        if (is_multisite()) {
            if (self::is_main_site()) {
                self::update_setting_var('redis_cache_onn', $value);
                $redis_action = $value === 1 ? 'enableall' : 'disableall';
            }
            else {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Redis Object Cache can be enabled/disabled only via Network Admin page', 'runcloud-hub'),
                );
            }
        }
        else {
            self::update_setting_var('redis_cache_onn', $value);
            $redis_action = $value === 1 ? 'enable' : 'disable';
        }

        self::wp_cache_delete();

        if ($value === 1) {
            $return = self::install_dropin(true, true);
        }
        else {
            $return = self::uninstall_dropin(true, true);
        }

        if ($return['code'] === 200) {
            update_option(self::$db_notice.'_redis_reload', 1);
            update_option(self::$db_notice.'_redis_action', $redis_action);
        }

        return $return;
    }

    /**
     * update_setting_redis_subsite.
     */
    public static function update_setting_redis_subsite($value = 1)
    {
        if (is_multisite()) {
            if ( defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL ) {
                if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                    return array(
                        'code' => 0,
                        'status' => esc_html__('Redis Object Cache can not be enabled/disabled per site in WordPress Multisite Subdomain because of RCWP_REDIS_NETWORK_ACTIVE constant', 'runcloud-hub'),
                    );
                }
                else {
                    self::update_setting_var('redis_cache_onn', $value);
                }
            }
            else {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Redis Object Cache can not be enabled/disabled per site in WordPress Multisite Subdirectory', 'runcloud-hub'),
                );
            }
        }
        else {
            return array(
                'code' => 0,
                'status' => esc_html__('This feature is available only for WordPress Multisite Subdomain', 'runcloud-hub'),
            );
        }

        self::wp_cache_delete();

        $return = null;

        if (self::is_dropin_need_install() || self::is_dropin_need_update()) {
            $return = self::install_dropin(true, true);
        }
        elseif (self::is_dropin_need_remove()) {
            $return = self::uninstall_dropin(true, true);
        }

        if (self::is_dropin_config_need_install() || self::is_dropin_config_need_update()) {
            $return = self::install_dropin_config(true, true);
        }

        $redis_action = $value === 1 ? 'enable' : 'disable';

        if ($return['code'] === 200) {
            update_option(self::$db_notice.'_redis_reload', 1);
            update_option(self::$db_notice.'_redis_action', $redis_action);
        }

        return $return;
    }

    /**
     * update_setting_redis_all.
     */
    public static function update_setting_redis_all($value = 1)
    {
        if (is_multisite()) {
            if ( defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL ) {
                if ( false !== strpos(wp_get_referer(), '/network/settings.php')) {
                    if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) {
                        return array(
                            'code' => 0,
                            'status' => esc_html__('This feature is not available because of RCWP_REDIS_NETWORK_ACTIVE constant', 'runcloud-hub'),
                        );
                    }
                    else {
                        foreach (get_sites(array('number' => 2000)) as $site) {
                            switch_to_blog($site->blog_id);
                            self::debug(__METHOD__.'update_setting_var:redis_cache_onn='.$redis_cache_onn, ['multisite'=>get_site_url()]);
                            self::update_setting_var('redis_cache_onn', $value);
                            restore_current_blog();
                        }
                    }
                }
                else {
                    return array(
                        'code' => 0,
                        'status' => esc_html__('This feature is available only for Network Admin', 'runcloud-hub'),
                    );
                }
            }
            else {
                return array(
                    'code' => 0,
                    'status' => esc_html__('This feature is available only for WordPress Multisite Subdomain', 'runcloud-hub'),
                );
            }
        }
        else {
            return array(
                'code' => 0,
                'status' => esc_html__('This feature is available only for WordPress Multisite Subdomain', 'runcloud-hub'),
            );
        }

        self::wp_cache_delete();

        $return = null;

        if ($value === 1) {
            $return = self::install_dropin(true, true);
        }
        else {
            $return = self::uninstall_dropin(true, true);
        }

        if (self::is_dropin_config_need_install() || self::is_dropin_config_need_update()) {
            $return = self::install_dropin_config(true, true);
        }

        $redis_action = $value === 1 ? 'enableall' : 'disableall';

        if ($return['code'] === 200) {
            update_option(self::$db_notice.'_redis_reload', 1);
            update_option(self::$db_notice.'_redis_action', $redis_action);
        }

        return $return;
    }

    /**
     * install_dropin.
     */
    public static function install_dropin($force = false, $status = false)
    {
        self::debug(__METHOD__, 'event run');

        if ( defined('RUNCLOUD_HUB_INSTALL_DROPIN') && !RUNCLOUD_HUB_INSTALL_DROPIN ) {
            if ($status) {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Install Drop-in Stopped - RUNCLOUD_HUB_INSTALL_DROPIN', 'runcloud-hub'),
                );
            }
            return false;
        }

        $file_dropin_source = self::get_dropin_source_file();
        $file_dropin = self::get_dropin_file();
        $perm = self::get_fileperms('file');

        if (!file_exists($file_dropin_source)) {
            if ($status) {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Install Drop-in Stopped - Drop-in source file does not exist', 'runcloud-hub'),
                );
            }
            return false;
        }

        $need_install = self::is_dropin_need_install();
        $need_update = self::is_dropin_need_update();

        if ($force || $need_install || $need_update) {
            // self::wp_cache_delete();

            if ($force || $need_update) {
                if (file_exists($file_dropin)) {
                    $remove = @unlink($file_dropin);
                    if (!$remove) {
                        if ($status) {
                            return array(
                                'code' => 0,
                                'status' => esc_html__('Install Drop-in Stopped - Drop-in file was not writable', 'runcloud-hub'),
                            );
                        }
                        return false;
                    }
                }
            }

            if ( !file_exists($file_dropin) ) {
                $buff = file_get_contents($file_dropin_source);
                if ( !empty($buff) && file_put_contents($file_dropin, $buff, LOCK_EX) ) {
                    @chmod($file_dropin, $perm);
                    if (!$force) {
                        if ($status) {
                            return array(
                                'code' => 200,
                                'status' => esc_html__('Installing/updating object cache drop-in was successful', 'runcloud-hub'),
                            );
                        }
                        return true;
                    }
                    else {
                        return self::install_dropin_config($force, $status);
                    }
                }
                else {
                    if ($status) {
                        return array(
                            'code' => 0,
                            'status' => esc_html__('Installing/updating object cache drop-in failed', 'runcloud-hub'),
                        );
                    }
                    return false;
                }
            }
            else {
                if ($status) {
                    return array(
                        'code' => 0,
                        'status' => esc_html__('Install Drop-in Stopped - Drop-in file could not be updated', 'runcloud-hub'),
                    );
                }
                return false;
            }
        }
    }

    /**
     * install_dropin_config.
     */
    public static function install_dropin_config($force = false, $status = false)
    {
        self::debug(__METHOD__, 'event run');

        if ( defined('RUNCLOUD_HUB_INSTALL_DROPIN') && !RUNCLOUD_HUB_INSTALL_DROPIN ) {
            if ($status) {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Update Drop-in Config Stopped - RUNCLOUD_HUB_INSTALL_DROPIN', 'runcloud-hub'),
                );
            }
            return false;
        }

        $file_dropin_config = self::get_dropin_config_file();
        $perm = self::get_fileperms('file');

        $need_install = self::is_dropin_config_need_install();
        $need_update = self::is_dropin_config_need_update();

        if ($force || $need_install || $need_update) {
            self::wp_cache_delete();

            $dropin_config = self::get_dropin_config();
            self::debug(__METHOD__, $dropin_config);

            if ($force || $need_update) {
                if (file_exists($file_dropin_config)) {
                    $remove = @unlink($file_dropin_config);
                    if (!$remove) {
                        if ($status) {
                            return array(
                                'code' => 0,
                                'status' => esc_html__('Update Drop-in Config Stopped - Drop-in config file was not writable', 'runcloud-hub'),
                            );
                        }
                        return false;
                    }
                }
            }

            if ( !file_exists($file_dropin_config) ) {
                $buff = '<?php '.PHP_EOL.'return '.var_export($dropin_config,1).';'.PHP_EOL;
                if ( !empty($buff) && file_put_contents($file_dropin_config, $buff, LOCK_EX) ) {
                    self::wp_cache_delete();
                    if ( !self::$onfirehooks ) {
                        wp_cache_flush();
                    }
                    @chmod($file_dropin_config, $perm);
                    if ($status) {
                        return array(
                            'code' => 200,
                            'status' => esc_html__('Updating object cache drop-in config was successful', 'runcloud-hub'),
                        );
                    }
                    return true;
                }
                else {
                    if ($status) {
                        return array(
                            'code' => 0,
                            'status' => esc_html__('Updating object cache drop-in config failed', 'runcloud-hub'),
                        );
                    }
                    return false;
                }
            }
            else {
                if ($status) {
                    return array(
                        'code' => 0,
                        'status' => esc_html__('Install Drop-in Config Stopped - Drop-in config file could not be updated', 'runcloud-hub'),
                    );
                }
                return false;
            }
        }
    }

    /**
     * get_dropin_config.
     */
    private static function get_dropin_config()
    {
        $config = array(
            'type' => '',
        );

        if (is_multisite()) {
            if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                $config['type'] = 'multisite_subdomain';
            }
            else {
                $config['type'] = 'multisite_subdirectory';
            }
            $url = self::get_main_site_url();
            $part = wp_parse_url($url);
            $config['domain'] = $part['host'];
            $config['path'] = (!empty($part['path']) ? str_replace('/', '__', trim( $part['path'], '/' ) ) : '');
        }
        else {
            $url = untrailingslashit(self::home_url());
            $part = wp_parse_url($url);
            if(empty($part['path'])) {
                $config['type'] = 'single_domain';
            }
            else {
                $config['type'] = 'single_subdirectory';
            }
            $config['domain'] = $part['host'];
            $config['path'] = (!empty($part['path']) ? str_replace('/', '__', trim( $part['path'], '/' ) ) : '');
        }

        if (is_multisite()) {
            $main_site_id = self::get_main_site_id();
            switch_to_blog($main_site_id);
        }

        $options = self::get_setting();
        $config['status'] = $options['redis_cache_onn'];
        $config['prefix'] = $options['redis_prefix'];
        $config['maxttl'] = (int) $options['redis_maxttl_var'];
        if (!empty($options['redis_ignored_groups_onn']) && !empty($options['redis_ignored_groups_mch']) && is_array($options['redis_ignored_groups_mch'])) {
            $ignored_groups = (array) $options['redis_ignored_groups_mch'];
            $config['ignored_groups'] = $ignored_groups;
        }
        else {
            $config['ignored_groups'] = [];
        }

        if (is_multisite()) {
            restore_current_blog();
        }

        if ($config['type'] == 'multisite_subdomain') {
            if (!(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE)) {
                foreach (get_sites(array('number' => 2000)) as $site) {
                    $blog_id = $site->blog_id;
                    $url = self::get_home_url($blog_id);
                    $part = wp_parse_url($url);
                    $host = $part['host'];
                    $config['status_sites'][$host] = self::get_setting('redis_cache_onn', $blog_id);
                }
            }
        }

        return $config;
    }

    /**
     * uninstall_dropin.
     */
    public static function uninstall_dropin($config = true, $status = false)
    {
        self::debug(__METHOD__, 'event run');

        $removed = false;
        $file_dropin = self::get_dropin_file();
        if (file_exists($file_dropin)) {
            self::debug(__METHOD__, 'remove dropin');
            $removed = @unlink($file_dropin);
        }

        if ($config) {
            $file_dropin_config = self::get_dropin_config_file();
            if (file_exists($file_dropin_config)) {
                self::debug(__METHOD__, 'remove dropin config');
                @unlink($file_dropin_config);
            }
        }

        wp_cache_flush();

        if ($status) {
            if ($removed) {
                return array(
                    'code' => 200,
                    'status' => esc_html__('Removing object cache drop-in was successful', 'runcloud-hub'),
                );
            }
            else {
                return array(
                    'code' => 0,
                    'status' => esc_html__('Removing object cache drop-in failed', 'runcloud-hub'),
                );
            }
        }

        return $removed;
    }

    /**
     * is_plugin_active.
     */
    public static function is_plugin_active($plugin)
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($plugin);
    }

    /**
     * is_plugin_active_for_network.
     */
    private static function is_plugin_active_for_network($plugin)
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active_for_network($plugin);
    }

    /**
     * force_site_deactivate_plugin.
     */
    private static function force_site_deactivate_plugin()
    {
        if ((self::can_manage_network_plugins() || self::is_wp_cli())
            && self::is_plugin_active_for_network(self::$hook)) {
            foreach (get_sites(array('number' => 2000)) as $site) {
                switch_to_blog($site->blog_id);
                deactivate_plugins(self::$hook, true, false);
                restore_current_blog();
            }
        }
    }

    /**
     * callback_links.
     */
    public static function callback_links($links)
    {
        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                admin_url('options-general.php?page=' . self::$slug),
                esc_html__('Settings', 'runcloud-hub')
            )
        );
        return $links;
    }

    /**
     * callback_page.
     */
    public static function callback_page()
    {
        $hook_id = add_submenu_page(
            'options-general.php',
            self::$name,
            self::$name,
            apply_filters('capability', 'manage_options'),
            self::$slug,
            [__CLASS__, 'view_index']
        );
    }

    /**
     * callback_network_page.
     */
    public static function callback_network_page()
    {
        $hook_id = add_submenu_page(
            'settings.php',
            self::$name,
            self::$name,
            apply_filters('capability', 'manage_network_options'),
            self::$slug,
            [__CLASS__, 'view_network_index']
        );
    }

    /**
     * is_ourscreen.
     */
    private static function is_ourscreen()
    {
        if(!function_exists('get_current_screen')) {
            return false;
        }
        $screen = get_current_screen();
        $allowed_screens = array(
            'settings_page_'.self::$slug,
            'settings_page_'.self::$slug.'-network',
        );
        if ( !empty($screen->id) && in_array($screen->id, $allowed_screens) ) {
            return true;
        }
        return false;
    }

    /**
     * view_timeduration_select.
     */
    private static function view_timeduration_select($selected = '60', $skip = '')
    {
        $lists = [
            [
                'id'  => 'minute',
                'sec' => MINUTE_IN_SECONDS,
                'txt' => esc_html__('Minutes', 'runcloud-hub'),
            ],
            [
                'id'  => 'hour',
                'sec' => HOUR_IN_SECONDS,
                'txt' => esc_html__('Hours', 'runcloud-hub'),
            ],
            [
                'id'  => 'day',
                'sec' => DAY_IN_SECONDS,
                'txt' => esc_html__('Days', 'runcloud-hub'),
            ],
            [
                'id'  => 'week',
                'sec' => WEEK_IN_SECONDS,
                'txt' => esc_html__('Weeks', 'runcloud-hub'),
            ],
            [
                'id'  => 'month',
                'sec' => MONTH_IN_SECONDS,
                'txt' => esc_html__('Months', 'runcloud-hub'),
            ],
            [
                'id'  => 'year',
                'sec' => YEAR_IN_SECONDS,
                'txt' => esc_html__('Years', 'runcloud-hub'),
            ],
        ];

        foreach ($lists as $arr) {
            $id  = $arr['id'];
            $val = $arr['sec'];
            $txt = $arr['txt'];

            if (!empty($skip)) {
                if (is_array($skip) && in_array($id, $skip)) {
                    continue;
                } elseif ($skip === $id) {
                    continue;
                }
            }

            echo '<option value="' . esc_attr($val) . '"' . ($val == $selected ? ' selected' : '') . '>' . esc_html($txt) . '</option>';
        }
    }

    /**
     * view_timeduration_select.
     */
    private static function view_number_select($selected = 0, $min = 0, $max = 100)
    {
        for ($x = $min; $x <= $max; $x++) {
            echo '<option value="' . esc_attr($x) . '"' . ($x == $selected ? ' selected' : '') . '>' . esc_html($x) . '</option>';
        }
    }

    /**
     * view_stats_select.
     */
    private static function view_stats_select($selected = 'hourly', $skip = '')
    {
        $types = [
            'hourly'  => esc_html__('Hourly', 'runcloud-hub'),
            'daily'   => esc_html__('Daily', 'runcloud-hub'),
            'monthly' => esc_html__('Monthly', 'runcloud-hub'),
        ];

        foreach ($types as $id => $name) {
            if (!empty($skip) && $skip === $id) {
                continue;
            }
            echo '<option value="' . esc_attr($id) . '"' . ($id == $selected ? ' selected' : '') . '>' . esc_html($name) . '</option>';
        }
    }

    /**
     * view_action_link.
     */
    private static function view_action_link($type)
    {
        echo esc_url_raw( self::get_action_link($type) );
    }

    /**
     * view_site_select.
     */
    private static function view_site_select()
    {
        foreach (get_sites(array('number' => 2000)) as $site) {
            $n = $site->blog_id;
            $v = self::remove_protocol(self::get_home_url($n));
            if ((int) $n === (int) self::get_main_site_id()) {
                $v = $v . ' '.esc_html__('(primary)', 'runcloud-hub');
            }
            echo '<option value="' . esc_attr($n) . '"' . ($n == $selected ? ' selected' : '') . '>' . esc_html($v) . '</option>';
        }
    }

    /**
     * view_site_switch.
     */
    private static function view_site_switch()
    {
        if (!is_multisite()) {
            return;
        }

        $network_active = is_plugin_active_for_network( self::$slug.'/'.self::$slug.'.php' );
        $current_blog_id = get_current_blog_id();
        $main_site_id = self::get_main_site_id();

        if (is_network_admin()) {
            echo '<option value="">' . esc_html__('Select Site', 'runcloud-hub') . '</option>';
        }
        if ($network_active && !is_network_admin()) {
            $admin_url = network_admin_url('settings.php?page=' . self::$slug);
            echo '<option value="'.esc_attr($admin_url).'">' . esc_html__('Network Admin Settings', 'runcloud-hub') . '</option>';
        }

        $sites = get_sites(array('number' => 2000));
        foreach ($sites as $site) {
            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);
            if (!$network_active) {
                $active = is_plugin_active( self::$slug.'/'.self::$slug.'.php' );
                if (!$active) {
                    restore_current_blog();
                    continue;
                }
            }
            $label = self::home_url();
            $label = self::remove_protocol($label);
            if ($main_site_id == $blog_id) {
                $label = $label . ' '.esc_html__('(primary)', 'runcloud-hub');
            }
            $admin_url = admin_url('options-general.php?page=' . self::$slug);
            echo '<option value="' . esc_attr($admin_url) . '"' . (!is_network_admin() && $blog_id == $current_blog_id ? ' selected' : '') . '>' . esc_html($label) . '</option>';
            restore_current_blog();
        }
    }

    /**
     * view_fattr.
     */
    private static function view_fattr()
    {
        echo 'autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"';
    }

    /**
     * view_fname.
     */
    private static function view_fname($name)
    {
        echo self::$db_setting . '[' . esc_attr($name) . ']';
    }

    /**
     * view_checked.
     *
     * @since   0.0.0
     */
    private static function view_checked($name)
    {
        echo (isset(self::$checked[$name]) ? esc_attr(self::$checked[$name]) : '');
    }

    /**
     * view_rvalue.
     */
    private static function view_rvalue($name)
    {
        return (isset(self::$value[$name]) ? self::$value[$name] : '');
    }

    /**
     * view_fvalue.
     */
    private static function view_fvalue($name)
    {
        echo self::view_rvalue($name);
    }

    /**
     * view_do_preload.
     */
    private static function view_do_preload()
    {
        $preload = get_option(self::$db_preload);
        if (empty($preload)) {
            return;
        }

        if (!isset($preload['num'], $preload['total'], $preload['type'])) {
            return;
        }

        $num = $preload['num'];
        $total = $preload['total'];
        $type = $preload['type'];

        if (!$total || ($total && $num >= $total)) {
            delete_option(self::$db_preload);
            return;
        }

        $setting = self::get_main_setting();
        $exclude_url_rules = implode('|', $setting['exclude_url_mch']);

        if ($type === 'admin') {
            if ($num == 0) {
                // preload homepage
                $preload_urls = ['home' => self::home_url('/')];
                $preload_urls = apply_filters('runcloud_preload_urls', $preload_urls);

                foreach ($preload_urls as $preload_id => $preload_url) {
                    $desc = $preload_url;
                    if ($preload_id !== 'home') {
                        $desc .= ' ('.$preload_id.')';
                    }
                    if (!empty($exclude_url_rules) && preg_match('('.$exclude_url_rules.')', $preload_url)) {
                        $desc .= ' (skipped/excluded)';
                    } else {
                        self::fetch_preload($preload_url, false);
                    }

                    self::debug(__METHOD__, sprintf( esc_html__('Browser Preload Homepage : %s', 'runcloud-hub'), $desc ) );
                }

                // preload path
                $options = self::get_setting();
                if (!empty($options['preload_path_onn']) && !empty($options['preload_path_mch']) && is_array($options['preload_path_mch'])) {
                    foreach ($options['preload_path_mch'] as $path) {
                        if ('/' === $path) {
                            continue;
                        }
                        $path    = strtolower($path);
                        $preload_url = self::home_url('/') . ltrim($path, '/');
                        self::fetch_preload($preload_url, false);
                    }
                }
            }

            $time = time();
            $link_cancel = self::get_action_link('preload_cancel');
            $link_run_background = self::get_action_link('preload_run_background');
            $inc = self::get_preload_inc();
            $interval = self::get_preload_interval();

            $link_trigger = admin_url('admin-post.php?action=' . self::get_hash(self::$slug . '_preload') . '&_preload=' . $time);
            $link_trigger = add_query_arg('_wpnonce', wp_create_nonce('preload' . $time), $link_trigger);

            echo '<div class="notice notice-info" id="runcloud-preload" ';
            echo 'data-preload-abort="' . esc_url_raw($link_cancel) . '" ';
            echo 'data-preload-trigger="' . esc_url_raw($link_trigger) . '" ';
            echo 'data-preload-num="' . esc_attr($num) . '" ';
            echo 'data-preload-total="' . esc_attr($total) . '" ';
            echo 'data-preload-inc="' . esc_attr($inc) . '" ';
            echo 'data-preload-interval="' . esc_attr($interval) . '">';
            echo '<p id="preload-text"><strong>';
            if ($num) {
                esc_html_e('Post/Page Preloading is running... Please wait...', 'runcloud-hub');
            }
            else {
                esc_html_e('Post/Page Preloading is started... Please wait...', 'runcloud-hub');
            }
            echo '</strong></p>';
            echo '<p id="preload-button">';
            echo '<a href="' . esc_url_raw($link_cancel) . '" class="preload-stop button button-primary">'.esc_html__('Cancel', 'runcloud-hub').'</a>';
            echo '<a href="' . esc_url_raw($link_run_background) . '" class="preload-switch button-light">'.esc_html__('Switch to run in the background', 'runcloud-hub').'</a>';
            echo '</p>';
            echo '</div>';
        }
        else {
            $link_cancel = self::get_action_link('preload_cancel');
            $link_run_admin = self::get_action_link('preload_run_admin');
            echo '<div class="notice notice-info" id="runcloud-preload">';
            echo '<p id="preload-text"><strong>';
            printf( esc_html__('Post/Page Preloading is running in the background (%s/%s). Please refresh to see the progress.', 'runcloud-hub'), $num, $total );
            echo '</strong></p>';
            echo '<p id="preload-button">';
            echo '<a href="' . esc_url_raw($link_cancel) . '" class="preload-stop button button-primary">'.esc_html__('Stop', 'runcloud-hub').'</a>';
            echo '<a href="' . esc_url_raw($link_run_admin) . '" class="preload-switch button-light">'.esc_html__('Switch to run in the browser', 'runcloud-hub').'</a>';
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * callback_view_preload.
     */
    public static function callback_view_preload()
    {
        if (!isset($_GET['_wpnonce'], $_GET['_preload'])) {
            self::debug(__METHOD__, esc_html__('Invalid request', 'runcloud-hub'));
            delete_option(self::$db_preload);
            self::close_exit(esc_html__('Invalid request', 'runcloud-hub'));
        }

        if (!wp_verify_nonce($_GET['_wpnonce'], 'preload' . $_GET['_preload'])) {
            self::debug(__METHOD__, esc_html__('Invalid nonce', 'runcloud-hub'));
            delete_option(self::$db_preload);
            self::close_exit(esc_html__('Invalid nonce', 'runcloud-hub'));
        }

        $preload = get_option(self::$db_preload);
        if (!empty($preload) && is_array($preload)) {
            if (isset($_GET['num']) && self::is_num($_GET['num'])) {
                $num  = (int) $_GET['num'];
                if ($num >= (int) $preload['total']) {
                    delete_option(self::$db_preload);
                    self::close_exit('Done');
                }
                else {
                    $inc = self::get_preload_inc();
                    $urls = self::get_preload_urls($preload['num'], $inc);
                    if (!empty($urls)) {
                        $setting = self::get_main_setting();
                        $exclude_url_rules = implode('|', $setting['exclude_url_mch']);

                        foreach ($urls as $post_id => $url) {
                            $num++;

                            // allow user to filter
                            $preload_urls = [$post_id => $url];
                            $preload_urls = apply_filters('runcloud_preload_urls', $preload_urls);

                            foreach ($preload_urls as $preload_id => $preload_url) {
                                $return = $preload_url;
                                if (!is_int($preload_id)) {
                                    $return .= ' ('.$preload_id.')';
                                }
                                if (!empty($exclude_url_rules) && preg_match('('.$exclude_url_rules.')', $preload_url)) {
                                    $return .= ' (skipped/excluded)';
                                } else {
                                    self::fetch_preload($preload_url, false);
                                }

                                self::debug(__METHOD__, sprintf( esc_html__('Browser Preload %s / %s : %s', 'runcloud-hub'), $num, $preload['total'], $return ) );
                            }
                        }

                        $preload['num'] = $num;
                        update_option(self::$db_preload, $preload, false);
                        self::close_exit($return);
                    }
                    else {
                        self::debug(__METHOD__, sprintf( esc_html__('Browser Preload %s / %s : %s', 'runcloud-hub'), $num, $preload['total'], esc_html__('Preload URL is empty', 'runcloud-hub') ) );
                        self::close_exit();
                    }
                }
            }
        }
        self::debug(__METHOD__, esc_html__('Browser Preload Post/Page: URL is empty', 'runcloud-hub') );
        self::close_exit();
    }

    /**
     * view_do_network.
     */
    private static function view_do_network()
    {
        if (!is_network_admin()) {
            return;
        }

        $network = get_option(self::$db_network);
        if (empty($network)) {
            return;
        }

        if (!isset($network['num'], $network['total'], $network['type'])) {
            return;
        }

        $num = $network['num'];
        $total = $network['total'];
        $type = $network['type'];

        if (!$total || ($total && $num >= $total)) {
            delete_option(self::$db_network);
            return;
        }

        $time = time();
        $link_cancel = self::get_action_link('network_cancel');
        $inc = 1;
        $interval = 2;

        $link_trigger = admin_url('admin-post.php?action=' . self::get_hash(self::$slug . '_network') . '&_network=' . $time);
        $link_trigger = add_query_arg('_wpnonce', wp_create_nonce('network' . $time), $link_trigger);

        echo '<div class="notice notice-info" id="runcloud-network" ';
        echo 'data-network-abort="' . esc_url_raw($link_cancel) . '" ';
        echo 'data-network-trigger="' . esc_url_raw($link_trigger) . '" ';
        echo 'data-network-num="' . esc_attr($num) . '" ';
        echo 'data-network-total="' . esc_attr($total) . '" ';
        echo 'data-network-inc="' . esc_attr($inc) . '" ';
        echo 'data-network-interval="' . esc_attr($interval) . '">';
        echo '<p id="network-text"><strong>';
        if ($num) {
            esc_html_e('Please wait...', 'runcloud-hub');
        }
        else {
            esc_html_e('Please wait...', 'runcloud-hub');
        }
        echo '</strong></p>';
        echo '<p id="network-button">';
        echo '<a href="' . esc_url_raw($link_cancel) . '" class="network-stop button button-primary">'.esc_html__('Cancel', 'runcloud-hub').'</a>';
        echo '</p>';
        echo '</div>';
    }

    /**
     * callback_view_network.
     */
    public static function callback_view_network()
    {
        if (!isset($_GET['_wpnonce'], $_GET['_network'])) {
            self::debug(__METHOD__, esc_html__('Invalid request', 'runcloud-hub'));
            delete_option(self::$db_network);
            self::close_exit(esc_html__('Invalid request', 'runcloud-hub'));
        }

        if (!wp_verify_nonce($_GET['_wpnonce'], 'network' . $_GET['_network'])) {
            self::debug(__METHOD__, esc_html__('Invalid nonce', 'runcloud-hub'));
            delete_option(self::$db_network);
            self::close_exit(esc_html__('Invalid nonce', 'runcloud-hub'));
        }

        $network = get_option(self::$db_network);
        if (!empty($network) && is_array($network)) {
            if (isset($_GET['num']) && self::is_num($_GET['num'])) {
                $num  = (int) $_GET['num'];
                if ($num >= (int) $network['total']) {
                    delete_option(self::$db_network);
                    self::close_exit('Done');
                }
                else {
                    $sites = get_sites(['number' => 1, 'offset' => $num]);
                    if (!empty($sites)) {
                        $num++;
                        $network['num'] = $num;
                        update_option(self::$db_network, $network, false);
                        $message = '';
                        foreach ($sites as $site) {
                            switch_to_blog($site->blog_id);
                            if ($network['type'] == 'purge') {
                                $purge = self::purge_cache_all();
                                $message = self::home_url('/').' - '.$purge['status'];
                            }
                            restore_current_blog();
                        }
                        self::close_exit($message);
                    }
                }
            }
        }
        self::close_exit();
    }

    /**
     * view_page.
     */
    public static function view_page($name, $once = false)
    {
        $__varfunc_view = function ($name, $once = false) {
            $file = RUNCLOUD_HUB_PATH_VIEW . $name . '.php';
            if (is_file($file) && is_readable($file)) {
                clearstatcache(true, $file);

                if ($once) {
                    include_once $file;
                } else {
                    include $file;
                }
            } else {
                echo sprintf( esc_html__( 'Cannot read "%s" file', 'runcloud-hub'), $name ).'<br>';
            }
        };

        if (!empty($name)) {
            if (is_array($name)) {
                foreach ($name as $nm) {
                    $__varfunc_view($nm);
                }
            } else {
                $__varfunc_view($name);
            }
        }
    }

    /**
     * view_index.
     */
    public static function view_index($page = '')
    {
        $options_default = self::default_settings();
        $options         = self::get_setting();
        self::$checked   = [];
        if (!empty($options) && is_array($options)) {
            foreach ($options as $key => $val) {
                if (preg_match('/.*_onn$/', $key)) {
                    $val                 = (int) $val;
                    self::$checked[$key] = (1 === $val ? ' checked' : '');
                    self::$value[$key]   = $val;
                } elseif (preg_match('/.*_mch$/', $key)) {
                    $ckey = str_replace('_mch', '_onn', $key);
                    if (!empty($val) && is_array($val)) {
                        self::$value[$key] = implode("\n", $val);
                    } else {
                        self::$checked[$ckey] = '';
                        self::$value[$key]    = (!empty($options_default[$key]) && is_array($options_default[$key]) ? implode("\n", $options_default[$key]) : '');
                    }
                } elseif (preg_match('/^rcapi.*_id$/', $key)) {
                    self::$value[$key] = (empty($val) ? '' : $val);
                } else {
                    self::$value[$key] = $val;
                }
            }
        }

        $page = (!empty($page) ? esc_attr($page) : 'admin');
        self::view_page($page);
    }

    /**
     * view_network_index.
     */
    public static function view_network_index()
    {
        self::view_index('admin-network');
    }

    /**
     * assets_version.
     */
    private static function assets_version($file)
    {
        $fm = RUNCLOUD_HUB_PATH_RSC . $file;
        if (file_exists($fm)) {
            return self::get_hash(filemtime($fm));
        }

        return self::get_hash(self::$version);
    }

    /**
     * admin_assets.
     */
    public static function admin_assets()
    {
        if ( self::can_manage_options() ) {
            $fm = 'css/' . self::$islug . '-wp.css';
            wp_enqueue_style(
                self::$slug . '-wp',
                self::$plugin_url_assets . $fm,
                [],
                self::assets_version($fm),
                'all'
            );
        }

        if(!self::is_ourscreen()) {
            return;
        }

        $fm = 'css/' . self::$islug . '.css';
        wp_enqueue_style(
            self::$slug,
            self::$plugin_url_assets . $fm,
            [],
            self::assets_version($fm),
            'all'
        );

        $fm = 'js/' . self::$islug . '.js';
        wp_enqueue_script(
            self::$slug,
            self::$plugin_url_assets . $fm,
            ['jquery'],
            self::assets_version($fm),
            true
        );
    }

    /**
     * append_wp_http_referer.
     */
    private static function append_wp_http_referer()
    {
        $referer = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $referer = filter_var(wp_unslash($_SERVER['REQUEST_URI']), FILTER_SANITIZE_URL);
            $referer = '&_wp_http_referer=' . rawurlencode(remove_query_arg('fl_builder', $referer));
        }

        return $referer;
    }

    /**
     * callback_bar_menu.
     */
    public static function callback_bar_menu($wp_admin_bar)
    {
        if (!self::can_manage_options()) {
            return;
        }

        if (is_network_admin() && !self::is_plugin_active_for_network(self::$hook)) {
            return;
        }

        global $pagenow, $post;

        $referer = self::append_wp_http_referer();
        $wp_admin_bar->add_menu(
            [
                'id'    => self::$slug,
                'title' => self::$name,
            ]
        );

        $admin_url = (is_network_admin() ? network_admin_url('settings.php?page=' . self::$slug) : admin_url('options-general.php?page=' . self::$slug));

        if (!self::$optpage) {
            $wp_admin_bar->add_menu(
                [
                    'parent' => self::$slug,
                    'id'     => self::$slug . '-setting',
                    'title'  => esc_html__('Settings', 'runcloud-hub'),
                    'href'   => $admin_url . (self::is_ourscreen() ? '#setting' : ''),
                ]
            );
        }

        if (is_admin()) {
            if ($post && 'post.php' === $pagenow && isset($_GET['action'], $_GET['post'])) {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => self::$slug,
                        'id'     => self::$slug . '-clearcachepost',
                        'title'  => esc_html__('Clear Cache Of This Post', 'runcloud-hub'),
                        'href'   => self::get_action_link('purgepost-' . get_the_ID()),
                    ]
                );
            }
        } else {
            if (is_singular()) {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => self::$slug,
                        'id'     => self::$slug . '-clearcachepost',
                        'title'  => esc_html__('Clear Cache Of This Post', 'runcloud-hub'),
                        'href'   => self::get_action_link('purgepost-' . get_the_ID()),
                    ]
                );
            }
            else {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => self::$slug,
                        'id'     => self::$slug . '-clearcacheurl',
                        'title'  => esc_html__('Clear Cache Of This URL', 'runcloud-hub'),
                        'href'   => self::get_action_link('purgeurl'),
                    ]
                );
            }
        }

        if (!is_network_admin()) {
            $wp_admin_bar->add_menu(
                [
                    'parent' => self::$slug,
                    'id'     => self::$slug . '-clearcacheall',
                    'title'  => esc_html__('Clear All Cache', 'runcloud-hub'),
                    'href'   => self::get_action_link('purgeall'),
                ]
            );

            if (self::redis_is_connect() && self::is_dropin_active()) {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => self::$slug,
                        'id'     => self::$slug . '-clearcacheredis',
                        'title'  => esc_html__('Clear Redis Object Cache', 'runcloud-hub'),
                        'href'   => self::get_action_link('purgeredis'),
                    ]
                );
            }
        }
        else {
            $wp_admin_bar->add_menu(
                [
                    'parent' => self::$slug,
                    'id'     => self::$slug . '-clearcacheallsites',
                    'title'  => esc_html__('Clear All Sites Cache', 'runcloud-hub'),
                    'href'   => self::get_action_link('purgesiteall'),
                ]
            );

            if (self::redis_is_connect() && self::is_dropin_active()) {
                $wp_admin_bar->add_menu(
                    [
                        'parent' => self::$slug,
                        'id'     => self::$slug . '-clearcacheredis',
                        'title'  => esc_html__('Clear All Sites Redis Object Cache', 'runcloud-hub'),
                        'href'   => self::get_action_link('purgeredisall'),
                    ]
                );
            }
        }
    }

    /**
     * after_update_setting.
     */
    public static function after_update_setting($old, $options)
    {
        if (!empty($options)) {

            self::wp_cache_delete();

            // 2 = reset with current changes
            self::schedule_cron_purge(2);
            self::schedule_cron_preload(2);
            self::schedule_cron_rcstats(2);

            $referer = wp_get_raw_referer();

            // update rcapi push
            if (!empty($referer) && preg_match('@#runcache-rules?$@', $referer, $mm)) {
                self::rcapi_push();
            }

            // check cloudflare
            if (!empty($referer) && preg_match('@#cloudflare?$@', $referer, $mm)) {
                self::cloudflare_update_settings();
            }

            // fetch stats if stats is empty
            if (!empty($referer)) {
                self::rcapi_fetch_stats();
            }
        }
    }

    /**
     * callback_register_setting.
     */
    public static function callback_register_setting()
    {
        register_setting(
            self::$slug,
            self::$db_setting,
            [__CLASS__, 'update_setting']
        );
    }

    /**
     * callback_notices.
     */
    public static function callback_notices()
    {
        if (!is_admin()) {
            return;
        }

        if (defined('DOING_AUTOSAVE') || self::is_ajax() ) {
            return;
        }

        if (self::can_manage_options()) {
            add_action('all_admin_notices', [__CLASS__, 'callback_compatibility'], self::$ordlast);
            add_action('all_admin_notices', [__CLASS__, 'callback_use_permalink'], self::$ordlast);
            add_action('all_admin_notices', [__CLASS__, 'callback_action_notice'], self::$ordlast);
            add_action('all_admin_notices', [__CLASS__, 'callback_redis_connect'], self::$ordlast);
        }
    }

    /**
     * callback_compatibility.
     */
    public static function callback_compatibility()
    {
        if (self::maybe_deactivate_runcache_purger()) {
            $msg  = esc_html__('RunCache Purger has been automatically deactivated due to superseded by the RunCloud Hub Plugin', 'runcloud-hub');
            echo '<div id="rc-notice" class="notice notice-info is-dismissible">';
            echo '<p>';
            if (!self::is_ourscreen()) {
                echo '<strong>' . self::$name . ':</strong>&nbsp;' . esc_html($msg);
            } else {
                echo '<strong>' . esc_html($msg) . '</strong>';
            }
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * callback_use_permalink.
     */
    public static function callback_use_permalink()
    {
        if (get_option('permalink_structure')) {
            return;
        }
        if(function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (!empty($screen->id) && $screen->id === 'options-permalink') {
                return;
            }
        }

        $msg = esc_html__('Server-side page caching requires custom permalink structure to work properly.', 'runcloud-hub');
        echo '<div id="rc-redis-notice" class="notice notice-error">';
        echo '<p>';
        if (!self::is_ourscreen()) {
            echo '<strong>' . esc_html(self::$name) . ':</strong>&nbsp;' . esc_html($msg);
        } 
        else {
            echo '<strong>' . esc_html($msg) . '</strong>';
        }
        echo '</p>';
        echo '<p><a class="button" href="'.esc_url(admin_url('options-permalink.php')).'">'.esc_html__('Go To Permalink Settings', 'runcloud-hub').'</a></p>';
        echo '</div>';
    }

    /**
     * callback_redis_connect.
     */
    public static function callback_redis_connect()
    {
        if (!self::redis_is_enabled()) {
            return;
        }
        if (!self::is_dropin_hub()) {
            return;
        }
        if (self::redis_is_connect()) {
            return;
        }
        $msg = esc_html__('Redis Object Cache issue - Redis is not connected!', 'runcloud-hub');
        echo '<div id="rc-redis-notice" class="notice notice-error">';
        echo '<p>';
        if (!self::is_ourscreen()) {
            echo '<strong>' . esc_html(self::$name) . ':</strong>&nbsp;' . esc_html($msg);
        } 
        else {
            echo '<strong>' . esc_html($msg) . '</strong>';
        }
        echo '</p>';
        if (self::redis_can_enable()) {
            echo '<p>'.esc_html__('Please check Redis service in your server or disable Redis object cache.', 'runcloud-hub').'</p>';
        }
        else {
            echo '<p>'.esc_html__('Please contact site administrator to check Redis object cache.', 'runcloud-hub').'</p>';
        }
        echo '</div>';
    }

    /**
     * get_action_link.
     */
    private static function get_action_link($type)
    {
        $tag     = 'action';
        $referer = self::append_wp_http_referer();
        if (!is_admin()) {
            $referer .= '&__notadmin=1';
        }
        else {
            $referer .= '&__admin=1';
        }
        return wp_nonce_url(admin_url('admin-post.php?action=' . self::get_hash(self::$slug . '_' . $tag) . '&type=' . $type . $referer), $tag . '_' . $type);
    }

    /**
     * get_setting.
     */
    public static function get_setting($key = '', $blog_id = '')
    {
        if ($blog_id && is_multisite()) {
            switch_to_blog($blog_id);
        }

        $options = get_option(self::$db_setting, self::default_settings());
        if (!empty($options) && is_array($options)) {
            $options = array_merge(self::default_settings(), $options);
        } else {
            $options = [];
        }

        if (!empty($key)) {
            if (isset($options[$key])) {
                $return = $options[$key];
            }
            else {
                $return = null;
            }
        }
        else {
            $return = $options;
        }

        if ($blog_id && is_multisite()) {
            restore_current_blog();
        }

        return $return;
    }

    /**
     * get_setting.
     */
    public static function get_main_setting($key = '')
    {
        if (!is_multisite()) {
            return self::get_setting($key);
        }
        else {
            $network_id = self::get_main_site_id();
            return self::get_setting($key, $network_id);
        }
    }

    /**
     * dump_setting.
     */
    public static function dump_setting($all = true)
    {
        $__varfunc_options = function () {
            $options = get_option(self::$db_setting, self::default_settings());
            if (!empty($options) && is_array($options)) {
                $options = array_merge(self::default_settings(), $options);
            } else {
                $options = [];
            }
            return $options;
        };

        if (is_multisite()) {
            if ($all) {
                $results = [];
                $num = 0;
                foreach (get_sites(array('number' => 2000)) as $site) {
                    $num++;
                    switch_to_blog($site->blog_id);
                    $results[$num]['blog_id'] = $site->blog_id;
                    $results[$num]['url']     = self::home_url();
                    $results[$num]['options'] = $__varfunc_options();
                    restore_current_blog();
                }
                return $results;
            }
        }

        return $__varfunc_options();
    }

    /**
     * set_settings.
     */
    public static function reset_purge_action()
    {
        self::$is_run_preload         = false;

        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if (!empty($options['preload_onn'])) {
            self::$is_run_preload = true;
        }
    }

    /**
     * setting_cast_int.
     */
    private static function setting_cast_int($input)
    {
        if (!empty($input) && is_array($input)) {
            foreach ($input as $key => $val) {
                if (preg_match('/.*(_onn|_int|_unt)$/', $key)) {
                    $input[$key] = (int) $val;
                }
            }
        }
        return $input;
    }

    /**
     * sanitize_input.
     */
    private static function sanitize_input($input)
    {
        if (!empty($input) && is_array($input)) {
            $input = self::setting_cast_int($input);

            foreach ($input as $key => $val) {
                if (preg_match('/.*_int$/', $key)) {
                    if (('schedule_purge_int' === $key || 'preload_schedule_int' === $key) && empty($val)) {
                        $val = 1;
                    }

                    $key_prefix = str_replace('_int', '', $key);
                    $unt_key    = $key_prefix . '_unt';

                    if (isset($input[$unt_key])) {
                        $unt_key_var         = $key_prefix . '_var';
                        $input[$unt_key_var] = (0 !== $val ? ((int) $val * (int) $input[$unt_key]) : 0);
                    }
                } elseif ( 'rcapi_webapp_id' === $key ) {
                    $key = trim($key);
                    $input[$key] = (self::is_num($val) ? (int) $val : '');
                } elseif ( 'rcapi_key' === $key || 'rcapi_secret' === $key ) {
                    $input[$key] = trim($val);
                } elseif (preg_match('/.*_mch$/', $key) && !is_array($val)) {
                    $list = explode("\n", str_replace(["\n", "\r"], "\n", $val));
                    $list = array_unique($list);
                    foreach ($list as $num => $lst) {
                        if (empty($lst)) {
                            unset($list[$num]);
                        }

                        if ('url_path_mch' === $key || 'exclude_url_mch' === $key || 'preload_path_mch' === $key) {
                            $path = wp_parse_url($lst, PHP_URL_PATH);
                            if (!empty($path)) {
                                $path = strtolower($path);
                                if ('/' !== $path) {
                                    $list[$num] = $path;
                                } else {
                                    unset($list[$num]);
                                }
                            }
                        }
                    }
                    if (!empty($list) && is_array($list)) {
                        $list = array_values($list);
                    }

                    $input[$key] = (is_array($list) && count($list) > 0 ? $list : []);
                } elseif ('redis_prefix' === $key && empty($val)) {
                    $input[$key] = self::unique_key();
                } elseif ('redis_port' === $key && !self::is_num($val)) {
                    unset($input[$key]); // reset default
                } elseif ('cache_key_extra_var' === $key) {
                    $val = preg_replace('@[^A-Za-z0-9_$]@', '', trim($val));
                    if (empty($val)) {
                        $input['cache_key_extra_onn'] = 0;
                    } else {
                        if ( '$' !== substr($val, 0, 1) ) {
                            $val = '$' . $val;
                        }
                    }
                    $input[$key] = $val;
                }
            }
        }

        return $input;
    }

    /**
     * update_setting.
     */
    public static function update_setting($input)
    {
        $options = array_merge(self::default_settings(), self::get_setting());
        if (!empty($input) && is_array($input)) {
            foreach ($options as $key => $val) {
                if (preg_match('/.*_onn$/', $key)) {
                    if (!isset($input[$key])) {
                        $input[$key] = 0;
                    }
                }
            }
        } else {
            $input = self::default_settings();
        }

        $input                = self::sanitize_input($input);
        $options              = array_merge($options, $input);
        $options['timestamp'] = gmdate('Y-m-d H:i:s') . ' UTC';
        $options['update_reason'] = 'update_setting';

        // rcapi key/secret only for main site
        if (!self::is_main_site()) {
            $options['rcapi_key']    = '';
            $options['rcapi_secret'] = '';
            $options['rcapi_webapp_id'] = '';
        }

        if (isset($options['stats_onn'])) {
            $options['stats_schedule_onn'] = $options['stats_onn'];
        }

        if (empty($options['stats_transfer_onn']) && empty($options['stats_health_onn'])) {
            $options['stats_onn'] = 0;
        }

        return $options;
    }

    /**
     * update_setting_var.
     */
    public static function update_setting_var($key, $value)
    {
        $options = self::get_setting();
        if (isset($options[$key])) {
            $options[$key]          = $value;
            $options                = self::sanitize_input($options);
            $options['timestamp']   = gmdate('Y-m-d H:i:s') . ' UTC';
            $options['update_reason'] = 'update_setting_var_'.$key;
            return update_option(self::$db_setting, $options);
        }
        return false;
    }

    /**
     * update_setting_cli.
     */
    public static function update_setting_cli($input)
    {
        $options                = self::get_setting();
        $input                  = self::sanitize_input($input);
        $options                = array_merge($options, $input);
        $options['timestamp']   = gmdate('Y-m-d H:i:s') . ' UTC';
        $options['update_reason'] = 'update_setting_cli';
        return update_option(self::$db_setting, $options);
    }

    /**
     * register_admin_hooks.
     */
    public static function register_admin_hooks()
    {
        if (!self::$optpage) {
            add_filter('plugin_action_links_' . self::$hook, [__CLASS__, 'callback_links'], self::$ordfirst);
        }
        add_action('admin_menu', [__CLASS__, 'callback_page'], self::$ordfirst);

        if (self::is_plugin_active_for_network(self::$hook)) {
            add_action('network_admin_menu', [__CLASS__, 'callback_network_page'], self::$ordfirst);
        }

        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets'], self::$ordlast);

        add_action('admin_bar_menu', [__CLASS__, 'callback_bar_menu'], self::$ordlast);
        add_action('update_option_' . self::$db_setting, [__CLASS__, 'after_update_setting'], self::$ordlast, 2);
        add_action('admin_init', [__CLASS__, 'callback_register_setting'], self::$ordfirst);
        add_action('admin_post_' . self::get_hash(self::$slug . '_action'), [__CLASS__, 'callback_action'], self::$ordlast);
        add_action('admin_post_' . self::$slug . '_deactivate_plugin', [__CLASS__, 'callback_deactivate_plugin']);
        add_action('plugins_loaded', [__CLASS__, 'callback_notices'], self::$ordfirst);

        // autologin
        add_action('init', [__CLASS__, 'callback_magiclink_login'], self::$ordlast);

        // cron
        add_action('cron_purge_' . self::get_hash(self::$slug . 'cron_purge'), [__CLASS__, 'callback_cron_purge'], self::$ordfirst);
        add_action('cron_preload_' . self::get_hash(self::$slug . 'cron_preload'), [__CLASS__, 'callback_cron_preload'], self::$ordfirst);
        add_action('cron_rcstats_' . self::get_hash(self::$slug . 'cron_rcstats'), [__CLASS__, 'callback_cron_rcstats'], self::$ordfirst);

        // preload
        add_action('admin_post_' . self::get_hash(self::$slug . '_preload'), [__CLASS__, 'callback_view_preload'], self::$ordfirst);
        add_action('preload_batch_' . self::get_hash(self::$slug . '_preload_batch'), [__CLASS__, 'callback_preload_batch'], self::$ordfirst);

        // network
        add_action('admin_post_' . self::get_hash(self::$slug . '_network'), [__CLASS__, 'callback_view_network'], self::$ordfirst);
    }

    /**
     * get_user_ip.
     */
    private static function get_user_ip()
    {
        foreach ([
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key]);
                $ip = end($ip);

                if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * get_user_agent.
     */
    private static function get_user_agent()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? esc_attr($_SERVER['HTTP_USER_AGENT']) : '';

        if (empty($user_agent)) {
            $user_agent = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36';
        }

        $user_agent .= ' (compatible; RunCloud-Plugin ' . self::$version . '; +https://runcloud.io)';

        return $user_agent;
    }

    /**
     * purge_request.
     */
    private static function purge_request($url, $options = [])
    {
        if (self::is_defined_halt()) {
            return false;
        }

        $hostname = parse_url($url, PHP_URL_HOST);

        static $done = [];
 
        $purge_host = self::is_containerized() ? 'host' : '127.0.0.1';

        $url = str_replace($hostname, $purge_host, $url);

        $args = [
            'method'      => 'GET',
            'timeout'     => 10,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => self::get_user_agent(),
            'blocking'    => true,
            'headers'     => ['Host' => $hostname],
            'cookies'     => [],
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null,
        ];

        if (!empty($options) && is_array($options)) {
            $args = array_merge($args, $options);
        }

        if (isset($done[$hostname][$url]) && $done[$hostname][$url] === $args['method']) {
            return;
        }

        if ($args['blocking'] === false) {
            $args['timeout'] = 0.01;
        }

        $return = [
            'blocking' => $args['blocking'],
            'code'   => 'X',
            'status' => '',
            'host'   => $args['headers']['Host'],
            'url'    => $url,
            'method' => $args['method'],
        ];

        $response = wp_remote_request($url, $args);

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 403) {
            if (false !== strpos($url, 'http://')) {
                $url = str_replace('http://', 'https://', $url);
                $response = wp_remote_request($url, $args);
                $return['url'] = $url;
                $return['note'] = 'retry code : '.$response_code;
            }
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {
            $return['code']   = $response_code;
            $return['status'] = $response->get_error_message();
            $return['response'] = 'error, ignore';
            if (strpos($return['status'], 'Could not resolve host: host') !== false) {
                $return['status'] .= '. Are you using Containerized Server? If not, please remove RCWP_IS_CONTAINERIZED constant in wp-config.php file';
            } else if (strpos($return['status'], 'Failed to connect to 127.0.0.1') !== false && strpos($return['status'], 'Connection refused') !== false) {
                $return['status'] .= ". Are you using Containerized Server? If yes, please add define( 'RCWP_IS_CONTAINERIZED', true ); constant to wp-config.php file";
            }
            self::define_halt();
        }
        else {
            $response_header = (isset($response['headers']) && is_object($response['headers']) ? (array) $response['headers'] : null);
            $return['header'] = $response_header;
            $return['code'] = $response_code;
            $cache_type_lc = wp_remote_retrieve_header( $response, 'x-runcache-type' );
            $cache_type_uc = wp_remote_retrieve_header( $response, 'X-RunCache-Type' );
            $cache_type = !empty($cache_type_lc) ? $cache_type_lc : $cache_type_uc;
            if (!empty($cache_type)) {
                $return['cache_type'] = $cache_type;
                if (self::is_srcache() && $cache_type === 'native') {
                    $return['code_response'] = $response_code;
                    $return['code'] = 88;
                    $return['status'] = esc_html__('Purge cache failed - Incorrect cache method detected!', 'runcloud-hub');
                }
                elseif (!self::is_srcache() && $cache_type !== 'native') {
                    $return['code_response'] = $response_code;
                    $return['code'] = 88;
                    $return['status'] = esc_html__('Purge cache failed - Incorrect cache method detected!', 'runcloud-hub');
                }
                if (self::is_debug()) {
                    $return['response'] = $response['body'];
                }
            }
            else {
                if ($response_code === 200 || $response_code === 404) {
                    $return['code_response'] = $response_code;
                    $return['code'] = 77;
                    $return['status'] = esc_html__('Purge cache failed - No server-side page caching detected!', 'runcloud-hub');
                }
            }
            $response_body = isset($response['body']) ? $response['body'] : null;
            if (!empty($response_body)) {
                $response_body = trim($response_body);
                $response_body = str_replace(array("\r", "\n"), '', $response_body);
                $response_body = str_replace(array("\t", '  ', '    '), ' ', $response_body);
            }
            $return['response'] = $response_body;
        }

        if ($args['blocking'] === false) {
            $return['status'] = esc_html__('Purge cache was initiated and will run shortly', 'runcloud-hub');
        }

        if (empty($return['status'])) {
            switch ($return['code']) {
                case '200':
                    if (strpos($return['response'], 'Successful purge') !== false || substr($return['response'], 0, 1) === ':' || substr($return['response'], 0, 4) === '+OK:') {
                        // Successful purge
                        $return['status'] = esc_html__('Purge cache was successful', 'runcloud-hub');
                    } else {
                        // Failed, possibly not RunCloud
                        $return['code'] = '200-unknown';
                        $return['status'] = esc_html__('Purge cache failed - Unknown output! Please try to push server cache rules or reinstall server side cache again.', 'runcloud-hub');
                    }
                    break;
                case '412':
                    // Preconditioned failed
                    $return['status'] = esc_html__('Purge cache was skipped (no cache)', 'runcloud-hub');
                    break;
                case '400':
                    // Request Forbidden
                    $return['status'] = sprintf(esc_html__('Purge cache failed - Request Forbidden (%s)', 'runcloud-hub'), $response_code);
                    break;
                case '403':
                    // Request Forbidden
                    $return['status'] = sprintf(esc_html__('Purge cache failed - Request Forbidden (%s)', 'runcloud-hub'), $response_code);
                    break;
                case '404':
                    // Request Not Found
                    $return['status'] = sprintf(esc_html__('Purge cache failed - Request Not Found (%s)', 'runcloud-hub'), $response_code);
                    break;
                case '405':
                    // Method Not Allowed
                    $return['status'] = sprintf(esc_html__('Purge cache failed - Method Not Allowed (%s)', 'runcloud-hub'), $response_code);
                    self::define_halt();
                    break;
                default:
                    $return['status'] = sprintf(esc_html__('Purge cache failed - Error Code %s', 'runcloud-hub'), $response_code);
                    self::define_halt();
            }
        }

        // shorten the output, for debug purpose only
        if (!empty($return['response'])) {
            $return['response'] = substr($return['response'], 0, 500);
        }

        self::debug(__METHOD__, $return);

        $done[$hostname][$url] = $args['method'];

        return $return;
    }

    /**
     * nginx_purge_type.
     */
    public static function nginx_purge_type()
    {
        if (self::is_srcache()) {
            return 'srcache';
        }

        return (self::is_nginx() ? 'fastcgi' : 'proxy');
    }

    /**
     * nginx_purge_all.
     */
    private static function nginx_purge_all($type = null, $proto = null, $run_action = true)
    {
        if (self::$done_purgeall) {
            return [
                'code' => 200,
                'status' => 'done',
            ];
        }

        if (self::is_defined_halt()) {
            return [
                'code' => 0,
                'status' => 'halt',
            ];
        }

        $plugins = [];
        if ( $run_action ) {
            // always clear cache from other cache plugin first before purge all
            $plugins = self::purge_cache_plugins_all();
            // hook before purge all
            do_action('runcloud_purge_nginx_cache_before');
        }

        $type = (!empty($type) ? $type : self::nginx_purge_type());

        if (empty($proto) && 'http' !== $proto && 'https' !== $proto) {
            $proto = (self::is_wp_ssl() ? 'https' : 'http');
        }

        if ( !is_multisite() ) {
            $hostname = parse_url(self::home_url(), PHP_URL_HOST);
            $purge_prefix  = self::$purge_prefix_all;
            $request_query = $proto . '://' . $hostname . '/' . $purge_prefix . '-' . $type;
            $return = self::purge_request($request_query, ['method' => 'PURGE']);
            if (empty($return['code']) || (int)$return['code'] !== 200) {
                if (!self::is_defined_halt()) {
                    $return = self::purge_request($request_query, ['method' => 'GET']);
                }
            }
        }
        else {
            $url = self::home_url('/');
            $hostname = parse_url($url, PHP_URL_HOST);
            $purge_prefix  = self::$purge_prefix_var;
            $request_query = str_replace($hostname, $hostname . '/' . $purge_prefix . '-' . $type . '/', $url) . '*';
            $request_query = str_replace($purge_prefix . '-' . $type . '//', $purge_prefix . '-' . $type . '/', $request_query);
            $return = self::purge_request($request_query, ['method' => 'GET']);
        }

        self::$done_purgeall = true;

        if ( $run_action ) {
            // hook after purge all
            do_action('runcloud_purge_nginx_cache_after');
            // old hook, backward compatibility
            do_action('runcloud_purge_nginx_cache');
        }

        if ( self::redis_is_enabled() || self::redis_is_connect() ) {
            self::purge_cache_redis();
        }

        if (!empty($return['code']) && (int)$return['code'] === 200 && $return['code'] !== '200-unknown') {
            if (!empty($plugins)) {
                $return['status'] = sprintf(esc_html__('Purge all %s and NGINX page cache was successful', 'runcloud-hub'), implode(', ', $plugins));
            }
            else {
                $return['status'] = esc_html__('Purge all NGINX page cache was successful', 'runcloud-hub');
            }
        }

        return $return;
    }

    /**
     * nginx_purge_all_sites.
     */
    private static function nginx_purge_all_sites($type = null, $proto = null, $run_action = true)
    {
        if (self::$done_purgesiteall) {
            return [
                'code' => 200,
                'status' => 'done',
            ];
        }

        if (self::is_defined_halt()) {
            return [
                'code' => 0,
                'status' => 'halt',
            ];
        }

        $type = (!empty($type) ? $type : self::nginx_purge_type());

        if (empty($proto) && 'http' !== $proto && 'https' !== $proto) {
            $proto = (self::is_wp_ssl() ? 'https' : 'http');
        }

        $blog_id = self::get_main_site_id();
        $url = self::home_url('/');
        $hostname = parse_url($url, PHP_URL_HOST);
        $purge_prefix  = self::$purge_prefix_all;
        $request_query = $proto . '://' . $hostname . '/' . $purge_prefix . '-' . $type;
        $return = self::purge_request($request_query, ['method' => 'PURGE']);
        if (empty($return['code']) || (int)$return['code'] !== 200) {
            $return = self::purge_request($request_query, ['method' => 'GET']);
        }

        self::$done_purgesiteall = true;

        if ( self::redis_is_enabled() || self::redis_is_connect() ) {
            self::purge_cache_redis_all();
        }

        if ( $run_action && defined('WP_CLI') && WP_CLI) {
            if (is_multisite()) {
                foreach (get_sites(array('number' => 2000)) as $site) {
                    switch_to_blog($site->blog_id);
                    // remove others cache
                    self::purge_cache_plugins_all();
                    // trigger others plugin
                    do_action('runcloud_purge_nginx_cache');
                    restore_current_blog();
                }
            }
        }

        if (!empty($return['code']) && (int)$return['code'] === 200) {
            $return['status'] = esc_html__('Purge all sites NGINX page cache was successful', 'runcloud-hub');
        }

        return $return;
    }

    /**
     * nginx_purge_cache_url.
     */
    private static function nginx_purge_cache_url($url, $colon = true, $blocking = true)
    {
        if (self::is_defined_halt()) {
            return false;
        }

        if (strpos($url, '?') !== false) {
            $colon = false;
        }

        $hostname = parse_url($url, PHP_URL_HOST);

        $type          = self::nginx_purge_type();
        $purge_prefix  = self::$purge_prefix_var;
        $purge_suffix  = $colon ? ':*' : '*';

        $request_query = str_replace($hostname, $hostname . '/' . $purge_prefix . '-' . $type . '/', $url) . $purge_suffix;
        $request_query = str_replace($purge_prefix . '-' . $type . '//', $purge_prefix . '-' . $type . '/', $request_query);

        return self::purge_request($request_query, ['method' => 'GET', 'blocking' => $blocking]);
    }

    /**
     * callback_action.
     */
    public static function callback_action()
    {
        if (!self::can_manage_options()) {
            return;
        }

        if (!isset($_GET['type'], $_GET['_wpnonce'], $_GET['action']) ) {
            return;
        }

        $wp_referer = wp_get_referer();
        $get_type   = sanitize_text_field($_GET['type']);
        $get_nonce  = sanitize_text_field($_GET['_wpnonce']);
        $get_action = sanitize_text_field($_GET['action']);

        $type = explode('-', $get_type);
        $type = reset($type);
        $id   = explode('-', $get_type);
        $id   = end($id);

        $aname    = self::get_hash(self::$slug . '_action');
        $notadmin = (isset($_GET['__notadmin']) ? true : false);
        $status   = true;

        $req_status = '';

        if (wp_verify_nonce($get_nonce, 'action_' . $get_type)) {
            if ($aname === $get_action) {

                $options = self::get_setting();
                $args = array(
                    'blocking' => true,
                    'blocking_cf' => true,
                    'purge_post' => true,
                    'purge_plugins' => true,
                    'preload' => (bool) $options['preload_post_onn'],
                );
                $args['purge_urlpath'] = !empty($options['url_path_onn']) && !empty($options['url_path_mch']) && is_array($options['url_path_mch']) ? $options['url_path_mch'] : [];

                switch ($type) {
                    case 'purgeall':
                        $req_status = self::purge_cache_all();
                        break;
                    case 'purgesiteall':
                        $req_status = self::purge_cache_all_sites();
                        break;
                    case 'purgepost':
                        $req_status = self::purge_cache_post($id, $args);
                        break;
                    case 'purgeurl':
                        if ('/' === $wp_referer) {
                            $req_status = self::purge_cache_home($args);
                        } else {
                            $up = $wp_referer;
                            if ('/' === $up[0]) {
                                $up = self::home_url($up);
                            }
                            $req_status = self::purge_cache_url($up, $args);
                        }
                        break;
                    case 'purgehomepage':
                        $req_status = self::purge_cache_home($args);
                        break;
                    case 'purgeredis':
                        $req_status = self::purge_cache_redis();
                        break;
                    case 'purgeredisall':
                        $req_status = self::purge_cache_redis_all();
                        break;
                    case 'preload':
                        // clear all cache first but do not trigger background preload
                        self::$is_run_preload = false;
                        self::purge_cache_all_noaction();
                        // setup browser preload
                        $status = false;
                        $total = self::get_total_posts();
                        if ($total) {
                            update_option(
                                self::$db_preload,
                                [
                                    'num'    => 0,
                                    'total'  => $total,
                                    'type'   => 'admin',
                                ],
                                false
                            );
                        }
                        break;
                    case 'preload_cancel':
                        $status = false;
                        delete_option(self::$db_preload);
                        self::schedule_preload_batch(1);
                        self::debug(__METHOD__, esc_html__('Preload is cancelled', 'runcloud-hub'));
                        break;
                    case 'preload_run_admin':
                        $status = false;
                        $preload = get_option(self::$db_preload);
                        $preload['type'] = 'admin';
                        update_option(self::$db_preload, $preload, false);
                        self::schedule_preload_batch(1);
                        self::debug(__METHOD__, esc_html__('Preload switch from background to browser', 'runcloud-hub'));
                        break;
                    case 'preload_run_background':
                        $status = false;
                        $preload = get_option(self::$db_preload);
                        $preload['type'] = 'background';
                        update_option(self::$db_preload, $preload, false);
                        self::schedule_preload_batch();
                        self::debug(__METHOD__, esc_html__('Preload switch from browser to background', 'runcloud-hub'));
                        break;
                    case 'network_cancel':
                        $status = false;
                        delete_option(self::$db_network);
                        self::debug(__METHOD__, esc_html__('Network action is cancelled', 'runcloud-hub'));
                        break;
                    case 'fetchstats':
                        $req_status = self::rcapi_fetch_stats(true, true);
                        break;
                    case 'rcapipush':
                        $req_status = self::rcapi_push(true);
                        break;
                    case 'enableredis':
                        $req_status = self::update_setting_redis(1);
                        break;
                    case 'disableredis':
                        $req_status = self::update_setting_redis(0);
                        break;
                    case 'enableredissite':
                        $req_status = self::update_setting_redis_subsite(1);
                        break;
                    case 'disableredissite':
                        $req_status = self::update_setting_redis_subsite(0);
                        break;
                    case 'enableredisall':
                        $req_status = self::update_setting_redis_all(1);
                        break;
                    case 'disableredisall':
                        $req_status = self::update_setting_redis_all(0);
                        break;
                    case 'installdropin':
                        $req_status = self::reinstall_dropin(true);
                        break;
                    case 'reset':
                        $req_status = self::reinstall_options(false, true);
                        break;
                    case 'switchpurger':
                        $req_status = self::switch_cache_type(true);
                        break;
                    case 'checkcfdev':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::check_cloudflare_dev_mode();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'enablecfdev':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::enable_cloudflare_dev_mode();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'disablecfdev':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::disable_cloudflare_dev_mode();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'checkcfcache':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::check_cloudflare_cache();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'enablecfcache':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::enable_cloudflare_cache();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'disablecfcache':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::disable_cloudflare_cache();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'checkcfapo':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::check_cloudflare_apo();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'enablecfapo':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::enable_cloudflare_apo();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'disablecfapo':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::disable_cloudflare_apo();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    case 'purgecfall':
                        if (self::cloudflare_is_enabled()) {
                            $req_status = self::purge_cloudflare_cache();
                        }
                        else {
                            $req_status = [
                                'code' => 0,
                                'status' => esc_html__('Couldflare integration is disabled', 'runcloud-hub'),
                            ];
                        }
                        break;
                    default:
                        $req_status = [
                            'code' => 0,
                            'status' => esc_html__('Failed, no valid action was specified', 'runcloud-hub'),
                        ];
                }
            }
            else {
                $req_status = [
                    'code' => 0,
                    'status' => esc_html__('Failed, no valid action was specified', 'runcloud-hub'),
                ];
            }
        }
        else {
            $req_status = [
                'code' => 0,
                'status' => esc_html__('Failed, security nonce was invalid', 'runcloud-hub'),
            ];
        }

        self::reset_purge_action();

        if ($status) {
            if (empty($req_status)) {
                $req_status = [
                    'code' => 0,
                    'status' => '',
                ];
            }
            update_option(self::$db_notice, $req_status);
            if ($notadmin) {
                $wp_referer = remove_query_arg('fl_builder', $wp_referer);
                delete_option(self::$db_notice);
                wp_safe_redirect(esc_url_raw($wp_referer));
                self::close_exit();
            }
            else {
                if ($type == 'purgesiteall' && self::is_known_plugins_active()) {
                    wp_safe_redirect(network_admin_url('settings.php?page=' . self::$slug));
                    self::close_exit();
                }
            }
        }

        wp_safe_redirect(esc_url_raw($wp_referer));
        self::close_exit();
    }

    /**
     * callback_action_notice.
     */
    public static function callback_action_notice()
    {
        if (self::is_ourscreen()) {
            $redis_reload = get_option(self::$db_notice.'_redis_reload');
            if ($redis_reload) {
                $redis_action = get_option(self::$db_notice.'_redis_action');
                echo '<div id="rc-notice" class="notice notice-info is-dismissible">';
                echo '<p><strong>'.esc_html__('Updating Redis Object Cache... Please wait and do not close this page...', 'runcloud-hub').'</strong></p>';
                echo '</div>';
                echo '<meta http-equiv="refresh" content="2" />';
                $redis_reload_max = 5;
                if ($redis_reload >= $redis_reload_max) {
                    $deleted = delete_option(self::$db_notice.'_redis_reload');
                    $deleted = delete_option(self::$db_notice.'_redis_action');
                }
                else {
                    if ($redis_action == 'enable' || $redis_action == 'disable') {
                        $purge_redis = self::purge_cache_redis();
                    }
                    elseif ($redis_action == 'enableall' || $redis_action == 'disableall') {
                        $purge_redis = self::purge_cache_redis_all();
                    }

                    if (($redis_action == 'enable' || $redis_action == 'enableall') && $purge_redis['code'] == 200) {
                        $deleted = delete_option(self::$db_notice.'_redis_reload');
                        $deleted = delete_option(self::$db_notice.'_redis_action');
                    }
                    elseif (($redis_action == 'disable' || $redis_action == 'disableall') && $purge_redis['code'] != 200) {
                        $deleted = delete_option(self::$db_notice.'_redis_reload');
                        $deleted = delete_option(self::$db_notice.'_redis_action');
                    }
                    else {
                        update_option(self::$db_notice.'_redis_reload', $redis_reload+1);
                    }
                }
                self::fastcgi_close();
            }
        }

        $req_status = get_option(self::$db_notice);
        if (!empty($req_status) && is_array($req_status)) {
            if (200 === $req_status['code'] || 412 === $req_status['code']) {
                $notice_type = 'success';
                if (!empty($req_status['status'])) {
                    $msg = $req_status['status'];
                }
                else {
                    $msg = esc_html__('Success', 'runcloud-hub');
                }
            }
            else {
                $notice_type = 'error';
                if (!empty($req_status['status'])) {
                    $msg = $req_status['status'];
                }
                else {
                    $msg = esc_html__('Unknown Error. Are you sure you have correct RunCloud Hub API Key?', 'runcloud-hub');
                }
            }

            if (isset($_GET['_rc_purge_ref'])) {
                $msg .= '.&nbsp; ' . esc_html__('Redirecting in 5 seconds...', 'runcloud-hub');
            }

            $allowed_html = array( 'a' => array( 'href' => [], 'title' => [] ), 'br' => [], 'em' => [], 'strong' => [] );

            echo '<div id="rc-notice" class="notice notice-' . esc_attr($notice_type) . ' is-dismissible">';
            echo '<p>';
            if (!self::is_ourscreen()) {
                echo '<strong>' . esc_html(self::$name) . ':</strong>&nbsp;' . wp_kses($msg, $allowed_html);
            } 
            else {
                echo wp_kses($msg, $allowed_html);
            }
            if (isset($_GET['_rc_purge_ref'])) {
                echo '<noscript><meta http-equiv="refresh" content="5;url=' . esc_url(self::home_url('/').$_GET['_rc_purge_ref']) . '"></noscript>';
            }
            echo '</p>';
            if (!empty($req_status['button_action']) && !empty($req_status['button_text'])) {
                echo '<p><a class="button button-primary" href="'.esc_url_raw( self::get_action_link($req_status['button_action']) ).(isset($req_status['button_page']) ? $req_status['button_page'] : '').'">'.esc_html($req_status['button_text']).'</a></p>';
            }
            elseif (!empty($req_status['button_url']) && !empty($req_status['button_text'])) {
                echo '<p><a class="button button-primary" href="'.esc_url($req_status['button_url']).'" '.(isset($req_status['button_target']) ? 'target="'.$req_status['button_target'].'"' : '').'>'.esc_html($req_status['button_text']).'</a></p>';
            }
            if (88 === $req_status['code'] && !empty($req_status['cache_type'])) {
                if ($req_status['cache_type'] === 'native') {
                    $method_db = esc_html__('Redis Page Cache', 'runcloud-hub');
                    $method_detected = esc_html__('FastCGI/Proxy Cache', 'runcloud-hub');
                }
                else {
                    $method_db = esc_html__('FastCGI/Proxy Cache', 'runcloud-hub');
                    $method_detected = esc_html__('Redis Page Cache', 'runcloud-hub');
                }
                echo '<p>';
                printf( esc_html__('Please try again in few minutes if you just changed the method from %1$s to %2$s in your server.', 'runcloud-hub'), $method_db, $method_detected );
                echo '</p>';
                echo '<p>';
                printf( esc_html__('Or please switch the cache purger method to %1$s if you use %1$s in your server.', 'runcloud-hub'), $method_detected );
                echo '</p>';
                echo '<p><a class="button" href="'.esc_url_raw( self::get_action_link('switchpurger') ).'">'.esc_html(sprintf(esc_html__( 'Switch Cache Purger To %s', 'runcloud-hub'), $method_detected)).'</a></p>';
            }
            echo '</div>';
            if (isset($req_status['code2']) && !empty($req_status['status2'])) {
                $notice2_type = 200 === $req_status['code2'] ? 'success' : 'error';
                echo '<div class="notice notice-' . esc_attr($notice2_type) . ' is-dismissible">';
                echo '<p>';
                if (!self::is_ourscreen()) {
                    echo '<strong>' . esc_html(self::$name) . ':</strong>&nbsp;' . wp_kses($req_status['status2'], $allowed_html);
                } 
                else {
                    echo wp_kses($req_status['status2'], $allowed_html);
                }
                echo '</p>';
                echo '</div>';
            }
        }
        delete_option(self::$db_notice);
    }

    /**
     * callback_deactivate_plugin,
     */
    public static function callback_deactivate_plugin()
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'deactivate_plugin')) {
            wp_nonce_ays('');
        }

        deactivate_plugins($_GET['plugin']);
        self::wp_cache_delete();

        wp_safe_redirect(wp_get_referer());
        self::close_exit();
    }

    /**
     * get_post_terms_urls.
     */
    private static function get_post_terms_urls($post_id)
    {
        $urls       = [];

        if (empty($post_id)) {
            return $urls;
        }

        $post_type = get_post_type($post_id);

        if (empty($post_type)) {
            return $urls;
        }

        if ($post_type === 'page') {
            return $urls;
        }

        $taxonomies = get_object_taxonomies($post_type, 'objects');

        if (empty($taxonomies)) {
            return $urls;
        }

        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }

            if (class_exists('WooCommerce')) {
                if ('product_shipping_class' === $taxonomy->name) {
                    continue;
                }
            }

            $terms = get_the_terms($post_id, $taxonomy->name);
            if (!empty($terms)) {
                foreach ($terms as $term) {
                    $term_url = get_term_link($term->slug, $taxonomy->name);
                    if (!empty($term_url)) {
                        $urls[] = $term_url;
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * purge_cache_redis.
     */
    public static function purge_cache_redis()
    {
        static $done_purgeredis = false;
        $ok                 = false;
        $is_dropin          = self::is_dropin_exists();
        $is_connect         = self::redis_is_connect();
        $is_enabled         = self::redis_is_enabled();

        if ($is_connect) {
            $ok = wp_cache_flush();

            if (self::is_wp_cli()) {
                self::late_purge_cache_redis();
            }

            if ( $ok ) {
                $status = esc_html__('Purge Redis object cache was successful', 'runcloud-hub');
            }
            else {
                $status = esc_html__('Purge Redis object cache failed - Request not found', 'runcloud-hub');
            }
        }
        else {
            if ( $is_enabled ) {
                $status = esc_html__('Purge Redis object cache failed - Redis is not connected', 'runcloud-hub');
            }
            else {
                $status = esc_html__('Purge Redis object cache failed - Redis is not enabled', 'runcloud-hub');
            }
        }

        $return = [
            'code'       => ($ok ? 200 : 404),
            'status'     => $status,
            'is_redis'   => 1,
            'is_dropin'  => ($is_dropin ? 1 : 0),
            'is_connect' => ($is_connect ? 1 : 0),
            'is_enabled' => ($is_enabled ? 1 : 0),
        ];

        self::debug(__METHOD__, $return);

        if (!$done_purgeredis) {
            // trigger others plugin
            do_action('runcloud_purge_redis_cache');
            $done_purgeredis = true;
        }

        return $return;
    }

    /**
     * purge_cache_redis_all.
     */
    public static function purge_cache_redis_all()
    {
        static $done_purgeredisall = false;
        $ok                 = false;
        $is_dropin          = self::is_dropin_exists();
        $is_connect         = self::redis_is_connect();
        $is_enabled         = self::redis_is_enabled();

        if (self::is_wp_cli() || self::can_manage_network_options()) {
            if (function_exists('wp_cache_flush_sites')) {
                if ($is_connect) {
                    $ok = wp_cache_flush_sites();

                    if (self::is_wp_cli()) {
                        self::late_purge_cache_redis_all();
                    }

                    if ( $ok ) {
                        $status = esc_html__('Purge all sites Redis object cache was successful', 'runcloud-hub');
                    }
                    else {
                        $status = esc_html__('Purge all sites Redis object cache failed - Request not found', 'runcloud-hub');
                    }
                }
                else {
                    if ( $is_enabled ) {
                        $status = esc_html__('Purge all sites Redis object cache failed - Redis is not connected', 'runcloud-hub');
                    }
                    else {
                        $status = esc_html__('Purge all sites Redis object cache failed - Redis is not enabled', 'runcloud-hub');
                    }
                }
            }
            else {
                $status = esc_html__('Purge all sites Redis object cache failed - Redis object cache is disabled on main site', 'runcloud-hub');
            }
        }
        else {
            $status = esc_html__('Purge all sites Redis object cache failed - For Super Administrator only', 'runcloud-hub');
        }

        $return = [
            'code'       => ($ok ? 200 : 404),
            'status'     => $status,
            'is_redis'   => 1,
            'is_dropin'  => ($is_dropin ? 1 : 0),
            'is_connect' => ($is_connect ? 1 : 0),
            'is_enabled' => ($is_enabled ? 1 : 0),
        ];

        self::debug(__METHOD__, $return);

        if (!$done_purgeredisall) {
            // trigger others plugin
            do_action('runcloud_purge_redis_cache_all');
            $done_purgeredisall = true;
        }

        return $return;
    }

    /**
     * purge_cache_url.
     */
    public static function purge_cache_url($url, $args = [])
    {
        if (self::is_defined_halt()) {
            return;
        }

        if (empty($url)) {
            return [
                'code' => 0,
                'status' => 'Unknown',
            ];
        }

        $defaults = [
            'blocking' => false,
            'blocking_cf' => false,
            'preload' => false,
            'colon' => true,
        ];

        $args = wp_parse_args( $args, $defaults );

        if ($args['preload']) {
            $args['blocking'] = true;
        }

        $url = self::get_absolute_url($url);

        $return = self::nginx_purge_cache_url($url, $args['colon'], $args['blocking']);

        if ($args['preload']) {
            self::fetch_preload($url, false);
        }

        if (self::cloudflare_is_enabled('purgeurl')) {
            $returncf = self::purge_cloudflare_cache([$url], $args);
            if (isset($returncf['code']) && !empty($returncf['status'])) {
                $return['code2'] = $returncf['code'];
                $return['status2'] = $returncf['status'];
            }
        }

        return $return;
    }

    /**
     * purge_cache_home.
     */
    public static function purge_cache_home($args = [])
    {
        if (self::is_defined_halt()) {
            return;
        }

        // backward compatibility
        if (!is_array($args)) {
            $args = [
                'blocking' => (is_bool($args) ? $args : false),
            ];
        }

        $defaults = [
            'blocking' => false,
            'blocking_cf' => false,
            'preload' => false,
        ];

        $args = wp_parse_args( $args, $defaults );

        if ($args['preload']) {
            $args['blocking'] = true;
        }

        $home_url = self::home_url('/');

        $return = self::nginx_purge_cache_url($home_url, true, $args['blocking']);

        $show_on_front = get_option('show_on_front');
        if ( $show_on_front !== 'page' ) {
            if ( get_option('permalink_structure') ) {
                self::nginx_purge_cache_url($home_url.'page/', false, false);
            }
        }

        if ($args['preload']) {
            self::fetch_preload($home_url, false);
        }

        if (self::cloudflare_is_enabled('purgeurl')) {
            $returncf = self::purge_cloudflare_cache([$home_url], $args);
            if (!empty($returncf['status'])) {
                $return['code2'] = $returncf['code'];
                $return['status2'] = $returncf['status'];
            }
        }

        // run hook after purge home
        do_action('runcloud_purge_nginx_cache_home');

        return $return;
    }

    /**
     * purge_cache_all.
     */
    public static function purge_cache_all()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        self::debug(__METHOD__, esc_attr($_SERVER['REQUEST_URI']));

        $return = self::nginx_purge_all();

        if (self::cloudflare_is_enabled('purgeall')) {
            $returncf = self::purge_cloudflare_cache();
            self::debug(__METHOD__, $returncf);
            if (isset($returncf['code']) && !empty($returncf['status'])) {
                $return['code2'] = $returncf['code'];
                $return['status2'] = $returncf['status'];
            }
        }

        if (self::$is_run_preload) {
            $total = self::get_total_posts();
            if ($total) {
                update_option(
                    self::$db_preload,
                    [
                        'num'    => 0,
                        'total'  => $total,
                        'type'   => 'background',
                    ],
                    false
                );
                self::schedule_preload_batch();
            }
        }

        return $return;
    }

    /**
     * purge_cache_all_noaction.
     */
    public static function purge_cache_all_noaction()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        self::debug(__METHOD__, esc_attr($_SERVER['REQUEST_URI']));

        // run nginx_purge_all but with run_action false
        // it is dedicated for purger hooks with better plugin integration
        $return = self::nginx_purge_all(null, null, false);

        if (self::cloudflare_is_enabled('purgeall')) {
            $returncf = self::purge_cloudflare_cache();
            if (isset($returncf['code']) && !empty($returncf['status'])) {
                $return['code2'] = $returncf['code'];
                $return['status2'] = $returncf['status'];
            }
        }

        if (self::$is_run_preload) {
            $total = self::get_total_posts();
            if ($total) {
                update_option(
                    self::$db_preload,
                    [
                        'status' => 'queue',
                        'num'    => 0,
                        'total'  => $total,
                        'type'   => 'background',
                    ],
                    false
                );
                self::schedule_preload_batch();
            }
        }

        return $return;
    }

    /**
     * purge_cache_all_sites.
     */
    public static function purge_cache_all_sites()
    {
        if (self::$done_purgesiteall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        $return = self::nginx_purge_all_sites();

        if (isset($_GET['__admin']) && self::is_known_plugins_active()) {
            $counts = wp_count_sites();
            $total = isset($counts['all']) ? (int) $counts['all'] : 0;
            if ($total > 0) {
                update_option(
                    self::$db_network,
                    [
                        'num'    => 0,
                        'total'  => $total,
                        'type'   => 'purge',
                    ],
                    false
                );
            }
        }

        return $return;
    }

    /**
     * purge_cache_on_upgrader.
     */
    public static function purge_cache_on_upgrader($wp_upgrader, $options)
    {
        self::try_fix_dropin();

        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_upgrader_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        if (is_multisite()) {
            self::purge_cache_all_sites();
        }
        else {
            self::purge_cache_all();
        }
    }

    /**
     * purge_cache_on_plugin_activate.
     */
    public static function purge_cache_on_plugin_activate($plugin, $network_wide)
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_plugin_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        if ($network_wide) {
            self::purge_cache_all_sites();
        }
        else {
            self::purge_cache_all();
        }
    }

    /**
     * purge_cache_on_plugin_deactivate.
     */
    public static function purge_cache_on_plugin_deactivate($plugin, $network_deactivating)
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_plugin_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        if ($network_deactivating) {
            self::purge_cache_all_sites();
        }
        else {
            self::purge_cache_all();
        }
    }

    /**
     * purge_cache_on_theme_switch.
     */
    public static function purge_cache_on_theme_switch()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_theme_switch_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_permalink_update.
     */
    public static function purge_cache_on_permalink_update()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_permalink_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_general_update.
     */
    public static function purge_cache_on_general_update()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_general_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_customize_save.
     */
    public static function purge_cache_on_customize_save()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_customize_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_navmenu_update.
     */
    public static function purge_cache_on_navmenu_update()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_navmenu_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_sidebar_update.
     */
    public static function purge_cache_on_sidebar_update()
    {
        if (self::$done_purgeall) {
            return;
        }

        if (self::is_defined_halt()) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        if ( ! (bool) $options['purge_sidebar_onn'] ) {
            return;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();
    }

    /**
     * purge_cache_on_widget_update.
     */
    public static function purge_cache_on_widget_update( $obj )
    {
        if (self::$done_purgeall) {
            return $obj;
        }

        if (self::is_defined_halt()) {
            return $obj;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return $obj;
        }

        if ( ! (bool) $options['purge_widget_onn'] ) {
            return $obj;
        }

        self::debug(__METHOD__, 'event run');

        self::nginx_purge_all();

        return $obj;
    }

    /**
     * purge_cache_on_post_change.
     */
    public static function purge_cache_on_post_change($post_id) {

        if (empty($post_id)) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        $args = [];

        // purge post/page/CPT on published/removed
        if ( (bool) $options['content_publish_onn']) {
            $args['purge_post'] = true;
        } else {
            $args['purge_post'] = false;
        }

        $args['blocking'] = false;

        self::debug(__METHOD__, ['post_id' => $post_id]);

        self::purge_cache_post($post_id, $args);
    }

    /**
     * purge_cache_on_post_update.
     */
    public static function purge_cache_on_post_update($post_id, $post, $post_before) {
        if (empty($post_id)) {
            return;
        }

        if (empty($post->post_status)) {
            return;
        }

        if (in_array($post->post_status, ['auto-draft'])) {
            return;
        }

        if (empty($post->post_type)) {
            return;
        }

        if (function_exists('is_post_type_viewable') && !is_post_type_viewable($post->post_type)) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        $old_status = !empty($post_before->post_status) ? $post_before->post_status : '';
        $new_status = $post->post_status;

        if ($old_status == 'publish') {
            self::$updated_posts[$post_id] = $post_before;
        }

        $args = [];

        if ($new_status == 'publish' || $old_status == 'publish') {
            // purge post/page/CPT on published/removed
            if ( (bool) $options['content_publish_onn']) {
                $args['purge_post'] = true;
            } else {
                $args['purge_post'] = false;
            }

            $args['purge_home'] = false;

            // purge homepage on post updated/added
            if ($new_status == 'publish' && (bool) $options['homepage_post_onn']) {
                $args['purge_home'] = true;
            }

            // purge homepage on post removed
            if ($new_status !== 'publish' && (bool) $options['homepage_removed_onn']) {
                $args['purge_home'] = true;
            }

            // others
            $args['blocking'] = false;
            $args['purge_archives'] = (bool) $options['archives_content_onn'];
            $args['purge_cpt_archive'] = (bool) $options['archives_cpt_onn'];
            $args['purge_category'] = (bool) $options['archives_category_onn'];
            $args['purge_author'] = (bool) $options['archives_author_onn'];
            $args['purge_feed'] = (bool) $options['archives_feed_onn'];
            $args['preload'] = (bool) $options['preload_post_onn'];
            $args['purge_urlpath'] = !empty($options['url_path_onn']) && !empty($options['url_path_mch']) && is_array($options['url_path_mch']) ? $options['url_path_mch'] : [];
        }

        self::debug(__METHOD__, ['oldstatus' => $old_status, 'newstatus' => $new_status, 'post_id' => $post_id, 'post' => $post]);

        self::purge_cache_post($post_id, $args);
    }

    /**
     * purge_cache_on_post_after_insert.
     */
    public static function purge_cache_on_post_after_insert($post_id, $post, $update, $post_before) {
        self::purge_cache_on_post_update($post_id, $post, $post_before);
    }

    /**
     * purge_cache_on_comment.
     */
    public static function purge_cache_on_comment($comment_id, $comment) {
        $oldstatus = '';
        $newstatus = $comment->comment_approved === '1' ? 'approved' : 'unapproved';

        self::purge_cache_on_comment_status_change($newstatus, $oldstatus, $comment);
    }

    /**
     * purge_cache_on_comment_status_change.
     */
    public static function purge_cache_on_comment_status_change($newstatus, $oldstatus, $comment) {
        $post_id = $comment->comment_post_ID;

        if (empty($post_id)) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        $args = [];

        // purge post/page/CPT when a comment is approved/unapproved/trashed
        if ((bool) $options['content_comment_approved_onn']) {
            $args['purge_post'] = true;
        } else {
            $args['purge_post'] = false;
        }

        // purge archive when a purged content is triggered
        if ($args['purge_post']) {
            $args['purge_archives'] = (bool) $options['archives_content_onn'];
            $args['purge_cpt_archive'] = (bool) $options['archives_cpt_onn'];
            $args['purge_category'] = (bool) $options['archives_category_onn'];
            $args['purge_author'] = (bool) $options['archives_author_onn'];
            $args['purge_feed'] = (bool) $options['archives_feed_onn'];
            $args['preload'] = (bool) $options['preload_post_onn'];
            $args['purge_urlpath'] = !empty($options['url_path_onn']) && !empty($options['url_path_mch']) && is_array($options['url_path_mch']) ? $options['url_path_mch'] : [];
        }

        self::debug(__METHOD__, ['oldstatus' => $oldstatus, 'newstatus' => $newstatus, 'post_id' => $post_id, 'comment' => $comment]);

        self::purge_cache_post($post_id, $args);
    }

    /**
     * purge_cache_post.
     */
    public static function purge_cache_post($post_id, $args = [])
    {
        if (defined('DOING_AUTOSAVE') || self::is_defined_halt()) {
            return;
        }

        if (empty($post_id)) {
            return;
        }

        $post = get_post($post_id);
        if (empty($post)) {
            return;
        }

        if (empty($post->post_status)) {
            return;
        }

        if (in_array($post->post_status, ['auto-draft'])) {
            return;
        }

        if (empty($post->post_type)) {
            return;
        }

        if (function_exists('is_post_type_viewable') && !is_post_type_viewable($post->post_type)) {
            return;
        }

        self::debug(__METHOD__, ['post_id' => $post_id]);

        $defaults = [
            'purge_post' => true,
            'purge_home' => false,
            'purge_cpt_archive' => false,
            'purge_category' => false,
            'purge_author' => false,
            'purge_feed' => false,
            'purge_urlpath' => [],
            'purge_plugins' => false,
            'blocking' => false,
            'blocking_cf' => false,
            'preload' => false,
        ];

        $args = wp_parse_args( $args, $defaults );

        if ($args['purge_cpt_archive'] || $args['purge_category'] || $args['purge_author'] || $args['purge_feed']) {
            $args['purge_archives'] = true;
        } else {
            $args['purge_archives'] = true;
        }

        if ($args['preload']) {
            $args['blocking'] = true;
        }

        $return = [
            'code' => 0,
            'status' => 'Unknown',
        ];

        $post_before = !empty(self::$updated_posts[$post_id]) ? self::$updated_posts[$post_id] : null;

        $home_url = self::home_url('/');
        $purge_permalink = '';
        $purge_urls = [];
        $post_is_home = false;

        // Check if page is homepage
        if ($args['purge_post'] && 'page' === $post->post_type) {
            $show_on_front = get_option('show_on_front');
            if ( $show_on_front === 'page' ) {
                $page_on_front_id = (int) get_option('page_on_front');
                if ($post_id == $page_on_front_id) {
                    $post_is_home = true;
                    $purge_permalink = $home_url;
                    $purge_urls[] = $purge_permalink;
                }
            }
        }

        // Purge Post/Page/CPT
        if ($args['purge_post'] && !$post_is_home) {
            $purge_permalink = get_permalink($post_id);
            if (!empty($purge_permalink)) {
                $purge_permalink = str_replace('__trashed', '', $purge_permalink);
            }

            $old_permalink = '';
            if (!empty($post_before)) {
                $old_permalink = get_permalink($post_before);
                if (!empty($old_permalink)) {
                    $old_permalink = str_replace('__trashed', '', $old_permalink);
                }
            }

            if ($post->post_status !== 'publish' && !empty($old_permalink)) {
                $purge_permalink = $old_permalink;
            }

            if (!empty($purge_permalink)) {
                $purge_urls[] = $purge_permalink;

                if (!empty($old_permalink) && $old_permalink !== $purge_permalink) {
                    $purge_urls[] = $old_permalink;
                }
            }
        }

        // Purge Homepage
        if ($args['purge_home'] && !$post_is_home) {
            $purge_urls[] = $home_url;
        }

        // Purge Archive
        if ($args['purge_archives']) {

            // purge post type archive
            if ($args['purge_cpt_archive'] && !$post_is_home) {
                $post_type = $post->post_type;

                // purge blog posts page, if available
                if ($post_type === 'post') {
                    $show_on_front = get_option('show_on_front');
                    if ( $show_on_front === 'page' ) {
                        $page_for_posts_id = (int) get_option('page_for_posts');
                        if ('post' === $post->post_type && $page_for_posts_id > 0) {
                            $purge_urls[] = get_permalink($page_for_posts_id);
                        }
                    }
                }

                // purge custom post type archive page, if available
                if ($post_type != 'post' && $post_type != 'page') {
                    if (function_exists('get_post_type_archive_link')) {
                        $post_type_archive_link = get_post_type_archive_link($post->post_type);
                        if ($post_type_archive_link) {
                            $purge_urls[] = $post_type_archive_link;
                        }
                    }
                }
            }

            // purge category / tag / custom taxonomy
            if ($args['purge_category'] && !$post_is_home) {
                $purge_terms = self::get_post_terms_urls($post_id);
                if (!empty($purge_terms) && is_array($purge_terms)) {
                    $purge_urls = array_merge($purge_urls, $purge_terms);
                }
            }

            // purge author page
            if ($args['purge_author'] && !$post_is_home) {
                $author_id = $post->post_author;
                if (!empty($author_id) && function_exists('get_author_posts_url')) {
                    $author_link = get_author_posts_url($author_id);
                    if ($author_link) {
                        $purge_urls[] = $author_link;

                        if (!empty($post_before)) {
                            $old_author_id = $post_before->post_author;
                            if (!empty($author_id) && function_exists('get_author_posts_url')) {
                                $old_author_link = get_author_posts_url($old_author_id);
                                if ($old_author_link && $old_author_link !== $author_link) {
                                    $purge_urls[] = $old_author_link;
                                }
                            }
                        }
                    }
                }
            }

            // purge RSS feed page
            if ($args['purge_feed']) {
                if ( current_theme_supports( 'automatic-feed-links' ) && function_exists('get_feed_link') ) {
                    $purge_urls[] = get_feed_link();
                    $purge_urls[] = get_feed_link('comments_');
                }
            }
        }

        // Purge URL Path
        if (!empty($args['purge_urlpath']) && is_array($args['purge_urlpath'])) {
            foreach ($args['purge_urlpath'] as $path) {
                if ('/' === $path) {
                    continue;
                }
                if (!empty($path)) {
                    $purge_urls[] = $path;
                }
            }
        }

        if (empty($purge_urls)) {
            return array(
                'code' => 0,
                'status' => esc_html__('Purge post action failed because post permalink is empty. Please contact support', 'runcloud-hub'),
            );
        }

        // convert relative URLs to absolute URLs
        if (!empty($purge_permalink)) {
            $purge_permalink = self::get_absolute_url($purge_permalink);
        }
        foreach ($purge_urls as $key => $url) {
            $purge_urls[$key] = self::get_absolute_url($url);
        }

        // allow user to filter
        $purge_urls = apply_filters('runcloud_purge_urls', $purge_urls, $post_id);

        // remove duplicate URLs
        $purge_urls = array_unique($purge_urls);

        self::debug(__METHOD__, ['purge_urls' => $purge_urls]);

        // run purge Nginx cache for the post link
        if (!empty($purge_permalink)) {
            if ($post_is_home) {
                $return = self::purge_cache_home($args);
            } else {
                $return = self::nginx_purge_cache_url($purge_permalink, true, (bool)$args['blocking']);
            }
        }

        $plugins = [];
        if ( (bool) $args['purge_plugins'] ) {
            $plugins = self::purge_cache_plugins_post($post_id);
        }

        // run purge Nginx cache for other links
        foreach ($purge_urls as $url) {
            if (!empty($purge_permalink) && $url === $purge_permalink) {
                continue;
            }
            if ($url === $home_url) {
                self::purge_cache_home(['blocking' => false, 'blocking_cf' => false, 'preload' => false]);
            } else {
                self::nginx_purge_cache_url($url, true, false);
            }
        }

        // run purge cloudflare cache
        if (self::cloudflare_is_enabled('purgeurl')) {
            $returncf = self::purge_cloudflare_cache($purge_urls, $args);
            if (isset($returncf['code']) && !empty($returncf['status'])) {
                $return['code2'] = $returncf['code'];
                $return['status2'] = $returncf['status'];
            }
        }

        // run hook after purge post
        do_action('runcloud_purge_nginx_cache_post', $post_id);

        if ($args['preload'] && !empty($purge_permalink)) {
            self::fetch_preload($purge_permalink, false);
        }

        if (!empty($purge_permalink)) {
            $page_path = '/'.str_replace(self::home_url('/'), '', $purge_permalink);
            $setting = self::get_main_setting();
            if (!empty($setting['exclude_url_onn']) && isset($setting['exclude_url_mch'])) {
                $exclude_url_rules = implode('|', $setting['exclude_url_mch']);
                if (!empty($exclude_url_rules)) {
                    if (preg_match('('.$exclude_url_rules.')', $page_path)) {
                        return array(
                            'code' => 200,
                            'status' => esc_html__('Purge action is not needed for this post/page because it is excluded from page cache based on cache exclusion rules.', 'runcloud-hub'),
                            'permalink' => $purge_permalink,
                            'page_path' => $page_path,
                        );
                    }
                }
            }
        }

        if (!empty($return['code']) && (int)$return['code'] === 200 && $return['code'] !== '200-unknown') {
            if (!empty($plugins)) {
                $return['status'] = sprintf(esc_html__('Purge %s and NGINX page cache of this post/page was successful', 'runcloud-hub'), implode(', ', $plugins));
            }
            else {
                $return['status'] = esc_html__('Purge NGINX page cache of this post/page was successful', 'runcloud-hub');
            }
        }

        return $return;
    }

    /**
     * pre_term_update.
     */
    public static function pre_term_update($term_id) {
        if (empty($term_id)) {
            return;
        }

        $term = get_term($term_id);
        if (!empty($term)) {
            self::$updated_terms[$term_id] = $term;
        }
    }

    /**
     * purge_cache_term.
     */
    public static function purge_cache_term($term_id)
    {
        self::debug(__METHOD__, ['term_id' => $term_id]);

        $term_permalink = get_term_link($term_id);

        $purge_urls = [];

        if (!empty($term_permalink)) {
            self::nginx_purge_cache_url($term_permalink, true, false);
            $purge_urls[] = $term_permalink;
        }

        $term_before = !empty(self::$updated_terms[$term_id]) ? self::$updated_terms[$term_id] : null;

        if (!empty($term_before)) {
            $old_permalink = get_term_link($term_before);
            if (!empty($old_permalink)) {
                if ($old_permalink !== $term_permalink) {
                    self::nginx_purge_cache_url($old_permalink, true, false);
                    $purge_urls[] = $old_permalink;
                }
            }
        }

        if (!empty($purge_urls) && self::cloudflare_is_enabled('purgeurl')) {
            self::purge_cloudflare_cache($purge_urls);
        }

        // run hook after purge term
        do_action('runcloud_purge_nginx_cache_term', $term_id);
    }

    /**
     * purge_cache_user.
     */
    public static function purge_cache_user($user_id)
    {
        self::debug(__METHOD__, ['user_id' => $user_id]);

        $user_permalink = function_exists('get_author_posts_url') ? get_author_posts_url($user_id) : null;

        $purge_urls = [];

        if (!empty($user_permalink)) {
            self::nginx_purge_cache_url($user_permalink, true, false);
            $purge_urls[] = $user_permalink;
        }

        if (!empty($purge_urls) && self::cloudflare_is_enabled('purgeurl')) {
            self::purge_cloudflare_cache($purge_urls);
        }

        // run hook after purge user
        do_action('runcloud_purge_nginx_cache_user', $user_id);
    }

    /**
     * upgrade_plugin.
     */
    public static function upgrade_plugin()
    {
        if (self::is_defined_halt()) {
            return;
        }

        $ver = get_option(self::$db_version);

        if (version_compare($ver, '1.2.0', '<')) {
            $options_default = self::default_settings();
            $options         = self::get_setting();

            $ignore_query_default = array(
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_expid',
                'fb_action_ids',
                'fb_action_types',
                'fb_source',
                'fbclid',
                'gclid',
                'age-verified',
                'ao_noptimize',
                'usqp',
                'cn-reloaded',
                '_ga',
            );

            $ignore_query_mch = $options['ignore_query_mch'];
            if (!is_array($ignore_query_mch) || empty($ignore_query_mch)) {
                $ignore_query_mch = $ignore_query_default;
            }

            $allow_query_mch = $options['allow_query_mch'];
            if (!is_array($allow_query_mch) || empty($allow_query_mch)) {
                $allow_query_mch = [];
            }

            $needs_update = false;
            if (!empty($allow_query_mch)) {
                foreach ($allow_query_mch as $query_key => $query_mch) {
                    if(in_array($query_mch, $ignore_query_default)) {
                        unset($allow_query_mch[$query_key]);
                        if(!in_array($query_mch, $ignore_query_mch)) {
                            $ignore_query_mch[] = $query_mch;
                        }
                        $needs_update = true;
                    }
                }
            }

            self::update_setting_var('ignore_query_onn', 1);
            if ($needs_update) {
                self::update_setting_var('ignore_query_mch', $ignore_query_mch);
                self::update_setting_var('allow_query_mch', $allow_query_mch);
            }

            $exclude_cookie_mch = $options['exclude_cookie_mch'];
            if (!is_array($exclude_cookie_mch) || empty($exclude_cookie_mch)) {
                $exclude_cookie_mch = [];
            }

            $needs_update = false;

            if (!empty($exclude_cookie_mch)) {
                foreach ($exclude_cookie_mch as $cookie_key => $cookie_mch) {
                    if(in_array($cookie_mch, array('wordpress_no_cache', 'comment_author', 'wp-postpass', 'woocommerce_recently_viewed'))) {
                        unset($exclude_cookie_mch[$cookie_key]);
                        $needs_update = true;
                    }
                    elseif($cookie_mch == 'wordpress_[a-f0-9]+') {
                        $exclude_cookie_mch[$cookie_key] = 'wordpress_sec_[a-f0-9]+';
                        $needs_update = true;
                    }
                }
            }

            if ($needs_update) {
                self::update_setting_var('exclude_cookie_mch', $exclude_cookie_mch);
            }

            $exclude_url_mch = $options['exclude_url_mch'];
            if (!is_array($exclude_url_mch) || empty($exclude_url_mch)) {
                $exclude_url_mch = [];
            }

            $needs_update = false;

            if (!empty($exclude_url_mch)) {
                foreach ($exclude_url_mch as $url_key => $url_mch) {
                    if(in_array($url_mch, array('/store.*', '/addons.*', '/xmlrpc.php', 'wp-comments-popup.php', 'wp-links-opml.php', 'wp-locations.php', '[a-z0-9_-]+-sitemap([0-9]+)?.xml', '[a-z0-9_-]+-sitemap([0-9]+)'))) {
                        unset($exclude_url_mch[$url_key]);
                        $needs_update = true;
                    }
                    elseif(in_array($url_mch, array('sitemap(_index)?.xml', 'sitemap(_index)'))) {
                        $exclude_url_mch[$url_key] = 'sitemap.*.xml';
                        $needs_update = true;
                    }
                }
            }

            if ($needs_update) {
                self::update_setting_var('exclude_url_mch', $exclude_url_mch);
            }

            if ($options['preload_onn']) {
                self::update_setting_var('preload_post_onn', 1);
            }
        }

        if (version_compare($ver, '1.4.4', '<')) {
            $options_default = self::default_settings();
            $options         = self::get_setting();

            $needs_update = false;

            $exclude_url_mch_default = $options_default['exclude_url_mch'];
            $exclude_url_mch = $options['exclude_url_mch'];
            if (is_array($exclude_url_mch) && !empty($exclude_url_mch)) {
                if (!in_array('/wp-json/', $exclude_url_mch)) {
                    $exclude_url_mch[] = '/wp-json/';
                    $needs_update = true;
                }
            } else {
                $exclude_url_mch = $exclude_url_mch_default;
                $needs_update = true;
            }

            if ($needs_update) {
                self::update_setting_var('exclude_url_mch', $exclude_url_mch);
            }
        }

        if ($ver == self::$version) {
            return;
        }

        update_option(self::$db_version, self::$version);

        self::debug(__METHOD__, sprintf(esc_html__('Upgrade to v%s ', 'runcloud-hub'), self::$version));

        self::clean_setup();
    }

    /**
     * clean_setup.
     */
    public static function clean_setup()
    {
        self::debug(__METHOD__, 'event run');

        if (is_multisite()) {
            self::purge_cache_all_sites();
        }
        else {
            self::purge_cache_all();
        }

        self::try_setup_dropin();

        self::rcapi_fetch_stats(false);

        self::rcapi_push();
    }

    /**
     * purge_woo_product.
     */
    public static function purge_woo_product($product)
    {
        self::debug(__METHOD__, 'event run');

        $product_id = $product->get_id();

        if (empty($product_id)) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        $args = [];

        $args['blocking'] = false;
        $args['purge_post'] = true;
        $args['purge_cpt_archive'] = (bool) $options['archives_cpt_onn'];
        $args['purge_category'] = (bool) $options['archives_category_onn'];
        $args['preload'] = (bool) $options['preload_post_onn'];

        self::purge_cache_post($product_id, $args);
    }

    /**
     * purge_woo_product_variation.
     */
    public static function purge_woo_product_variation($variation_id)
    {
        self::debug(__METHOD__, 'event run');

        $product_id = wp_get_post_parent_id($variation_id);

        if (empty($product_id)) {
            return;
        }

        // exit early if options is empty
        $options = self::get_setting();
        if (empty($options)) {
            return;
        }

        $args = [];

        $args['blocking'] = false;
        $args['purge_post'] = true;
        $args['purge_cpt_archive'] = (bool) $options['archives_cpt_onn'];
        $args['purge_category'] = (bool) $options['archives_category_onn'];
        $args['preload'] = (bool) $options['preload_post_onn'];

        self::purge_cache_post($product_id, $args);
    }

    /**
     * is_known_plugins_active.
     */
    private static function is_known_plugins_active()
    {

        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            return true;
        }

        // Fast Velocity Minify
        if (function_exists('fvm_purge_all')) {
            return true;
        }

        // WPRocket
        if (function_exists('rocket_clean_domain')) {
            return true;
        }

        // Swift Performance
        if (class_exists('Swift_Performance_Cache')) {
            return true;
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            return true;
        }
 
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            return true;
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            return true;
        }

        // Hyper Cache
        if (class_exists('HyperCache')) {
            return true;
        }

        // WP Optimize
        if (function_exists('wpo_cache_flush')) {
            return true;
        }

        return false;
    }

    /**
     * purge_cache_plugins_all.
     */
    private static function purge_cache_plugins_all()
    {

        // Remove Action Hooks
        remove_action('autoptimize_action_cachepurged', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('fvm_after_purge_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('after_rocket_clean_domain', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('swift_performance_after_clear_all_cache', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('wpfc_delete_cache', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('wp_cache_cleared', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('w3tc_flush_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('w3tc_flush_posts', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('hyper_cache_flush_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('hyper_cache_purged', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        remove_action('wpo_cache_flush', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        $plugins = [];

        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall_actionless();
            $plugins[] = 'Autoptimize';
            self::debug(__METHOD__, 'Autoptimize');
        }

        // Fast Velocity Minify
        if (function_exists('fvm_purge_all')) {
            fvm_purge_all();
            $plugins[] = 'Fast Velocity Minify';
            self::debug(__METHOD__, 'Fast Velocity Minify');
        }

        // WPRocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            $plugins[] = 'WP Rocket';
            self::debug(__METHOD__, 'WPRocket');
        }

        // Swift Performance
        if (class_exists('Swift_Performance_Cache')) {
            Swift_Performance_Cache::clear_all_cache();
            $plugins[] = 'Swift Performance';
            self::debug(__METHOD__, 'Swift Performance');
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            $wpfc = new WpFastestCache();
            $wpfc->deleteCache();
            $plugins[] = 'WP Fastest Cache';
            self::debug(__METHOD__, 'WP Fastest Cache');
        }
 
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            if (is_multisite()) {
                $blog_id = get_current_blog_id();
                wp_cache_clear_cache($blog_id);
            } 
            else {
                wp_cache_clear_cache();
            }
            $plugins[] = 'WP Super Cache';
            self::debug(__METHOD__, 'WP Super Cache');
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            $plugins[] = 'W3 Total Cache';
            self::debug(__METHOD__, 'W3 Total Cache');
        }

        // Hyper Cache
        if (class_exists('HyperCache')) {
            $hypercache = new HyperCache();
            $hypercache->clean();
            $plugins[] = 'Hyper Cache';
            self::debug(__METHOD__, 'Hyper Cache');
        }

        // WP Optimize
        if (function_exists('wpo_cache_flush')) {
            wpo_cache_flush();
            $plugins[] = 'WP Optimize';
            self::debug(__METHOD__, 'WP Optimize');
        }

        return $plugins;
    }

    /**
     * purge_cache_plugins_post.
     */
    private static function purge_cache_plugins_post($post_id)
    {

        $plugins = [];

        // WPRocket
        if (function_exists('rocket_clean_post')) {
            rocket_clean_post($post_id);
            $plugins[] = 'WP Rocket';
            self::debug(__METHOD__, 'WPRocket');
        }

        // WP Super Cache
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($post_id);
            $plugins[] = 'WP Super Cache';
            self::debug(__METHOD__, 'WP Super Cache');
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_post')) {
            w3tc_flush_post($post_id);
            $plugins[] = 'W3 Total Cache';
            self::debug(__METHOD__, 'W3 Total Cache');
        }

        return $plugins;
    }

    /**
     * register_purge_hooks.
     */
    public static function register_purge_hooks()
    {

        // run plugin upgrader if needed after upgrading plugin
        add_action('admin_init', [__CLASS__, 'upgrade_plugin'], self::$ordlast);

        // clear cache when finish upgrading theme / plugin
        add_action('upgrader_process_complete', [__CLASS__, 'purge_cache_on_upgrader'], self::$ordlast, 2);

        // clear cache when a plugin is activated/deactivated
        add_action('activated_plugin', [__CLASS__, 'purge_cache_on_plugin_activate'], self::$ordlast, 2);
        add_action('deactivated_plugin', [__CLASS__, 'purge_cache_on_plugin_deactivate'], self::$ordlast, 2);

        // clear cache when switch to different theme
        add_action('switch_theme', [__CLASS__, 'purge_cache_on_theme_switch'], self::$ordlast);

        // clear cache when permalink structure is updated
        add_action('permalink_structure_changed', [__CLASS__, 'purge_cache_on_permalink_update'], self::$ordlast);
        add_action('update_option_category_base', [__CLASS__, 'purge_cache_on_permalink_update'], self::$ordlast);
        add_action('update_option_tag_base', [__CLASS__, 'purge_cache_on_permalink_update'], self::$ordlast);

        // clear cache when home/siteurl/blogname/blogdescription is updated
        add_action('update_option_home', [__CLASS__, 'purge_cache_on_general_update'], self::$ordlast);
        add_action('update_option_siteurl', [__CLASS__, 'purge_cache_on_general_update'], self::$ordlast);
        add_action('update_option_blogname', [__CLASS__, 'purge_cache_on_general_update'], self::$ordlast);
        add_action('update_option_blogdescription', [__CLASS__, 'purge_cache_on_general_update'], self::$ordlast);

        // clear cache when customizer / theme_mods is saved
        add_action('customize_save', [__CLASS__, 'purge_cache_on_customize_save'], self::$ordlast);

        // clear cache when update a menu item
        add_action('wp_update_nav_menu', [__CLASS__, 'purge_cache_on_navmenu_update'], self::$ordlast);

        // clear cache when add/remove sidebar widgets
        add_action('update_option_sidebars_widgets', [__CLASS__, 'purge_cache_on_sidebar_update'], self::$ordlast);

        // clear cache when update a widget parameter
        add_filter('widget_update_callback', [__CLASS__, 'purge_cache_on_widget_update'], self::$ordlast);

        // clear cache on post status change (publish/future/trash)
        add_action('wp_after_insert_post', [__CLASS__, 'purge_cache_on_post_after_insert'], self::$ordlast, 4);

        // clear cache on comment
        add_action('wp_insert_comment', [__CLASS__, 'purge_cache_on_comment'], self::$ordlast, 2);

        // clear cache on comment status change
        add_action('transition_comment_status', [__CLASS__, 'purge_cache_on_comment_status_change'], self::$ordlast, 3);

        // clear cache when edit/delete terms
        add_action('edit_terms', [__CLASS__, 'pre_term_update'], self::$ordlast, 2);
        add_action('edited_term', [__CLASS__, 'purge_cache_term'], self::$ordlast);
        add_action('pre_delete_term', [__CLASS__, 'purge_cache_term'], self::$ordlast);

        // clear cache when update/delete user
        add_action('profile_update', [__CLASS__, 'purge_cache_user'], self::$ordlast);
        add_action('delete_user', [__CLASS__, 'purge_cache_user'], self::$ordlast);

        // WooCommerce
        add_action('woocommerce_product_set_stock', [__CLASS__, 'purge_woo_product'], self::$ordlast);
        add_action('woocommerce_variation_set_stock', [__CLASS__, 'purge_woo_product'], self::$ordlast);
        add_action('woocommerce_rest_insert_product_object', [__CLASS__, 'purge_woo_product'], self::$ordlast);
        add_action('woocommerce_save_product_variation', [__CLASS__, 'purge_woo_product_variation'], self::$ordlast);

        // Autoptimize
        add_action('autoptimize_action_cachepurged', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // Fast Velocity Minify
        add_action('fvm_after_purge_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // WPRocket
        add_action('after_rocket_clean_domain', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // Swift Performance
        add_action('swift_performance_after_clear_all_cache', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // WP Fastest Cache
        add_action('wpfc_delete_cache', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // WP Super Cache
        add_action('wp_cache_cleared', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // W3 Total Cache
        add_action('w3tc_flush_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        add_action('w3tc_flush_posts', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // Hyper Cache
        add_action('hyper_cache_flush_all', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        add_action('hyper_cache_purged', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // WP Optimize
        add_action('wpo_cache_flush', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);

        // Beaver Builder
        add_action('fl_builder_cache_cleared', [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
        add_action('fl_builder_before_save_layout', [__CLASS__, 'purge_cache_on_post_change'], self::$ordlast);
        add_action('fl_builder_after_save_user_template', [__CLASS__, 'purge_cache_on_post_change'], self::$ordlast);

        // Custom Purger Hooks via filter
        $purge_action_hooks = apply_filters('runcloud_purge_nginx_cache_hooks', []);
        if (!empty($purge_action_hooks)) {
            foreach ($purge_action_hooks as $purge_action_hook) {
                add_action($purge_action_hook, [__CLASS__, 'purge_cache_all_noaction'], self::$ordlast);
            }
        }

        add_action('shutdown', [__CLASS__, 'add_redis_stats'], self::$ordlast);

        add_action('shutdown', [__CLASS__, 'add_html_footprint'], self::$ordlast);
    }

    /**
     * register_integrations.
     */
    public static function register_integrations()
    {
        // Site Health
        add_action('site_status_page_cache_supported_cache_headers', [__CLASS__, 'site_health_headers']);

        // Automatic cache exclusion
        add_action('admin_init', [__CLASS__, 'admin_donotcachepage_helper'], self::$ordlast);
        add_action('init', [__CLASS__, 'donotcachepage_helper'], self::$ordlast);

        // WPRocket
        add_action('rocket_htaccess_mod_expires', [__CLASS__, 'wprocket_remove_html_expire'], 5);
        add_action('after_rocket_clean_files', [__CLASS__, 'wprocket_after_clean_files'], self::$ordlast);
        // add_filter('do_rocket_generate_caching_files', '__return_false', self::$ordlast);
        add_filter('rocket_cache_mandatory_cookies', '__return_empty_array', self::$ordlast);
        add_filter('rocket_display_varnish_options_tab', '__return_false');
        add_filter('pre_get_rocket_option_cache_mobile', '__return_true');
        add_filter('pre_get_rocket_option_do_caching_mobile_files', '__return_false');

        add_filter('runcloud_purge_urls', [__CLASS__, 'wpml_purge_urls'], 10, 2);
        add_filter('runcloud_purge_urls', [__CLASS__, 'translatepress_purge_urls'], 10, 2);

        add_filter('runcloud_preload_urls', [__CLASS__, 'wpml_preload_urls'], 10);
        add_filter('runcloud_preload_urls', [__CLASS__, 'translatepress_preload_urls'], 10);
    }

    /**
     * admin_donotcachepage_helper.
     */
    public static function admin_donotcachepage_helper() {
        if ( defined('WP_CLI') && WP_CLI ) {
            return;
        }
        if (self::cloudflare_is_enabled()) {
            add_filter('nocache_headers', [__CLASS__, 'donotcachepage_headers'], self::$ordlast);
            nocache_headers();
        }
    }

    /**
     * donotcachepage_helper.
     */
    public static function donotcachepage_helper() {
        if ( defined('WP_CLI') && WP_CLI ) {
            return;
        }
        add_action('template_redirect', [__CLASS__, 'donotcachepage_check'], self::$ordlast);
    }

    /**
     * donotcachepage_check.
     */
    public static function donotcachepage_check() {
        $setting = self::get_main_setting();

        if (empty($setting['exclude_auto_onn'])) {
            return;
        }

        $donotcache = false;

        if (is_front_page()) {
            return;
        }

        $page_path = isset($_SERVER["REQUEST_URI"]) ? strtok($_SERVER["REQUEST_URI"], '?') : '';

        if (empty($page_path) || $page_path == '/') {
            return;
        }

        // Add path validation to check for forbidden characters
        if (preg_match('/[,\;\t\r\n\013\014 ]/', $page_path)) {
            // Invalid path, do not set the cookie
            return;
        }

        if (!empty($setting['exclude_url_onn']) && isset($setting['exclude_url_mch'])) {
            $exclude_url_rules = implode('|', $setting['exclude_url_mch']);
            if (!empty($exclude_url_rules)) {
                if (preg_match('('.$exclude_url_rules.')', $page_path)) {
                    if (self::cloudflare_is_enabled()) {
                        $donotcache = true;
                    }
                    else {
                        return;
                    }
                }
            }
        }

        if (!$donotcache && defined( 'DONOTCACHEPAGE')) {
           $donotcache = true;
        }

        if (!$donotcache && is_singular()) {
            global $post;
            if ( !empty( $post->post_password ) ) {
                if (!empty($setting['exclude_cookie_mch']) && is_array($setting['exclude_cookie_mch']) && in_array('wp-postpass', $setting['exclude_cookie_mch'])) {
                    return;
                }
                else {
                    $donotcache = true;
                }
            }
        }

        if (!$donotcache) {
            return;
        }

        add_filter('nocache_headers', [__CLASS__, 'donotcachepage_headers'], self::$ordlast);
        nocache_headers();
    
        header( 'wordpress-no-cache: true', false );

        setcookie( 'wordpress_no_cache', 1, 0, $page_path, '', true, true );
    }

    /**
     * donotcachepage_headers.
     */
    public static function donotcachepage_headers() {
        return array(
            'Cache-Control'   => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'          => 'no-cache',
            'Expires'         => 'Wed, 14 Sep 1977 05:00:00 GMT',
            'Last-Modified'   => gmdate('D, d M Y H:i:s') . ' GMT',
            'X-Accel-Expires' => '0'
        );
    }

    /**
     * wprocket_remove_html_expire.
     */
    public static function wprocket_remove_html_expire( $rules ) {
        $rules = preg_replace( '@\s*#\s*Your document html@', '', $rules );
        $rules = preg_replace( '@\s*ExpiresByType text/html\s*"access plus \d+ (seconds|minutes|hour|week|month|year)"@', '', $rules );

        return $rules;
    }

    /**
     * wprocket_after_clean_files.
     */
    public static function wprocket_after_clean_files( $urls ) {
        self::debug(__METHOD__, 'event run');

        foreach ($urls as $url) {
            self::nginx_purge_cache_url($url, true, false);
        }
    }

    /**
     * wpml_purge_urls.
     */
    public static function wpml_purge_urls($purge_urls, $post_id) {
        if (!defined('ICL_SITEPRESS_VERSION')) {
            return $purge_urls;
        }

        $languages = apply_filters('wpml_active_languages', null, []);

        $purge_urls_extra = [];

        foreach ($purge_urls as $url) {
            foreach ($languages as $lang) {
                $url_converted = apply_filters('wpml_permalink', $url, $lang['code'], true);

                if (!empty($url_converted) && !in_array($url_converted, $purge_urls)) {
                    $purge_urls_extra[] = $url_converted;
                }
            }
        }

        if (!empty($purge_urls_extra)) {
            $purge_urls = array_merge($purge_urls, $purge_urls_extra);
        }

        return $purge_urls;
    }

    /**
     * wpml_preload_urls.
     */
    public static function wpml_preload_urls($preload_urls) {
        if (!defined('ICL_SITEPRESS_VERSION')) {
            return $preload_urls;
        }

        $languages = apply_filters('wpml_active_languages', null, []);

        $updated_urls = [];
        foreach ($preload_urls as $preload_id => $preload_url) {
            if (is_int($preload_id)) {
                $post_id = $preload_id;
                global $sitepress;
                if (!empty($sitepress) && method_exists($sitepress, 'convert_url') && method_exists($sitepress, 'get_language_for_element')) {
                    $lang_code = $sitepress->get_language_for_element( $post_id, 'post_' . get_post_type( $post_id ) );

                    $url_converted = apply_filters('wpml_permalink', $preload_url, $lang_code, true);

                    // remove_all_filters('post_link');
                    // remove_all_filters('page_link');
                    // remove_all_filters('post_type_link');
                    // $preload_url = get_permalink($post_id);
                    // $url_converted = $sitepress->convert_url( $preload_url, $lang_code );

                    if (!empty($url_converted)) {
                        $updated_urls[$lang_code] = $url_converted;
                    }
                }
            }
        }

        if (!empty($updated_urls)) {
            return $updated_urls;
        }

        return $preload_urls;
    }

    /**
     * translatepress_purge_urls.
     */
    public static function translatepress_purge_urls($purge_urls, $post_id) {
        if (!class_exists('TRP_Translate_Press')) {
            return $purge_urls;
        }

        if (!function_exists('trp_get_languages')) {
            return $purge_urls;
        }

        if (empty($purge_urls)) {
            return $purge_urls;
        }

        global $TRP_LANGUAGE;
        $trp = \TRP_Translate_Press::get_trp_instance();
        $url_converter = $trp->get_component('url_converter');
        $languages = trp_get_languages();

        $purge_urls_extra = [];

        $unique_key = self::unique_key().'/';
        foreach ($purge_urls as $url) {
            foreach ($languages as $lang_code => $lang_value) {
                $url .= $unique_key;
                $url_converted = $url_converter->get_url_for_language($lang_code, $url, $unique_key);
                $url_converted = str_replace($unique_key, '', $url_converted);

                if (!empty($url_converted) && !in_array($url_converted, $purge_urls)) {
                    $purge_urls_extra[] = $url_converted;
                }
            }
        }

        if (!empty($purge_urls_extra)) {
            $purge_urls = array_merge($purge_urls, $purge_urls_extra);
        }

        return $purge_urls;
    }

    /**
     * translatepress_preload_urls.
     */
    public static function translatepress_preload_urls($preload_urls) {
        if (!class_exists('TRP_Translate_Press')) {
            return $preload_urls;
        }

        if (!function_exists('trp_get_languages')) {
            return $preload_urls;
        }

        if (empty($preload_urls)) {
            return $preload_urls;
        }

        global $TRP_LANGUAGE;
        $trp = \TRP_Translate_Press::get_trp_instance();
        $url_converter = $trp->get_component('url_converter');
        $languages = trp_get_languages();

        $updated_urls = [];
        $unique_key = self::unique_key().'/';
        foreach ($preload_urls as $preload_id => $preload_url) {
            if (is_int($preload_id) || $preload_id == 'home') {
                foreach ($languages as $lang_code => $lang_value) {
                    $preload_url .= $unique_key;
                    $url_converted = $url_converter->get_url_for_language($lang_code, $preload_url, $unique_key);
                    $url_converted = str_replace($unique_key, '', $url_converted);

                    if (!empty($url_converted)) {
                        $updated_urls[$lang_code] = $url_converted;
                    }
                }
            }
        }

        if (!empty($updated_urls)) {
            return $updated_urls;
        }

        return $preload_urls;
    }

    /**
     * site_health_headers.
     */
    public static function site_health_headers( $cache_headers ) {
        $cache_headers['X-RunCloud-Cache'] = static function ( $header_value ) {
            return false !== strpos( strtolower( $header_value ), 'hit' );
        };

        $cache_headers['X-RunCloud-SRCache-Fetch'] = static function ( $header_value ) {
            return false !== strpos( strtolower( $header_value ), 'hit' );
        };

        return $cache_headers;
    }

    /**
     * add_html_footprint.
     */
    public static function add_html_footprint() {
        $options = self::get_setting();
        if (!$options['html_footprint_onn']) {
            return;
        }

        if (is_admin()) {
            return;
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        $footprint = "\n<!-- " . 'RunCloud Hub ' . current_time( 'mysql' ) . " -->";
        echo wp_kses( $footprint, [] );
    }

    /**
     * get_total_posts.
     */
    private static function get_total_posts()
    {
        $post_types = get_post_types(['public' => true]);
        $post_types = array_filter($post_types, 'is_post_type_viewable');

        $count_posts = 0;
        foreach ( $post_types as $post_type ) {
            $count = wp_count_posts($post_type);
            $count_posts += $count->publish;
        }
        return $count_posts;
    }

    /**
     * get_preload_urls.
     */
    private static function get_preload_urls($num, $inc = 1)
    {
        if (!$inc) {
            $inc = 1;
        }

        $urls = [];
        $post_types = get_post_types(['public' => true]);
        $post_types = array_filter($post_types, 'is_post_type_viewable');
        $args = array(
            'fields'              => 'ids',
            'posts_per_page'      => $inc,
            'post_type'           => $post_types,
            'offset'              => $num,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'suppress_filters'    => true,
        );
        $posts = get_posts($args);
        if (!empty($posts)) {
            foreach ($posts as $post_id) {
                $urls[$post_id] = get_permalink($post_id);
            }
        }

        return $urls;
    }

    /**
     * get_preload_speed.
     */
    private static function get_preload_speed()
    {
        $options = self::get_setting();
        $preload_speed = intval( $options['preload_speed'] );
        if (!$preload_speed) {
            $preload_speed = 12;
        }
        elseif ($preload_speed > 120) {
            $preload_speed = 120;
        }
        return $preload_speed;
    }

    /**
     * get_preload_inc.
     */
    private static function get_preload_inc()
    {
        $preload_speed = self::get_preload_speed();
        if ( $preload_speed < 60 ) {
            $inc = ceil($preload_speed/6);
        }
        else {
            $inc = 10;
        }
        return $inc;
    }

    /**
     * get_preload_interval.
     */
    private static function get_preload_interval()
    {
        $preload_speed = self::get_preload_speed();
        if ( $preload_speed < 60 ) {
            $interval = 10;
        }
        else {
            $interval = 600/$preload_speed;
        }
        $interval = intval($interval*1000)-250;
        return $interval;
    }

    /**
     * match_wildcard.
     */
    public static function match_wildcard($match, $string)
    {
        if (false === strpos($match, '*')) {
            return (false !== strpos($string, $match) ? true : false);
        } else {
            $wildcard_chars = ['\*', '\?'];
            $regexp_chars   = ['.*', '.'];
            $regex          = str_replace($wildcard_chars, $regexp_chars, preg_quote($match, '/'));
            if (preg_match('/^' . $regex . '$/is', $string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * match_rules.
     */
    public static function match_filter($rules, $string)
    {
        if (is_object($rules)) {
            $rules = json_decode(json_encode($rules), true);
        }

        if (!empty($rules) && is_array($rules)) {
            foreach ($rules as $match) {
                if (self::match_wildcard($match, $string)) {
                    return true;
                }
            }
            return false;
        }

        return self::match_wildcard($rules, $string);
    }

    /**
     * deactivate_runcache_purger.
     */
    public static function maybe_deactivate_runcache_purger()
    {
        $file = 'runcache-purger/runcache-purger.php';

        if (self::is_plugin_active($file)) {
            deactivate_plugins($file);
            self::wp_cache_delete();
            return true;
        }

        return false;
    }

    /**
     * late_purge_cache_redis.
     */
    private static function late_purge_cache_redis()
    {
        self::debug(__METHOD__, 'event run');

        add_action(
            'shutdown',
            function () {
                wp_cache_flush();
            },
            self::$ordlast
        );
    }

    /**
     * late_purge_cache_redis_all.
     */
    private static function late_purge_cache_redis_all()
    {
        self::debug(__METHOD__, 'event run');

        add_action(
            'shutdown',
            function () {
                if (function_exists('wp_cache_flush_sites')) {
                    wp_cache_flush_sites();
                } else {
                    wp_cache_flush();
                }
            },
            self::$ordlast
        );
    }

    /**
     * get_schedule_name.
     */
    private static function get_schedule_name($num)
    {
        foreach (wp_get_schedules() as $name => $arr) {
            if (false !== strpos($name, self::$slug . '-')) {
                if ((int) $num === (int) $arr['interval']) {
                    return $name;
                }
            }
        }
        return null;
    }

    /**
     * callback_cron_purge.
     */
    public static function callback_cron_purge()
    {
        self::debug(__METHOD__, 'event run');
        self::purge_cache_all();
    }

    /**
     * schedule_cron_purge.
     */
    private static function schedule_cron_purge($reset = 0)
    {
        $hook_name = 'cron_purge_' . self::get_hash(self::$slug . 'cron_purge');

        if (!empty($reset)) {
            wp_clear_scheduled_hook($hook_name);
            if (1 === (int) $reset) {
                return true;
            }
        }

        $options = self::get_setting();
        if (!empty($options['schedule_purge_onn'])) {
            $schedule_name = self::get_schedule_name($options['schedule_purge_unt']);
            $duration      = (time() + (int) $options['schedule_purge_var']);
            if (!wp_next_scheduled($hook_name)) {
                return wp_schedule_event($duration, $schedule_name, $hook_name);
            }

            return false;
        }

        return wp_clear_scheduled_hook($hook_name);
    }

    /**
     * callback_cron_preload.
     */
    public static function callback_cron_preload()
    {
        self::debug(__METHOD__, 'event run');

        // clear all cache but do not trigger background preload
        self::$is_run_preload = false;
        self::purge_cache_all();

        $total = self::get_total_posts();
        if ($total) {
            update_option(
                self::$db_preload,
                [
                    'num'    => 0,
                    'total'  => $total,
                    'type'   => 'background',
                ],
                false
            );
            self::schedule_preload_batch();
        }
    }

    /**
     * callback_cron_rcstats.
     */
    public static function callback_cron_rcstats()
    {
        self::debug(__METHOD__, 'event run');
        self::rcapi_fetch_stats(true);
    }

    /**
     * schedule_cron_preload.
     */
    private static function schedule_cron_preload($reset = 0)
    {
        $hook_name = 'cron_preload_' . self::get_hash(self::$slug . 'cron_preload');

        if (!empty($reset)) {
            wp_clear_scheduled_hook($hook_name);
            if (1 === (int) $reset) {
                return true;
            }
        }

        $options = self::get_setting();
        if (!empty($options['preload_schedule_onn'])) {
            $schedule_name = self::get_schedule_name($options['preload_schedule_unt']);
            $duration      = (time() + (int) $options['preload_schedule_var']);
            if (!wp_next_scheduled($hook_name)) {
                return wp_schedule_event($duration, $schedule_name, $hook_name);
            }

            return false;
        }

        return wp_clear_scheduled_hook($hook_name);
    }

    /**
     * schedule_cron_rcstats.
     */
    private static function schedule_cron_rcstats($reset = 0)
    {
        $hook_name = 'cron_rcstats_' . self::get_hash(self::$slug . 'cron_rcstats');

        if (!empty($reset)) {
            wp_clear_scheduled_hook($hook_name);
            if (1 === (int) $reset) {
                return true;
            }
        }

        $options = self::get_setting();
        if (!empty($options['stats_onn']) && !empty($options['stats_schedule_onn'])) {
            $schedule_name = self::get_schedule_name($options['stats_schedule_unt']);
            $duration      = (time() + (int) $options['stats_schedule_var']);
            if (!wp_next_scheduled($hook_name)) {
                return wp_schedule_event($duration, $schedule_name, $hook_name);
            }

            return false;
        }

        return wp_clear_scheduled_hook($hook_name);
    }

    /**
     * register_cron_hooks.
     */
    private static function register_cron_hooks($install = true)
    {
        if ($install) {
            self::schedule_cron_purge();
            self::schedule_cron_preload();
            self::schedule_cron_rcstats();
        } else {
            self::schedule_cron_purge(1);
            self::schedule_cron_preload(1);
            self::schedule_cron_rcstats(1);
        }
    }

    /**
     * view_stats_charts.
     */
    public static function view_stats_charts($name)
    {
        $stats = [
            'type' => 'daily',
            'data' => [],
        ];

        if (!in_array($name, array('health','transfer'))) {
            return $stats;
        }

        $options = self::get_setting();
        if (!empty($options['stats_onn']) && !empty($options['stats_'.$name . '_onn'])) {
            if ($name === 'health') {
                $stats['type'] = in_array($options['stats_health_var'], array('daily','hourly')) ? $options['stats_health_var'] : 'hourly';
            }
            elseif ($name === 'transfer') {
                $stats['type'] = in_array($options['stats_transfer_var'], array('daily','monthly')) ? $options['stats_transfer_var'] : 'daily';
            }

            $data = self::rcapi_get_stats();
            if (!empty($data)) {
                if (!empty($data[$name][$stats['type']])) {
                    $stats['data'] = $data[$name][$stats['type']];
                }
            }
        }

        return $stats;
    }

    /**
     * view_stats_lastupdate.
     */
    public static function view_stats_lastupdate()
    {
        $data = get_option(self::$db_stats);
        if (!empty($data) && !empty($data['lastupdate'])) {
            $lastupdate = $data['lastupdate'];
            $time = human_time_diff(strtotime($lastupdate));
            if ($time) {
                $lastupdate .= sprintf(esc_html__(' (%s ago)', 'runcloud-hub'), $time);
            }
            return $lastupdate;
        }
        return '-';
    }

    /**
     * __shutdown.
     */
    private static function __shutdown()
    {
        add_action(
            'shutdown',
            function () {
                self::wp_cache_delete();
                if ( !self::$onfirehooks ) {
                    wp_cache_flush();
                }
                if (function_exists('wp_cache_self_remove') && (defined('RCWP_REDIS_DROPIN') || defined('RUNCACHE_PURGER_DROPIN'))) {
                    wp_cache_self_remove();
                }
            },
            self::$ordlast
        );
    }

    /**
     * activate.
     */
    public static function activate($network_wide)
    {
        self::$onfirehooks = true;
        self::install_options($network_wide);
        self::maybe_deactivate_runcache_purger();
        self::try_setup_dropin();
    }

    /**
     * deactivate.
     */
    public static function deactivate($network_wide)
    {
        self::$onfirehooks = true;
        self::force_site_deactivate_plugin();
        self::uninstall_dropin(true);
        self::__shutdown();
    }

    /**
     * uninstall.
     */
    public static function uninstall($network_wide)
    {
        self::$onfirehooks = true;
        self::uninstall_options($network_wide);
        self::uninstall_dropin(true);
        self::__shutdown();
    }

    /**
     * register_hook.
     */
    public static function register_plugin_hooks()
    {
        register_activation_hook(RUNCLOUD_HUB_HOOK, [__CLASS__, 'activate']);
        register_deactivation_hook(RUNCLOUD_HUB_HOOK, [__CLASS__, 'deactivate']);
        register_uninstall_hook(RUNCLOUD_HUB_HOOK, [__CLASS__, 'uninstall']);
    }

    /**
     * reinstall_options.
     */
    public static function reinstall_options($all_site = false, $status = false)
    {
        self::debug(__METHOD__, 'event run');

        if (self::is_main_site()) {
            $rcapi_key = self::get_setting('rcapi_key');
            $rcapi_secret = self::get_setting('rcapi_secret');
            $rcapi_webapp_id = self::get_setting('rcapi_webapp_id');
        }

        self::uninstall_options($all_site);
        self::install_options($all_site);

        if (self::is_main_site()) {
            if (!empty($rcapi_key)) {
                self::update_setting_var('rcapi_key', $rcapi_key);
            }
            if (!empty($rcapi_secret)) {
                self::update_setting_var('rcapi_secret', $rcapi_secret);
            }
            if (!empty($rcapi_webapp_id)) {
                self::update_setting_var('rcapi_webapp_id', $rcapi_webapp_id);
            }
        }

        self::clean_setup();

        if ($status) {
            $req_status = [
                'code' => 200,
                'status' => esc_html__('Reset settings was successful.', 'runcloud-hub'),
            ];
            return $req_status;
        }
    }

    /**
     * reinstall_dropin.
     */
    public static function reinstall_dropin($status = false)
    {
        return self::install_dropin(true, $status);
    }

    /**
     * register_wpcli_hooks.
     */
    public static function register_wpcli_hooks()
    {
        if (self::is_wp_cli() && !class_exists('RunCloud_Hub_CLI')) {
            require_once RUNCLOUD_HUB_PATH_LIB . 'wp-cli.php';
            WP_CLI::add_command('runcloud-hub', esc_html__('RunCloud_Hub_CLI', 'runcloud-hub'), ['shortdesc' => esc_html__('Manages RunCloud Hub WordPress Plugin', 'runcloud-hub')]);
            WP_CLI::add_command('runcache-purger', esc_html__('RunCloud_Hub_CLI', 'runcloud-hub'), ['shortdesc' => esc_html__('RunCache Purger for RunCloud Hub', 'runcloud-hub')]);
        }
    }

    /**
     * array_merge_r.
     */
    private static function array_merge_r()
    {
        if (func_num_args() < 2) {
            trigger_error(__FUNCTION__ . ' invalid input', E_USER_WARNING);
            return;
        }

        $arrays = func_get_args();
        $merged = [];

        while ($array = @array_shift($arrays)) {
            if (!is_array($array)) {
                trigger_error(__FUNCTION__ . ' invalid input', E_USER_WARNING);
                return;
            }

            if (empty($array)) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                        $merged[$key] = self::array_merge_r($merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * get_fileperms.
     */
    private static function get_fileperms($type)
    {
        static $perms = [];

        $type = (string) $type;

        if (isset($perms[$type])) {
            return $perms[$type];
        }

        if ('dir' === $type) {
            if (defined('FS_CHMOD_DIR')) {
                $perms[$type] = FS_CHMOD_DIR;
            } else {
                clearstatcache();
                $perms[$type] = fileperms(ABSPATH) & 0777 | 0755;
            }

            return $perms[$type];
        } elseif ('file' === $type) {
            if (defined('FS_CHMOD_FILE')) {
                $perms[$type] = FS_CHMOD_FILE;
            } else {
                clearstatcache();
                $perms[$type] = fileperms(ABSPATH . 'index.php') & 0777 | 0644;
            }

            return $perms[$type];
        }

        return 0755;
    }

    /**
     * fetch_preload.
     */
    private static function fetch_preload($url, $blocking = true)
    {
        $args = [
            'timeout'     => $blocking ? 10 : 0.01,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent'  => self::get_user_agent(),
            'blocking'    => $blocking,
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null,
        ];

        $results = [
            'blocking' => $blocking,
            'code'     => '',
            'status'   => '',
            'host'     => parse_url($url, PHP_URL_HOST),
            'url'      => $url,
            'method'   => 'GET',
            'error'    => '',
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $results['status'] = wp_remote_retrieve_response_message($response);
            $results['error']  = wp_strip_all_tags($response->get_error_message());
        } else {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $results['status'] = wp_remote_retrieve_response_message($response);
            $results['header'] = (isset($response['headers']) && is_object($response['headers']) ? (array) $response['headers'] : null);
        }

        self::debug(__METHOD__, $results);
        return (empty($results['error']) ? true : false);
    }

    /**
     * schedule_preload_batch.
     */
    private static function schedule_preload_batch($reset = 0)
    {
        $hook_name = 'preload_batch_' . self::get_hash(self::$slug . '_preload_batch');

        if (!empty($reset)) {
            self::debug(__METHOD__, 'schedule preload batch cleared');
            wp_clear_scheduled_hook($hook_name);
        }
        else {
            if (!wp_next_scheduled($hook_name)) {
                self::debug(__METHOD__, 'schedule preload batch');
                wp_schedule_event(time(), 'runcloud-hub-minute', $hook_name);
            }
        }
    }

    /**
     * callback_preload_batch.
     */
    public static function callback_preload_batch()
    {
        self::fastcgi_close();

        $preload = get_option(self::$db_preload);
        if (empty($preload)) {
            self::schedule_preload_batch(1);
            return;
        }

        if (!isset($preload['num'], $preload['total'], $preload['type'])) {
            self::schedule_preload_batch(1);
            return;
        }

        if ($preload['type'] !== 'background') {
            self::schedule_preload_batch(1);
            return;
        }

        $num = (int) $preload['num'];
        $total = (int) $preload['total'];
        $type = $preload['type'];

        if (!$total || ($total && $num >= $total)) {
            self::schedule_preload_batch(1);
            self::debug(__METHOD__, sprintf( esc_html__('Background Preload is finished, num = %s, total = %s', 'runcloud-hub'), $num, $total ) );
            return;
        }

        $setting = self::get_main_setting();
        $exclude_url_rules = implode('|', $setting['exclude_url_mch']);

        if ($num == 0) {
            // preload homepage
            $preload_urls = ['home' => self::home_url('/')];
            $preload_urls = apply_filters('runcloud_preload_urls', $preload_urls);

            foreach ($preload_urls as $preload_id => $preload_url) {
                $desc = $preload_url;
                if ($preload_id !== 'home') {
                    $desc .= ' ('.$preload_id.')';
                }
                if (!empty($exclude_url_rules) && preg_match('('.$exclude_url_rules.')', $preload_url)) {
                    $desc .= ' (skipped/excluded)';
                } else {
                    self::fetch_preload($preload_url, false);
                }

                self::debug(__METHOD__, sprintf( esc_html__('Background Preload Homepage : %s', 'runcloud-hub'), $desc ) );
            }

            // preload path
            $options = self::get_setting();
            if (!empty($options['preload_path_onn']) && !empty($options['preload_path_mch']) && is_array($options['preload_path_mch'])) {
                foreach ($options['preload_path_mch'] as $path) {
                    if ('/' === $path) {
                        continue;
                    }
                    $path    = strtolower($path);
                    $preload_url = self::home_url('/') . ltrim($path, '/');
                    self::fetch_preload($preload_url, false);
                }
            }
        }

        $speed = self::get_preload_speed();
        $urls = self::get_preload_urls($num, $speed);
        if (!empty($urls)) {
            $count = count($urls);
            $num_batch = $num + $count;
            $preload['num'] = $num_batch;
            update_option(self::$db_preload, $preload, false);
            if ($num_batch >= $total) {
                self::schedule_preload_batch(1);
            }

            $i = 0;
            $num_fetch = $num;
            foreach ($urls as $post_id => $url) {
                $i++;
                $num_fetch++;

                // allow user to filter
                $preload_urls = [$post_id => $url];
                $preload_urls = apply_filters('runcloud_preload_urls', $preload_urls);

                foreach ($preload_urls as $preload_id => $preload_url) {
                    $return = $preload_url;
                    if (!is_int($preload_id)) {
                        $return .= ' ('.$preload_id.')';
                    }
                    if (!empty($exclude_url_rules) && preg_match('('.$exclude_url_rules.')', $preload_url)) {
                        $return .= ' (skipped/excluded)';
                    } else {
                        self::fetch_preload($preload_url, false);
                    }

                    self::debug(__METHOD__, sprintf( esc_html__('Background Preload %s / %s : %s', 'runcloud-hub'), $num_fetch, $total, $return ) );
                }

                if ($i == 10 && $count > 10) {
                    $i = 0;
                    sleep(4);
                }
            }

            if ($num_batch >= $total) {
                delete_option(self::$db_preload);
                self::debug(__METHOD__, sprintf( esc_html__('Background Preload is finished, num = %s, total = %s', 'runcloud-hub'), $num_batch, $total ) );
            }
        }
    }

    /**
     * cloudflare_update_settings.
     */
    public static function cloudflare_update_settings()
    {
        $data = [];
        $data['status']  = '';
        $data['email']   = '';
        $data['apikey']  = '';
        $data['zoneid']  = '';
        $data['message'] = '';

        $options = self::get_setting();
        if (empty($options)) {
            $data['status'] = 'disabled';
        }

        if ( ! (bool) $options['cloudflare_onn'] ) {
            $data['status'] = 'disabled';
        }

        if ( empty( $options['cloudflare_apikey'] ) ) {
            $data['status'] = 'disabled';
            $data['message'] = sprintf( esc_html__('Cloudflare integration is disabled: %s', 'runcloud-hub'), esc_html__('Missing Cloudflare Global API Key or API Token', 'runcloud-hub'));
        }
        else {
            $data['email'] = trim($options['cloudflare_email']);

            if ( !empty( $options['cloudflare_email'] ) && strlen( $options['cloudflare_email']) <= 37 && preg_match('/^[0-9a-f]+$/',  $options['cloudflare_email'])) {
                if ( empty( $options['cloudflare_email'] ) ) {
                    $data['status'] = 'disabled';
                    $data['message'] = sprintf( esc_html__('Cloudflare integration is disabled: %s', 'runcloud-hub'), esc_html__('Missing Cloudflare email', 'runcloud-hub'));
                }
            }
        }

        if ($data['status'] !== 'disabled') {
            $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);

            $data['hostname'] = $hostname;

            preg_match_all('/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/\n]+)/im', $hostname, $matches);
            $domain = isset($matches[1][0]) ? $matches[1][0] : $hostname;

            while (substr_count($domain, '.') > 0) {
                $method = 'GET';
                $endpoint = 'zones?name='.$domain.'&status=active';
                $args = [];
                $results = self::request_cloudflare($method, $endpoint, $args);

                if (!empty($results['result'][0]['id']) && !empty($results['result'][0]['status'])) {
                    break;
                }
                
                $domain = implode('.', array_slice(explode('.', $domain), 1));
            }

            if (!empty($results['result'][0]['id']) && !empty($results['result'][0]['status'])) {
                $data['zoneid']       = $results['result'][0]['id'];
                $data['name']         = $results['result'][0]['name'];
                $data['status']       = $results['result'][0]['status'];
                $data['paused']       = $results['result'][0]['paused'];
                $data['type']         = $results['result'][0]['type'];
                $data['dev']          = $results['result'][0]['development_mode'];
                $data['account_id']   = $results['result'][0]['account']['id'];
                $data['account_name'] = $results['result'][0]['account']['name'];

                $is_subdomain = $hostname !== $domain && $hostname !== 'www.'.$domain ? true : false;
                if (!$is_subdomain) {
                    $data['message'] = sprintf( esc_html__('Cloudflare integration is enabled for domain %s (Zone ID: %s; Status: %s). Please remember, apart from the page rules and premium APO feature, any Cloudflare settings applied via this page will be applied to all subdomains as well.', 'runcloud-hub'), '<strong>'.$domain.'</strong>', '<strong>'.$data['zoneid'].'</strong>', '<strong>'.$data['status'].'</strong>' );
                }
                else {
                    $data['message'] = sprintf( esc_html__('Cloudflare integration is enabled for subdomain %s (Zone ID: %s; Status: %s). Please remember, apart from the page rules and premium APO feature, any Cloudflare settings applied via this page will be applied to the main domain %s and other subdomains as well.', 'runcloud-hub'), '<strong>'.$hostname.'</strong>', '<strong>'.$data['zoneid'].'</strong>', '<strong>'.$data['status'].'</strong>', '<strong>'.$domain.'</strong>' );
                }
            }
            else {
                $domain = $hostname;
                $data['status'] = 'disabled';
                if ($results['success'] && !$results['error']) {
                    $data['message'] = sprintf( esc_html__('Cloudflare integration is disabled: This domain %s is not provisioned with Cloudflare under current API key/token or the status is not active on Cloudflare.', 'runcloud-hub'), '<strong>'.$domain.'</strong>' );
                }
                else {
                    $data['message'] = sprintf( esc_html__('Cloudflare integration is disabled: %s', 'runcloud-hub'), $results['status']);
                }
            }

            if (empty($data['message'])) {
                $data['message'] = sprintf( esc_html__('Cloudflare integration is disabled: %s', 'runcloud-hub'), esc_html__('Unknown error', 'runcloud-hub'));
            }
        }

        if (empty($data['status'])) {
            $data['status'] = 'disabled';
        }

        update_option(self::$db_cloudflare, $data);
        self::debug(__METHOD__, $data);
    }

    /**
     * cloudflare_get_settings.
     */
    private static function cloudflare_get_settings()
    {
        $settings = [
            'email'  => '',
            'apikey' => '',
            'zoneid' => '',
        ];

        $blog_id = '';
        if (is_multisite() && !self::is_main_site()) {
            $current_network = get_network();
            $network_domain = $current_network->domain;
            $home_url = home_url('/');
            if ( false !== strpos($home_url, $network_domain) ) {
                $blog_id = self::get_main_site_id();
            }
        }

        if ($blog_id) {
            switch_to_blog($blog_id);
        }

        $options = self::get_setting();
        if (!empty($options['cloudflare_email'])) {
            $settings['email'] = $options['cloudflare_email'];
        }
        if (!empty($options['cloudflare_apikey'])) {
            $settings['apikey'] = $options['cloudflare_apikey'];
        }

        $options = get_option(self::$db_cloudflare);
        if (!empty($options['zoneid'])) {
            $settings['zoneid'] = $options['zoneid'];
        }

        if ($blog_id) {
            restore_current_blog();
        }

        return $settings;
    }

    /**
     * cloudflare_can_enable.
     */
    private static function cloudflare_can_enable()
    {
        $can = false;
        if (!is_multisite()) {
            $can = self::get_setting('allow_cloudflare_onn');
        }
        else {
            $network_id = self::get_main_site_id();
            $can_network = self::get_setting('allow_cloudflare_onn', $network_id);
            if ( $can_network ) {
                if (self::is_main_site()) {
                    $can = true;
                }
                else {
                    $current_network = get_network();
                    $network_domain = $current_network->domain;
                    $home_url = self::home_url('/');
                    if ( false === strpos($home_url, $network_domain) ) {
                        $can = true;
                    }
                }
            }
        }
        return $can;
    }

    /**
     * cloudflare_is_enabled.
     */
    public static function cloudflare_is_enabled($action = '')
    {
        $enabled = false;
        $blog_id = '';

        if (!is_multisite()) {
            $enabled = self::get_setting('allow_cloudflare_onn');
        }
        else {
            $network_id = self::get_main_site_id();
            $enabled = self::get_setting('allow_cloudflare_onn', $network_id);
        }

        if (!$enabled) {
            return false;
        }

        $blog_id = '';
        if (is_multisite() && !self::is_main_site()) {
            $current_network = get_network();
            $network_domain = $current_network->domain;
            $home_url = self::home_url('/');
            if ( false !== strpos($home_url, $network_domain) ) {
                $blog_id = self::get_main_site_id();
            }
        }

        if ($blog_id) {
            switch_to_blog($blog_id);
        }

        $enabled = self::get_setting('cloudflare_onn');

        if ($enabled) {
            $options = get_option(self::$db_cloudflare);
            $enabled = !empty($options['zoneid']) ? true : false;
        }

        if ($enabled && !empty($_SERVER['SERVER_NAME'])) {
            $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);
            $enabled = !empty($options['hostname']) && $options['hostname'] !== $hostname ? false : true;
        }

        if ($enabled) {
            if ($action === 'purgeall') {
                $enabled = self::get_setting('cloudflare_purgeall_onn');
            }
            elseif ($action === 'purgeurl') {
                $enabled = self::get_setting('cloudflare_purgeurl_onn');
            }
        }

        if ($blog_id) {
            restore_current_blog();
        }

        return $enabled;
    }

    /**
     * check_cloudflare_cache.
     */
    private static function check_cloudflare_cache()
    {

        $request_url = self::home_url('/');

        $request_args = [
            'method'      => 'GET',
            'headers'     => [
                'Accept' => 'text/html'
            ],
            'user-agent'  => self::get_user_agent(),
            'body'        => null,
            'sslverify'   => false,
            'blocking'    => true,
            'timeout'     => 10,
            'redirection' => 5,
        ];

        wp_remote_request($request_url, $request_args);

        $response = wp_remote_request($request_url, $request_args);

        $results = [];
        if (is_wp_error($response)) {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $results['status'] = wp_strip_all_tags($response->get_error_message());
            $results['error']  = true;
        } else {
            $results['button_page'] = '#cloudflare';
            $response_headers  = wp_remote_retrieve_headers($response);
            if (isset($response_headers['CF-Cache-Status'])) {
                $results['code']   = wp_remote_retrieve_response_code($response);
                if( in_array( strtoupper($response_headers['CF-Cache-Status']), array('HIT', 'MISS', 'EXPIRED') ) ) {
                    if (isset($response_headers['cf-apo-via']) && $response_headers['cf-apo-via'] == 'tcache') {
                        $results['status'] = sprintf( esc_html__('Cloudflare Cache Status of your homepage is %s. Cloudflare page cache is active via APO in your website.', 'runcloud-hub'), $response_headers['CF-Cache-Status'] ); 
                        $results['button_action'] = 'disablecfapo';
                        $results['button_text'] = esc_html__('Disable Cloudflare APO', 'runcloud-hub');
                    }
                    else {
                        $results['status'] = sprintf( esc_html__('Cloudflare Cache Status of your homepage is %s. Cloudflare page cache is active via page rule in your website.', 'runcloud-hub'), $response_headers['CF-Cache-Status'] ); 
                        $results['button_action'] = 'disablecfcache';
                        $results['button_text'] = esc_html__('Disable Cloudflare Page Rule', 'runcloud-hub');
                    }
                }
                elseif( strcasecmp($response_headers['CF-Cache-Status'], 'BYPASS') == 0 ) {
                    $results['status'] = sprintf( esc_html__('Cloudflare Cache Status of your homepage is %s. This page is not cached by Cloudflare and served from the origin server.', 'runcloud-hub'), $response_headers['CF-Cache-Status'] ); 
                }
                elseif( strcasecmp($response_headers['CF-Cache-Status'], 'DYNAMIC') == 0 ) {
                    $results['status'] = sprintf( esc_html__('Cloudflare Cache Status of your homepage is %s. This page is not cached by Cloudflare. You can enable page caching by creating Cache Everything page rule (free) or APO (premium) in Cloudflare. If you have added the page rule, please wait about few minutes and then test again.', 'runcloud-hub'), $response_headers['CF-Cache-Status'] ); 
                    $results['button_action'] = 'enablecfcache';
                    $results['button_text'] = esc_html__('Create Cloudflare Page Rule', 'runcloud-hub');
                }
                else {
                    $results['status'] = sprintf( esc_html__('Cloudflare Cache Status of your homepage is %s.', 'runcloud-hub'), $response_headers['CF-Cache-Status'] ); 
                }
            }
            else {
                $results['code']   = 10;
                $results['status'] = esc_html__('Your website is currently not behind Cloudflare. You can check DNS record of this website in Cloudflare. If you have recently switched the DNS record from DNS Only to Proxied in Cloudflare, please wait about few minutes and then try again because the changes on Cloudflare could take some times to fully propagate on the web.', 'runcloud-hub'); 
            }
            if ( self::is_debug() ) {
                $results['headers'] = $response_headers;
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * enable_cloudflare_cache.
     */
    private static function enable_cloudflare_cache()
    {

        $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);

        $method = 'POST';
        $endpoint = 'zones/{{zoneid}}/pagerules';
        $args = array(
            'targets' => array(
                array(
                    'target' => 'url', 
                    'constraint' => array(
                        'operator' => 'matches', 
                        'value' => $hostname.'/*'
                    )
                )
            ), 
            'actions' => array(
                array(
                    'id' => 'cache_level', 
                    'value' => 'cache_everything'
                )
            ), 
            'priority' => 1, 
            'status' => 'active'
        );

        $results = self::request_cloudflare($method, $endpoint, $args);
        if ($results['code'] === 200) {
            if (!empty($results['result']['status']) && $results['result']['status'] == 'active') {
                $results['status'] = esc_html__('Cloudflare page cache has been enabled by creating CF page rule with Cache Level = Cache Everything. Please wait few minutes for changes to take effect from Cloudflare.', 'runcloud-hub'); 
            }
            else {
                $results['code'] = 10;
                $results['status'] = esc_html__('Failed to create Cloudflare page rule to enable CF page cache: Unknown result from Cloudflare API', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to create Cloudflare page rule to enable CF page cache: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to create Cloudflare page rule to enable CF page cache', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * disable_cloudflare_cache.
     */
    private static function disable_cloudflare_cache()
    {

        $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);

        $page_rule_targets = array(array('target' => 'url', 'constraint' => array('operator' => 'matches', 'value' => $hostname.'/*')));
        $page_rule_actions = array(array('id' => 'cache_level', 'value' => 'cache_everything'));

        // Handle page rules created manually or by other plugin
        $page_rule_targets_http = array(array('target' => 'url', 'constraint' => array('operator' => 'matches', 'value' => 'http://'.$hostname.'/*')));
        $page_rule_targets_https = array(array('target' => 'url', 'constraint' => array('operator' => 'matches', 'value' => 'https://'.$hostname.'/*')));

        $page_rules_found = false;
        $page_rule_ids = [];

        $method = 'GET';
        $endpoint = 'zones/{{zoneid}}/pagerules?status=active';
        $args = [];
        $results = self::request_cloudflare($method, $endpoint, $args);
        if ($results['code'] === 200) {
            if (isset($results['result'])) {
                $page_rules_found = true;
                $page_rules = $results['result'];
                if (!empty($page_rules)) {
                    foreach ($page_rules as $page_rule) {
                        if ($page_rule['targets'] == $page_rule_targets && $page_rule['actions'] == $page_rule_actions) {
                            $page_rule_ids[] = $page_rule['id'];
                        }
                        elseif ($page_rule['targets'] == $page_rule_targets_http && $page_rule['actions'] == $page_rule_actions) {
                            $page_rule_ids[] = $page_rule['id'];
                        }
                        elseif ($page_rule['targets'] == $page_rule_targets_https && $page_rule['actions'] == $page_rule_actions) {
                            $page_rule_ids[] = $page_rule['id'];
                        }
                    }
                }
            }
            else {
                $results['code'] = 10;
                $results['status'] = esc_html__('Failed to get Cloudflare page rules to disable CF page cache: Unknown result from Cloudflare API', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to get Cloudflare page rules to disable CF page cache: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to get Cloudflare page rules to disable CF page cache', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        if (!$page_rules_found) {
            return $results;
        }

        if ($page_rules_found && empty($page_rule_ids)) {
            $results['code'] = 10;
            $results['status'] = esc_html__('Failed to find the Cloudflare page rules to disable CF page cache. If you have created the page rules either manually or using other plugin, please remove the page rules from Cloudflare dashboard.', 'runcloud-hub'); 
            return $results;
        }

        foreach ($page_rule_ids as $page_rule_id) {
            $method = 'DELETE';
            $endpoint = 'zones/{{zoneid}}/pagerules/'.$page_rule_id;
            $args = [];
            $results = self::request_cloudflare($method, $endpoint, $args);
            if ($results['code'] === 200 && isset($results['success']) && $results['success']) {
                $results['status'] = esc_html__('Cloudflare page cache has been disabled by deleting the CF page rule. Please wait few minutes for changes to take effect from Cloudflare.', 'runcloud-hub'); 
            }
            else {
                if (!empty($results['status'])) {
                    $results['status'] = sprintf( esc_html__('Failed to delete Cloudflare page rules to disable CF page cache: %s', 'runcloud-hub'), $results['status']); 
                }
                else {
                    $results['status'] = esc_html__('Failed to delete Cloudflare page rules to disable CF page cache', 'runcloud-hub'); 
                }
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * check_cloudflare_dev_mode.
     */
    private static function check_cloudflare_dev_mode()
    {
        $method = 'GET';
        $endpoint = 'zones/{{zoneid}}/settings/development_mode';
        $args = [];
        $results = self::request_cloudflare($method, $endpoint, $args);
        $results['button_page'] = '#cloudflare';

        if ($results['code'] === 200) {
            if (!empty($results['result']['value'])) {
                if ($results['result']['value'] == 'on') {
                    $results['status'] = esc_html__('Cloudflare development mode is currently ON (active) on this website. This will bypass Cloudflare accelerated cache and slow down your site, but is useful if you are making changes to cacheable content (like images, css, or JavaScript) and would like to see those changes right away. Once entered, development mode will last for 3 hours and then automatically toggle off.', 'runcloud-hub');
                    $results['button_action'] = 'disablecfdev';
                    $results['button_text'] = esc_html__('Disable Cloudflare Development Mode', 'runcloud-hub');
                }
                elseif ($results['result']['value'] == 'off') {
                    $results['status'] = esc_html__('Cloudflare development mode is currently OFF (not active) on this website. Enable development mode to bypass Cloudflare accelerated cache. It is useful if you are making changes to cacheable content (like images, css, or JavaScript) and would like to see those changes right away.', 'runcloud-hub'); 
                    $results['button_action'] = 'enablecfdev';
                    $results['button_text'] = esc_html__('Enable Cloudflare Development Mode', 'runcloud-hub');
                }
                else {
                    $results['code'] = 10;
                    $results['status'] = esc_html__('Failed to get Cloudflare development mode status: Unknown result from Cloudflare API', 'runcloud-hub'); 
                }
            }
            else {
                $results['code'] = 10;
                $results['status'] = esc_html__('Failed to get Cloudflare development mode status: Unknown result from Cloudflare API', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to get Cloudflare development mode status: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to get Cloudflare development mode status', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * enable_cloudflare_dev_mode.
     */
    private static function enable_cloudflare_dev_mode()
    {
        $method = 'PATCH';
        $endpoint = 'zones/{{zoneid}}/settings/development_mode';
        $args = array('value' => 'on');
        $results = self::request_cloudflare($method, $endpoint, $args);
        if ($results['code'] === 200) {
            if (!empty($results['result']['value'])) {
                if ($results['result']['value'] == 'on') {
                    $results['status'] = esc_html__('Cloudflare development mode has been enabled on this website. This will bypass Cloudflare accelerated cache and slow down your site, but is useful if you are making changes to cacheable content (like images, css, or JavaScript) and would like to see those changes right away. Once entered, development mode will last for 3 hours and then automatically toggle off.', 'runcloud-hub');
                }
                elseif ($results['result']['value'] == 'off') {
                    $results['code'] = 10;
                    $results['status'] = esc_html__('Failed to enable Cloudflare development mode, please try again.', 'runcloud-hub'); 
                }
            }
            else {
                $results['code'] = 10;
                $results['status'] = esc_html__('Failed to enable Cloudflare development mode: Unknown result from Cloudflare API', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to enable Cloudflare development mode: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to enable Cloudflare development mode status', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * disable_cloudflare_dev_mode.
     */
    private static function disable_cloudflare_dev_mode()
    {
        $method = 'PATCH';
        $endpoint = 'zones/{{zoneid}}/settings/development_mode';
        $args = array('value' => 'off');
        $results = self::request_cloudflare($method, $endpoint, $args);
        if ($results['code'] === 200) {
            if (!empty($results['result']['value'])) {
                if ($results['result']['value'] == 'off') {
                    $results['status'] = esc_html__('Cloudflare development mode has been disabled on this website. Please wait few minutes for changes to take effect from Cloudflare.', 'runcloud-hub');
                }
                elseif ($results['result']['value'] == 'on') {
                    $results['code'] = 10;
                    $results['status'] = esc_html__('Failed to disable Cloudflare development mode, please try again.', 'runcloud-hub'); 
                }
            }
            else {
                $results['code'] = 10;
                $results['status'] = esc_html__('Failed to disable Cloudflare development mode: Unknown result from Cloudflare API', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to disable Cloudflare development mode: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to disable Cloudflare development mode status', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * get_cloudflare_apo.
     */
    private static function check_cloudflare_apo()
    {
        $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);

        $method = 'GET';
        $endpoint = 'zones/{{zoneid}}/settings/automatic_platform_optimization';
        $args = [];
        $results = self::request_cloudflare($method, $endpoint, $args);
        $results['button_page'] = '#cloudflare';

        if ($results['code'] === 200) {
            if (isset($results['result']['value']['enabled'])) {
                if ($results['result']['value']['enabled'] == true) {
                    $apo_hostnames = $results['result']['value']['hostnames'];
                    if (!empty($apo_hostnames)) {
                        if (!empty($hostname) && in_array($hostname, $apo_hostnames)) {
                            $results['status'] = sprintf( esc_html__('Cloudflare APO is currently ON (active) on this website. It is also active on other websites, including %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                            $results['button_action'] = 'disablecfapo';
                            $results['button_text'] = esc_html__('Disable Cloudflare APO', 'runcloud-hub');
                        }
                        else {
                            $results['status'] = sprintf( esc_html__('Cloudflare APO is currently OFF (not active) on this website. It is active on %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                            $results['button_action'] = 'enablecfapo';
                            $results['button_text'] = esc_html__('Enable Cloudflare APO', 'runcloud-hub');
                        }
                    }
                    else {
                        $results['status'] = esc_html__('Cloudflare APO is currently OFF (not active) on this website. APO (Automatic Platform Optimization) is a premium Cloudflare feature that will serves your WordPress site from Cloudflare\' edge network and caches third party fonts.', 'runcloud-hub'); 
                        $results['button_action'] = 'enablecfapo';
                        $results['button_text'] = esc_html__('Enable Cloudflare APO', 'runcloud-hub');
                    }
                }
                else {
                    $results['status'] = esc_html__('Cloudflare APO is currently OFF (not active) on this website. APO (Automatic Platform Optimization) is a premium Cloudflare feature that will serves your WordPress site from Cloudflare\' edge network and caches third party fonts.', 'runcloud-hub'); 
                    $results['button_action'] = 'enablecfapo';
                    $results['button_text'] = esc_html__('Enable Cloudflare APO', 'runcloud-hub');
                }
            }
            else {
                if (isset($results['result']['value']) && (empty($results['result']['value']) || $results['result']['value'] == 'off')) {
                    $results['status'] = esc_html__('Cloudflare APO is currently OFF (not active) on this website. APO (Automatic Platform Optimization) is a premium Cloudflare feature that will serves your WordPress site from Cloudflare\' edge network and caches third party fonts.', 'runcloud-hub'); 
                    $results['button_action'] = 'enablecfapo';
                    $results['button_text'] = esc_html__('Enable Cloudflare APO', 'runcloud-hub');
                }
                else {
                    $results['code'] = 10;
                    $results['status'] = esc_html__('Failed to get Cloudflare APO status: Unknown result from Cloudflare API', 'runcloud-hub'); 
                }
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to get Cloudflare APO status: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to get Cloudflare APO status', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * enable_cloudflare_apo.
     */
    private static function enable_cloudflare_apo()
    {
        $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);
            
        if (empty($hostname)) {
            $results = array(
                'status' => sprintf( esc_html__('Failed to enable Cloudflare APO: %s', 'runcloud-hub'), esc_html__('empty hostname', 'runcloud-hub')),
                'code'   => 10,
            );

            self::debug(__METHOD__, $results);
            return $results;
        }

        $request_url = self::home_url('/');

        $request_args = [
            'method'      => 'GET',
            'headers'     => [
                'Accept' => 'text/html'
            ],
            'user-agent'  => self::get_user_agent(),
            'body'        => null,
            'sslverify'   => false,
            'blocking'    => true,
            'timeout'     => 10,
            'redirection' => 5,
        ];

        $response = wp_remote_request($request_url, $request_args);

        $results = [];
        if (is_wp_error($response)) {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $results['status'] = sprintf( esc_html__('Failed to check if this website is behind Cloudflare to enable APO: %s', 'runcloud-hub'), wp_strip_all_tags($response->get_error_message()));
            $results['error']  = true;

            self::debug(__METHOD__, $results);
            return $results;
        } else {
            $response_headers  = wp_remote_retrieve_headers($response);
            if (!isset($response_headers['CF-Cache-Status'])) {
                $results['code']   = 10;
                $results['status'] = esc_html__('Failed to enable Cloudflare APO because your website is currently not behind Cloudflare. If you have recently switched the DNS record from DNS Only to Proxied in Cloudflare, please wait about few minutes and then try again because the changes on Cloudflare could take some times to fully propagate on the web.', 'runcloud-hub');
    
                self::debug(__METHOD__, $results);
                return $results;
            }
        }

        $apo_enabled = false;
        $apo_hostnames = [];
        $apo_cache_by_device_type = false;

        $method = 'GET';
        $endpoint = 'zones/{{zoneid}}/settings/automatic_platform_optimization';
        $args = [];
        $results = self::request_cloudflare($method, $endpoint, $args);

        if ($results['code'] === 200) {
            if (isset($results['result']['value']['enabled'])) {
                $apo_enabled = $results['result']['value']['enabled'];
                $apo_hostnames = $results['result']['value']['hostnames'];
                $apo_cache_by_device_type = $results['result']['value']['cache_by_device_type'];
                if ($apo_enabled === true && in_array($hostname, $apo_hostnames)) {
                    $results['status'] = esc_html__('Cloudflare APO is currently enabled on this domain zone. No action needed.', 'runcloud-hub');
                    self::debug(__METHOD__, $results);
                    return $results;
                }
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to get Cloudflare APO status to enable APO: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to get Cloudflare APO status to enable APO', 'runcloud-hub'); 
            }
            self::debug(__METHOD__, $results);
            return $results;
        }

        if (!is_array($apo_hostnames)) {
            $apo_hostnames = [];
        }
        $apo_hostnames[] = $hostname;

        $method = 'PATCH';
        $endpoint = 'zones/{{zoneid}}/settings/automatic_platform_optimization';
        $args = array(
            'value' => array(
                'enabled' => true,
                'cf' => true,
                'wordpress' => true,
                'wp_plugin' => true,
                'hostnames' => $apo_hostnames,
                'cache_by_device_type' => $apo_cache_by_device_type,
            )
        );

        $results = self::request_cloudflare($method, $endpoint, $args);

        if ($results['code'] === 200 && isset($results['result']['value']['hostnames'])) {
            $apo_hostnames = $results['result']['value']['hostnames'];
            if (!empty($apo_hostnames)) {
                if (in_array($hostname, $apo_hostnames)) {
                    if (count($apo_hostnames) == 1) {
                        $results['status'] = esc_html__('Cloudflare APO has been enabled successfully from this website. Please wait few minutes for changes to take effect from Cloudflare.', 'runcloud-hub');
                    }
                    else {
                        $results['status'] = sprintf( esc_html__('Cloudflare APO has been enabled successfully from this website. Please wait few minutes for changes to take effect from Cloudflare. Note: Cloudflare APO is also active on %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                    }
                }
                else {
                    $results['code'] = 10;
                    $results['status'] = sprintf( esc_html__('Failed to enable Cloudflare APO for unknown reason. Cloudflare APO is still active on %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                }
            }
            else {
                $results['status'] = esc_html__('Failed to enable Cloudflare APO for unknown reason.', 'runcloud-hub'); 
            }
            self::disable_cloudflare_cache();
            self::purge_cloudflare_cache();
        }
        else {
            if (!empty($results['status'])) {
                if (strpos($results['status'], 'not_entitled') !== false) {
                    $results['status'] = sprintf( esc_html__('Failed to enable Cloudflare APO: %s', 'runcloud-hub'), esc_html__('No active Cloudflare APO subscription on this domain.', 'runcloud-hub').' '.$results['status']);
                    $options = get_option(self::$db_cloudflare);
                    if (!empty($options['name']) && !empty($options['account_id'])) {
                        $results['button_url'] = 'https://dash.cloudflare.com/'.$options['account_id'].'/'.$options['name'].'/speed/optimization/apo/purchase'; 
                        $results['button_text'] = esc_html__('Purchase Cloudflare APO', 'runcloud-hub'); 
                        $results['button_target'] = '_blank'; 
                    }
                }
                else {
                    $results['status'] = sprintf( esc_html__('Failed to enable Cloudflare APO: %s', 'runcloud-hub'), $results['status']); 
                }
            }
            else {
                $results['status'] = esc_html__('Failed to enable Cloudflare APO', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * disable_cloudflare_apo.
     */
    private static function disable_cloudflare_apo()
    {
        $hostname = sanitize_text_field($_SERVER['SERVER_NAME']);

        if (empty($hostname)) {
            $results = array(
                'status' => sprintf( esc_html__('Failed to disable Cloudflare APO: %s', 'runcloud-hub'), esc_html__('empty hostname', 'runcloud-hub')),
                'code'   => 10,
            );

            self::debug(__METHOD__, $results);
            return $results;
        }

        $apo_enabled = false;
        $apo_hostnames = [];
        $apo_cache_by_device_type = false;

        $method = 'GET';
        $endpoint = 'zones/{{zoneid}}/settings/automatic_platform_optimization';
        $args = [];
        $results = self::request_cloudflare($method, $endpoint, $args);

        if ($results['code'] === 200) {
            if (isset($results['result']['value']['enabled'])) {
                $apo_enabled = $results['result']['value']['enabled'];
                $apo_hostnames = $results['result']['value']['hostnames'];
                $apo_cache_by_device_type = $results['result']['value']['cache_by_device_type'];
                if ($apo_enabled === false) {
                    $results['status'] = esc_html__('Cloudflare APO is currently disabled on this domain zone. No action needed.', 'runcloud-hub');
                    self::debug(__METHOD__, $results);
                    return $results;
                }
                if (empty($hostname) || !in_array($hostname, $apo_hostnames)) {
                    $results['status'] = esc_html__('Cloudflare APO is currently disabled on this website. No action needed.', 'runcloud-hub');
                    self::debug(__METHOD__, $results);
                    return $results;
                }
            }
            else {
                $results['status'] = esc_html__('Cloudflare APO is currently not available on this domain zone. No action needed.', 'runcloud-hub');
                self::debug(__METHOD__, $results);
                return $results;
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to get Cloudflare APO status to disable APO: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to get Cloudflare APO status to disable APO', 'runcloud-hub'); 
            }
            self::debug(__METHOD__, $results);
            return $results;
        }

        if (($hostname_key = array_search($hostname, $apo_hostnames)) !== false) {
            array_splice($apo_hostnames, $hostname_key, 1);
        }

        $method = 'PATCH';
        $endpoint = 'zones/{{zoneid}}/settings/automatic_platform_optimization';
        $args = array(
            'value' => array(
                'enabled' => true,
                'cf' => true,
                'wordpress' => true,
                'wp_plugin' => true,
                'hostnames' => $apo_hostnames,
                'cache_by_device_type' => $apo_cache_by_device_type,
            )
        );

        $results = self::request_cloudflare($method, $endpoint, $args);

        if ($results['code'] === 200 && isset($results['result']['value']['hostnames'])) {
            $apo_hostnames = $results['result']['value']['hostnames'];
            if (empty($apo_hostnames)) {
                $results['status'] = esc_html__('Cloudflare APO has been disabled successfully from this website. Please wait few minutes for changes to take effect from Cloudflare.', 'runcloud-hub'); 
            }
            else {
                if (!in_array($hostname, $apo_hostnames)) {
                    $results['status'] = sprintf( esc_html__('Cloudflare APO has been disabled successfully from this website. Please wait few minutes for changes to take effect from Cloudflare. Note: Cloudflare APO is still active on %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                }
                else {
                    $results['code'] = 10;
                    $results['status'] = sprintf( esc_html__('Failed to disable Cloudflare APO for unknown reason. Cloudflare APO is still active on %s', 'runcloud-hub'), '<strong>'.implode(', ', $apo_hostnames).'</strong>');
                }
            }
            self::disable_cloudflare_cache();
            self::purge_cloudflare_cache();
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Failed to disable Cloudflare APO: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Failed to disable Cloudflare APO', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * purge_cloudflare_cache.
     */
    private static function purge_cloudflare_cache( $urls = [], $args = [] )
    {

        // set default blocking to be true
        $blocking = isset($args['blocking_cf']) ? (bool)$args['blocking_cf'] : true;

        $method = 'POST';
        $endpoint = 'zones/{{zoneid}}/purge_cache';
        if (empty($urls)) {
            $purge_args = [
                'purge_everything' => true,
                'blocking_cf' => $blocking,
            ];
        }
        else {
            $purge_args = [
                'files' => $urls,
                'blocking_cf' => $blocking,
            ];
        }
        $results = self::request_cloudflare($method, $endpoint, $purge_args);
        if ($results['code'] === 200) {
            if (empty($urls)) {
                $results['status'] = esc_html__('Purge all Cloudflare cache was successful. Please allow up to 30 seconds for changes to take effect from Cloudflare.', 'runcloud-hub'); 
            }
            else {
                $results['status'] = esc_html__('Purge Cloudflare cache was successful. Please allow up to 30 seconds for changes to take effect from Cloudflare.', 'runcloud-hub'); 
            }
        }
        else {
            if (!empty($results['status'])) {
                $results['status'] = sprintf( esc_html__('Purge Cloudflare cache failed: %s', 'runcloud-hub'), $results['status']); 
            }
            else {
                $results['status'] = esc_html__('Purge Cloudflare cache failed', 'runcloud-hub'); 
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * request_cloudflare.
     */
    public static function request_cloudflare( $method = 'POST', $endpoint = '', $args = [] )
    {
        $results = [
            'error'    => false,
            'code'     => '',
            'status'   => '',
            'method'   => $method,
            'args'     => $args,
            'endpoint' => $endpoint,
            'success'  => false,
            'reponse'  => '',
            'result'   => '',
        ];

        if (empty($endpoint)) {
            $results['status'] = esc_html__('No endpoint defined', 'runcloud-hub'); 
            return $results;
        }

        $request_url = 'https://api.cloudflare.com/client/v4/'.$endpoint;

        $options = self::cloudflare_get_settings();
        if (empty($options['apikey'])) {
            $results['status'] = esc_html__('Cloudflare API key/token missing', 'runcloud-hub'); 
            return $results;
        }

        if (strlen(trim($options['apikey'])) <= 37 && preg_match('/^[0-9a-f]+$/', trim($options['apikey']))) {
            if (empty($options['email'])) {
                $results['status'] = esc_html__('Cloudflare email missing', 'runcloud-hub'); 
                return $results;
            }

            $request_headers = array(
                'X-Auth-Email' => trim($options['email']),
                'X-Auth-Key' => trim($options['apikey']),
                'Content-Type' => 'application/json',
            );
        }
        else {
            $request_headers = array(
                'Authorization' => 'Bearer '.trim($options['apikey']),
                'Content-Type' => 'application/json',
            );
        } 

        if (strpos($endpoint, '{{zoneid}}') !== false) {
            if (empty($options['apikey'])) {
                $results['status'] = esc_html__('Cloudflare Zone ID missing', 'runcloud-hub'); 
                return $results;
            }
            else {
                $request_url = str_replace('{{zoneid}}', trim($options['zoneid']), $request_url);
            }
        }

        self::debug(__METHOD__, esc_attr($_SERVER['REQUEST_URI']));

        if (isset($args['blocking_cf'])) {
            $blocking = $args['blocking_cf'];
            unset($args['blocking_cf']);
        } else {
            // set default blocking to be true
            $blocking = true;
        }

        $request_args = [
            'method'      => $method,
            'headers'     => $request_headers,
            'user-agent'  => self::get_user_agent(),
            'body'        => !empty($args) ? json_encode($args) : null,
            'sslverify'   => false,
            'blocking'    => $blocking,
            'timeout'     => 10,
            'redirection' => 5,
        ];

        $response = wp_remote_request($request_url, $request_args);

        if (is_wp_error($response)) {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $results['status'] = wp_strip_all_tags($response->get_error_message());
            $results['error']  = true;
        } else {
            $results['code']   = wp_remote_retrieve_response_code($response);
            $response_body     = wp_remote_retrieve_body($response);
            $response_data     = json_decode($response_body, true);
            if (isset($response_data['success'])) {
                $results['success'] = $response_data['success'];
                if ($response_data['success']) {
                    if (!empty($response_data['result'])) {
                        $results['result'] = $response_data['result'];
                    }
                }
                else {
                    $results['code'] = 10;
                    $errors = [];
                    foreach ($response_data['errors'] as $error) {
                        $error_message = '';
                        if (!empty($error['message'])) {
                            $error_message .= $error['message'];
                        } 
                        if (!empty($error['code'])) {
                            $error_message .= ' ('.$error['code'].')';
                        } 
                        if (!empty($error_message)) {
                            $errors[] = $error_message;
                        } 
                    }
                    if (!empty($errors)) {
                        $results['status'] = implode(', ', $errors);
                    }
                    if (strpos($results['status'], 'See messages for details') !== false) {
                        $errors = [];
                        foreach ($response_data['messages'] as $error) {
                            $error_message = '';
                            if (!empty($error['message'])) {
                                $error_message .= $error['message'];
                            } 
                            if (!empty($error['code'])) {
                                $error_message .= ' ('.$error['code'].')';
                            } 
                            if (!empty($error_message)) {
                                $errors[] = $error_message;
                            } 
                        }
                        if (!empty($errors)) {
                            $results['status'] = implode(', ', $errors);
                        }
                    }
                }
            }
            if ( self::is_debug() ) {
                $results['response'] = $response_data;
            }
        }

        self::debug(__METHOD__, $results);

        return $results;
    }

    /**
     * get_hash.
     */
    public static function get_hash($string, $len = 12)
    {
        $str = @md5($string);
        if ( strlen($str) > $len ) {
            return @substr($str, 0, $len);
        }
        return $str;
    }

    /**
     * is_debug.
     */
    private static function is_debug()
    {
        return (defined('RUNCLOUD_HUB_DEBUG') && RUNCLOUD_HUB_DEBUG);
    }

    /**
     * debug.
     */
    private static function debug($caller, $data)
    {
        if (!self::is_debug()) {
            return false;
        }

        $log = [
            'timestamp' => gmdate('Y-m-d H:i:s') . ' UTC',
            'caller'    => $caller,
        ];

        if (!empty($data) && is_array($data)) {
            $log = self::array_merge_r($log, $data);
        } else {
            $log['status'] = $data;
        }

        return self::debug_log($log);
    }

    /**
     * array_export.
     */
    public static function array_export($data)
    {
        $data_e = var_export($data, true);
        $data_e = str_replace('Requests_Utility_CaseInsensitiveDictionary::__set_state(', '', $data_e);

        $data_e = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $data_e);
        $data_r = preg_split("/\r\n|\n|\r/", $data_e);

        $data_r = preg_replace(['/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/'], [null, ']$1', ' => ['], $data_r);
        return join(PHP_EOL, array_filter(['['] + $data_r));
    }

    /**
     * debug_log.
     */
    private static function debug_log($data, $filesave = '')
    {
        if (empty($filesave)) {
            $fname    = str_replace(' ', '_', self::$slug);
            $filesave = WP_CONTENT_DIR . '/' . $fname . '.log';
        }

        if (is_dir($filesave)) {
            return false;
        }

        if (!empty($data) && (is_array($data) || is_object($data))) {
            $data = str_replace('\\u0000', '', str_replace('\\u0000*', '', json_encode($data)));
            $data = json_decode($data, true);
            if (isset($data['header']['data'])) {
                $h              = $data['header']['data'];
                $data['header'] = $h;
                unset($h);
            }
        }

        if (!empty($data) && is_array($data)) {
            $data = str_replace('\\u0000', '', str_replace('\\u0000*', '', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))) . ',';
        } else {
            $data = trim($data);
        }

        $output = $data . PHP_EOL;

        $perm = self::get_fileperms('file');
        $is_file_exists = file_exists($filesave);

        if ( $is_file_exists ) {
            @chmod($filesave, $perm);
        }

        if (@file_put_contents($filesave, $output, FILE_APPEND|LOCK_EX)) {
            if ( !$is_file_exists ) {
                @chmod($filesave, $perm);
            }
            return true;
        }

        return false;
    }

    /**
     * wpcli_nginx_purge_all.
     */
    public static function wpcli_nginx_purge_all($type = null, $host_url = null)
    {
        $res = [];

        if ( $host_url ) {
            // reset
            $proto    = parse_url($host_url, PHP_URL_SCHEME);
            $hostname = parse_url($host_url, PHP_URL_HOST);

            // maybe port 443 is open. Then check if we should use the https proto instead
            if (self::is_wp_ssl() && 'https' !== $proto && @fsockopen('tls://' . $hostname, 443)) {
                $proto = 'https';
            }
        }
        else {
            $proto = null;
        }

        $return = [
            'code' => '',
            'status' => '',
        ];

        if (self::is_srcache()) {
            self::$done_purgeall = false;
            $return['purge_srcache'] = self::nginx_purge_all('srcache', $proto);
            $return['code'] = $return['purge_srcache']['code'];
            $return['status'] = '[Redis SRcache] '.$return['purge_srcache']['status'].'. ';
        }
        else {
            self::$done_purgeall = false;
            $return['purge_fastcgi'] = self::nginx_purge_all('fastcgi', $proto);
            $return['code'] = $return['purge_fastcgi']['code'];
            $return['status'] = '[FastCGI] '.$return['purge_fastcgi']['status'].'. ';

            if (!self::is_containerized()) {
                self::$done_purgeall = false;
                $return['purge_proxy'] = self::nginx_purge_all('proxy', $proto);
                if ( isset($return['purge_proxy']['code']) && $return['purge_proxy']['code'] !== 200 ) {
                    $return['code'] = $return['purge_proxy']['code'];
                }
                $return['status'] .= '[Proxy] '.$return['purge_proxy']['status'].'. ';
            }
        }

        if (self::cloudflare_is_enabled('purgeall')) {
            $return['purge_cloudflare'] = self::purge_cloudflare_cache();
        }

        return $return;
    }

    /**
     * wpcli_nginx_purge_all_sites.
     */
    public static function wpcli_nginx_purge_all_sites($type = null, $host_url = null)
    {
        $res = [];

        if ( $host_url ) {
            // reset
            $proto    = parse_url($host_url, PHP_URL_SCHEME);
            $hostname = parse_url($host_url, PHP_URL_HOST);

            // maybe port 443 is open. Then check if we should use the https proto instead
            if (self::is_wp_ssl() && 'https' !== $proto && @fsockopen('tls://' . $hostname, 443)) {
                $proto = 'https';
            }
        }
        else {
            $proto = null;
        }

        $return = [
            'code' => '',
            'status' => '',
        ];

        if (self::is_srcache()) {
            self::$done_purgesiteall = false;
            $return['purge_srcache'] = self::nginx_purge_all_sites('srcache', $proto);
            $return['code'] = $return['purge_srcache']['code'];
            $return['status'] = '[Redis SRcache] '.$return['purge_srcache']['status'].'. ';
        }
        else {
            self::$done_purgesiteall = false;
            $return['purge_fastcgi'] = self::nginx_purge_all_sites('fastcgi', $proto);
            $return['code'] = $return['purge_fastcgi']['code'];
            $return['status'] = '[FastCGI] '.$return['purge_fastcgi']['status'].'. ';

            if (!self::is_containerized()) {
                self::$done_purgesiteall = false;
                $return['purge_proxy'] = self::nginx_purge_all_sites('proxy', $proto);
                if ( isset($return['purge_proxy']['code']) && $return['purge_proxy']['code'] !== 200 ) {
                    $return['code'] = $return['purge_proxy']['code'];
                }
                $return['status'] .= '[Proxy] '.$return['purge_proxy']['status'].'. ';
            }
        }

        if (self::cloudflare_is_enabled('purgeall')) {
            $return['purge_cloudflare'] = self::purge_cloudflare_cache();
        }

        return $return;
    }

    /**
     * rcapi_endpoint.
     */
    private static function rcapi_endpoint()
    {
        static $lists;

        if (is_object($lists)) {
            return $lists;
        }

        $options         = self::get_setting();
        $rcapi_key       = $options['rcapi_key'];
        $rcapi_secret    = $options['rcapi_secret'];
        $rcapi_webapp_id = $options['rcapi_webapp_id'];

        // get key/secret from main site
        if (is_multisite() && !self::is_main_site()) {
            switch_to_blog(self::get_main_site_id());
            $options_site = self::get_setting();
            $rcapi_key    = $options_site['rcapi_key'];
            $rcapi_secret = $options_site['rcapi_secret'];
            restore_current_blog();
            unset($options_site);
        }

        $rcapi_url = (defined('RUNCLOUD_HUB_ENDPOINT') ? RUNCLOUD_HUB_ENDPOINT : self::$endpoint);

        $lists = [
            'url'       => $rcapi_url,
            'auth'      => base64_encode($rcapi_key . ':' . $rcapi_secret),
            'webapp_id' => $rcapi_webapp_id,
        ];

        $lists = (object) $lists;
        return $lists;
    }

    /**
     * rcapi_fetch.
     */
    public static function rcapi_fetch($url)
    {
        $args = [
            'timeout'     => 10,
            'httpversion' => '1.1',
            'user-agent'  => self::get_user_agent(),
            'blocking'    => true,
            'headers'     => ['Authorization' => 'Basic ' . self::rcapi_endpoint()->auth],
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null,
        ];

        $results = [
            'code'   => '',
            'status' => '',
            'host'   => parse_url($url, PHP_URL_HOST),
            'url'    => $url,
            'method' => 'GET',
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $results['code']    = wp_remote_retrieve_response_code($response);
            $results['status']  = sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), wp_strip_all_tags($response->get_error_message()));
            $results['content'] = '';
        } else {
            $results['code']    = wp_remote_retrieve_response_code($response);
            if ($results['code'] == 200) {
                $results['status']  = esc_html__('Fetching server stats was successful', 'runcloud-hub');
                $results['content'] = $response['body'];
            }
            else {
                $error = 'Unknown';
                $content = json_decode($response['body'], true);
                if (isset($content['message']) && $content['message']) {
                    $error = $content['message'];
                }
                $results['status']  = sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), $error);
            }
            $results['header']  = (is_object($response['headers']) ? (array) $response['headers'] : null);
        }

        self::debug(__METHOD__, $results);
        return $results;
    }

    /**
     * rcapi_fetch_stats.
     */
    public static function rcapi_fetch_stats($update = false, $status = false)
    {
        $data = [];

        self::debug(__METHOD__, 'event run');

        // only can fetch stats from main site if multisite
        if (!self::is_main_site() || self::is_subdirectory()) {
            $message = sprintf(esc_html__('Only can fetch stats from main site. Current: %s', 'runcloud-hub'), get_site_url());
            self::debug(__METHOD__, $message);
            if ($status) {
                return array(
                    'code' => 0,
                    'status' => sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), $message),
                );
            }
            return $data;
        }

        if (!$update) {
            if (get_option(self::$db_stats)) {
                $message = esc_html__('Stats is not empty, no need to fetch new stats data', 'runcloud-hub');
                self::debug(__METHOD__, $message);
                if ($status) {
                    return array(
                        'code' => 200,
                        'status' => sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), $message),
                    );
                }
                return $data;
            }
        }

        $options = self::get_setting();
        if (empty($options['rcapi_key']) || empty($options['rcapi_secret']) || empty($options['rcapi_webapp_id'])) {
            $message = esc_html__('Missing API Key / Secret / WebApp ID', 'runcloud-hub');
            self::debug(__METHOD__, $message);
            if ($status) {
                return array(
                    'code' => 0,
                    'status' => sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), $message),
                );
            }
            return $data;
        }

        $ep = self::rcapi_endpoint();

        $success = false;
        if (!empty($ep->webapp_id) && false === strpos($ep->url, '{{PANELURL}}')) {
            $type  = in_array($options['stats_health_var'], array('daily','hourly')) ? $options['stats_health_var'] : 'hourly';
            $url   = $ep->url . '/webapps/' . $ep->webapp_id . '/server/health/periodic?key=' . $type;
            $fetch = self::rcapi_fetch($url);
            if (!empty($fetch['code']) && $fetch['code'] == 200 && !empty($fetch['content'])) {
                $data['health'][$type] = json_decode($fetch['content'], true);
                $success = true;
            }

            $type  = in_array($options['stats_transfer_var'], array('daily','monthly')) ? $options['stats_transfer_var'] : 'daily';
            $url   = $ep->url . '/webapps/' . $ep->webapp_id . '/stats/transfer?key=' . $type;
            $fetch = self::rcapi_fetch($url);
            if (!empty($fetch['code']) && $fetch['code'] == 200 && !empty($fetch['content'])) {
                $data['transfer'][$type] = json_decode($fetch['content'], true);
                $success = true;
            }

            if ($success) {
                $data['lastupdate'] = gmdate('Y-m-d H:i:s') . ' UTC';
                update_option(self::$db_stats, $data);
            }
        }
        else {
            if ($status) {
                $message = esc_html__('Endpoint not configured properly', 'runcloud-hub');
                return array(
                    'code' => 0,
                    'status' => sprintf(esc_html__('Fetching server stats failed - %s', 'runcloud-hub'), $message),
                );
            }
        }

        if ($status) {
            return array(
                'code' => $fetch['code'],
                'status' => $fetch['status'],
            );
        }

        return $data;
    }

    /**
     * rcapi_get_stats.
     */
    public static function rcapi_get_stats()
    {
        $data = get_option(self::$db_stats);
        if (!empty($data) && is_array($data)) {
            return $data;
        }
        return [];
    }

    /**
     * rcapi_push.
     */
    public static function rcapi_push($status = false)
    {

        // only update from main site if multisite
        if (!self::is_main_site() || self::is_subdirectory()) {
            $message = sprintf( esc_html__('Only can update from main site. Current: %s', 'runcloud-hub'), get_site_url() );
            self::debug(__METHOD__, $message);
            $req_status = array(
                'code' => 0,
                'status' => sprintf(esc_html__('Updating cache rules to the server failed - %s', 'runcloud-hub'), $message),
            );
            if ($status) {
                return $req_status;
            } else {
                update_option(self::$db_notice, $req_status);
                return false;
            }
        }

        $options = self::get_setting();

        if (empty($options['rcapi_key']) || empty($options['rcapi_secret']) || empty($options['rcapi_webapp_id'])) {
            $message = esc_html__('Missing API Key / Secret / WebApp ID', 'runcloud-hub');
            self::debug(__METHOD__, $message);
            $req_status = array(
                'code' => 0,
                'status' => sprintf(esc_html__('Updating cache rules to the server failed - %s', 'runcloud-hub'), $message),
            );
            if ($status) {
                return $req_status;
            } else {
                update_option(self::$db_notice, $req_status);
                return false;
            }
        }

        if (!empty($options) && is_array($options)) {
            $data = [];

            if ( empty($options['exclude_url_onn']) || empty( $options['exclude_url_mch'] ) ) {
                $options['exclude_url_mch'] = [];
            }

            if ( !in_array('/.well-known.*', $options['exclude_url_mch'] ) ) {
                $options['exclude_url_mch'][] = '/.well-known.*';
            }
            if ( !in_array('/wp-admin/', $options['exclude_url_mch'] ) ) {
                $options['exclude_url_mch'][] = '/wp-admin/';
            }
            if ( !in_array('wp-.*.php', $options['exclude_url_mch'] ) ) {
                $options['exclude_url_mch'][] = 'wp-.*.php';
            }

            $options['exclude_url_mch'] = apply_filters( 'runcloud_cache_exclude_url', $options['exclude_url_mch'] );

            $data['exclude']['uri']        = implode("\n", $options['exclude_url_mch']);

            if ( empty($options['exclude_cookie_onn']) || empty( $options['exclude_cookie_mch'] ) ) {
                $options['exclude_cookie_mch'] = [];
            }

            if ( !in_array('wordpress_no_cache', $options['exclude_cookie_mch'] ) ) {
                $options['exclude_cookie_mch'][] = 'wordpress_no_cache';
            }

            $options['exclude_cookie_mch'] = apply_filters( 'runcloud_cache_exclude_cookie', $options['exclude_cookie_mch'] );

            $data['exclude']['cookie']     = implode("\n", $options['exclude_cookie_mch']);

            $data['exclude']['browser']    = (!empty($options['exclude_browser_onn']) && !empty($options['exclude_browser_mch']) ? implode("\n", $options['exclude_browser_mch']) : '');
            $data['exclude']['visitor_ip'] = (!empty($options['exclude_visitorip_onn']) && !empty($options['exclude_visitorip_mch']) ? implode("\n", $options['exclude_visitorip_mch']) : '');

            $data['query_string']['ignore'] = (!empty($options['ignore_query_onn']) && !empty($options['ignore_query_mch']) ? implode("\n", $options['ignore_query_mch']) : '');
            $data['query_string']['include'] = (!empty($options['allow_query_onn']) && !empty($options['allow_query_mch']) ? implode("\n", $options['allow_query_mch']) : '');
            $data['query_string']['exclude'] = (!empty($options['exclude_query_onn']) && !empty($options['exclude_query_mch']) ? implode("\n", $options['exclude_query_mch']) : '');

            $data['cache_key']['extra'] = (!empty($options['cache_key_extra_onn']) && !empty($options['cache_key_extra_var']) ? trim($options['cache_key_extra_var']) : '');

            $ep = self::rcapi_endpoint();

            if (!empty($ep->webapp_id) && false === strpos($ep->url, '{{PANELURL}}')) {
                $url = $ep->url . '/webapps/' . $ep->webapp_id . '/wordpress/runcloud-hub/runcache-exclusion';

                $args = [
                    'timeout'     => 10,
                    'httpversion' => '1.1',
                    'user-agent'  => self::get_user_agent(),
                    'blocking'    => true,
                    'headers'     => ['Authorization' => 'Basic ' . self::rcapi_endpoint()->auth],
                    'body'        => $data,
                    'compress'    => false,
                    'decompress'  => true,
                    'sslverify'   => false,
                    'stream'      => false,
                    'filename'    => null,
                ];

                $results = [
                    'code'   => '',
                    'status' => '',
                    'host'   => parse_url($url, PHP_URL_HOST),
                    'url'    => $url,
                    'method' => 'POST',
                    'error'  => '',
                    'param'  => [
                        'Authorization' => $args['headers']['Authorization'],
                        'data'          => $args['body'],
                    ],
                ];

                $response = wp_remote_post($url, $args);
                if (is_wp_error($response)) {
                    $results['code']   = wp_remote_retrieve_response_code($response);
                    $results['status'] = wp_remote_retrieve_response_message($response);
                    $results['error']  = wp_strip_all_tags($response->get_error_message());
                } else {
                    $results['code']   = wp_remote_retrieve_response_code($response);
                    $results['status'] = wp_remote_retrieve_response_message($response);
                    $results['header'] = (is_object($response['headers']) ? (array) $response['headers'] : null);
                    if ($results['code'] !== 200) {
                        $response_body = wp_remote_retrieve_body($response);
                        $response_body = json_decode($response_body);
                        $results['error'] = !empty($response_body->message) ? $response_body->message : $results['status'];
                    }
                }

                $message = $results;
                self::debug(__METHOD__, $message);
                if (empty($results['error']) && 200 === $results['code']) {
                    $req_status = array(
                        'code' => $results['code'],
                        'status' => esc_html__('Updating cache rules to the server was successful. It will be applied to the server in a few moments.', 'runcloud-hub'),
                    );
                    if ($status) {
                        return $req_status;
                    } else {
                        update_option(self::$db_notice, $req_status);
                        return false;
                    }
                }
                else {
                    $req_status = array(
                        'code' => $results['code'],
                        'status' => sprintf(esc_html__('Updating cache rules to the server failed - %s', 'runcloud-hub'), $message['error']),
                    );
                    if ($status) {
                        return $req_status;
                    } else {
                        update_option(self::$db_notice, $req_status);
                        return false;
                    }
                }
            }

            $message = ['status' => esc_html__('Endpoint not configured properly', 'runcloud-hub'), 'endpoint' => (array) $ep];
            self::debug(__METHOD__, $message);
            $req_status = array(
                'code' => 0,
                'status' => sprintf(esc_html__('Updating cache rules to the server failed - %s', 'runcloud-hub'), $message['status']),
            );
            if ($status) {
                return $req_status;
            } else {
                update_option(self::$db_notice, $req_status);
                return false;
            }
        }

        $message = esc_html__('Unknown Error. Please contact support.', 'runcloud-hub');
        self::debug(__METHOD__, $message);
        $req_status = array(
            'code' => 0,
            'status' => sprintf(esc_html__('Updating cache rules to the server failed - %s', 'runcloud-hub'), $message),
        );

        if ($status) {
            return $req_status;
        } else {
            update_option(self::$db_notice, $req_status);
            return false;
        }
    }

    /**
     * get_magic_users.
     */
    public static function get_magic_users()
    {

        $magiclink_enabled = !empty(self::get_setting('rcapi_magiclink_onn')) ? true : false;
        $blog_id = get_current_blog_id();

        $results = [];

        if (!is_multisite()) {
            $args = [
                'role__in' => ['administrator'],
                'order'    => 'ASC',
            ];

            $list    = get_users($args);
            if (!empty($list) && is_array($list)) {
                foreach ($list as $num => $arr) {
                    if (!empty($arr->data->deleted)) {
                        continue;
                    }

                    $results[$num]['magiclink']  = $magiclink_enabled;
                    $results[$num]['blog_id']    = $blog_id;
                    $results[$num]['user_id']    = $arr->data->ID;
                    $results[$num]['user_login'] = $arr->data->user_login;
                    $results[$num]['user_email'] = $arr->data->user_email;
                    $results[$num]['user_role']  = implode(',', $arr->roles);
                    $results[$num]['url']        = get_site_url();
                    $results[$num]['domain']     = wp_parse_url(get_site_url(), PHP_URL_HOST);
                }
            }
        }
        else {
            $super_admins = get_super_admins();

            if (!empty($super_admins) && is_array($super_admins)) {
                $num = 0;
                foreach ($super_admins as $super_admin) {
                    $arr = get_user_by('login', $super_admin);

                    if (!empty($arr->data->deleted)) {
                        continue;
                    }

                    $results[$num]['magiclink']  = $magiclink_enabled;
                    $results[$num]['blog_id']    = $blog_id;
                    $results[$num]['user_id']    = $arr->data->ID;
                    $results[$num]['user_login'] = $arr->data->user_login;
                    $results[$num]['user_email'] = $arr->data->user_email;
                    $results[$num]['user_role']  = 'super_administrator';
                    $results[$num]['url']        = get_site_url();
                    $results[$num]['domain']     = wp_parse_url(get_site_url(), PHP_URL_HOST);

                    $num++;
                }
            }
        }

        return $results;
    }

    /**
     * remove_magic_user_token.
     */
    public static function remove_magic_user_token()
    {
        $list = self::get_magic_users();
        if (!empty($list) && is_array($list)) {
            while ($row = @array_shift($list)) {
                $user_id = $row['user_id'];
                delete_user_meta($user_id, 'magic_link_token');
            }
        }
    }

    /**
     * generate_magic_link.
     */
    public static function generate_magic_link($user_id, $blog_id)
    {
        $expiry = strtotime('+5 minutes');
        $site_url = get_site_url($blog_id, '/');
        $domain = wp_parse_url($site_url, PHP_URL_HOST);

        $url = add_query_arg( array(
            self::$magicuserid => $user_id,
            self::$magicexpiry => $expiry,
        ), $site_url );

        $salt = wp_salt('auth');
        $selector = wp_generate_password(16, false, false);
        $verifier = json_encode(array($selector, $domain, (int) $user_id, (int) $expiry));
        $validator = hash_hmac( 'sha256', $verifier, $salt );

        $url = add_query_arg( array(
            self::$magictoken => $selector.'|'.$validator,
        ), $url );

        $data   = [
            'token'    => $selector,
            'expiry'   => $expiry,
            'expiry_h' => gmdate('Y-m-d H:i:s') . ' UTC',
        ];

        update_user_meta($user_id, 'magic_link_token', $data);

        return [
            'url'    => $url,
            'expiry' => $data['expiry_h'],
        ];
    }

    /**
     * callback_magiclink_login.
     */
    public static function callback_magiclink_login()
    {
        if (!isset($_GET[self::$magicuserid], $_GET[self::$magicexpiry], $_GET[self::$magictoken])) {
            return;
        }

        $user_id = intval($_GET[self::$magicuserid]);
        $expiry = intval($_GET[self::$magicexpiry]);
        $token = sanitize_text_field($_GET[self::$magictoken]);

        if (empty($user_id) || empty($expiry) || empty($token)) {
            return;
        }

        if (strpos($token, '|') === false) {
            wp_die( esc_html__('Invalid Magic Link', 'runcloud-hub'), self::$slug);
        }

        $token_split = explode('|', $token);
        $selector = strval($token_split[0]);
        $validator = strval($token_split[1]);

        $data = get_user_meta($user_id, 'magic_link_token', true);
        if (empty($data)) {
            wp_die( esc_html__('Invalid Magic Link', 'runcloud-hub'), self::$slug);
        }

        if ($selector !== $data['token']) {
            wp_die( esc_html__('Invalid Magic Link', 'runcloud-hub'), self::$slug);
        }

        if ($expiry !== $data['expiry']) {
            wp_die( esc_html__('Invalid Magic Link', 'runcloud-hub'), self::$slug);
        }

        if (time() >= $data['expiry']) {
            delete_user_meta($user_id, 'magic_link_token');
            wp_die( esc_html__('Expired Magic Link', 'runcloud-hub'), self::$slug);
        }

        $salt = wp_salt('auth');
        $site_url = site_url('/');
        $domain = wp_parse_url($site_url, PHP_URL_HOST);

        $verifier = json_encode(array($selector, $domain, (int) $user_id, (int) $expiry));
        $validator_expected = hash_hmac( 'sha256', $verifier, $salt );

        if ( ! hash_equals( $validator, $validator_expected ) ) {
            wp_die( esc_html__('Invalid Magic Link', 'runcloud-hub'), self::$slug);
        }

        $user_object = get_user_by('id', $user_id);
        wp_set_auth_cookie($user_object->ID, false);
        do_action('wp_login', $user_object->data->user_login, $user_object);

        $redirect_url = admin_url('/');
        if (is_multisite() && is_super_admin($user_id)) {
            $redirect_url = network_admin_url('/');
        }

        wp_safe_redirect($redirect_url);

        self::close_exit();
    }

    /**
     * register_update.
     */
    private static function register_update()
    {
        add_filter( 'plugins_api', [__CLASS__, 'plugins_api_filter' ], 20, 3 );
        add_filter( 'pre_set_site_transient_update_plugins', [__CLASS__, 'update_plugins_filter' ] );
    }

    /**
     * get_latest_version.
     */
    private static function get_latest_version($force = false) {
        $beta_tester = self::get_setting('beta_tester_onn');

        $transient_key = self::$transientk . ($beta_tester ? '_betaversion' : '_latestversion' );
        $version_url = $beta_tester ? self::$beta_url : self::$trunk_url;

        $data = get_transient($transient_key);

        if (!$force && !empty($data)) {
            return $data;
        }

        if (false !== strpos($version_url, '{{PANELURL}}')) {
            return $data;
        }

        $remote = wp_remote_get(
            $version_url,
            [
                'timeout' => 5,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'sslverify'   => false,
            ]
        );

        if (!is_wp_error($remote) && isset($remote['response']['code']) && 200 === $remote['response']['code'] && !empty($remote['body'])) {
            $data = $remote['body'];
            delete_transient($transient_key);
            set_transient($transient_key, $data, 43200); // 12 hours
            return $data;
        }

        return $data;
    }

    /**
     * plugins_api_filter.
     */
    public static function plugins_api_filter($res, $action, $args) {
        if ('plugin_information' !== $action) {
            return false;
        }

        if (self::$slug !== $args->slug) {
            return false;
        }

        $data = self::get_latest_version();
        if (empty($data)) {
            return false;
        }

        $remote = json_decode($data);
        if (empty($remote->name) || empty($remote->version) || empty($remote->download_url)) {
            return false;
        }

        $res    = new stdClass;

        $res->slug           = self::$slug;
        $res->name           = $remote->name;
        $res->version        = $remote->version;
        $res->download_link  = $remote->download_url;
        $res->trunk          = $remote->download_url;

        $res->tested         = isset($remote->tested) ? $remote->tested : '';
        $res->requires       = isset($remote->requires) ? $remote->requires : '';
        $res->author         = isset($remote->author) ? $remote->author : '';
        $res->author_profile = isset($remote->author_profile) ? $remote->author_profile : '';
        $res->requires_php   = isset($remote->requires_php) ? $remote->requires_php : '';
        $res->last_updated   = isset($remote->last_updated) ? $remote->last_updated : '';

        $res->sections = [];

        if (!empty($remote->sections->description)) {
            $res->sections['description'] = $remote->sections->description;
        }

        if (!empty($remote->sections->installation)) {
            $res->sections['installation'] = $remote->sections->installation;
        }

        if (!empty($remote->sections->changelog)) {
            $res->sections['changelog'] = $remote->sections->changelog;
        }

        if (!empty($remote->sections->screenshots)) {
            $res->sections['screenshots'] = $remote->sections->screenshots;
        }

        $res->banners = [];

        if (!empty($remote->banners->low)) {
            $res->banners['low'] = $remote->banners->low;
        }

        if (!empty($remote->banners->high)) {
            $res->banners['high'] = $remote->banners->high;
        }

        return $res;
    }

    /**
     * update_plugins_filter.
     */
    public static function update_plugins_filter($transient) {

        global $pagenow;

        $force = in_array($pagenow, ['update-core.php']) && !empty($_GET['force-check']) && (int) $_GET['force-check'] === 1 ? true : false;

        $data = self::get_latest_version($force);

        if (empty($data)) {
            return $transient;
        }

        $remote = json_decode($data);
        if (empty($remote->name) || empty($remote->version) || empty($remote->download_url)) {
            return $transient;
        }

        if (!version_compare(self::$version, $remote->version, '<')) {
            return $transient;
        }
        
        if (!empty($remote->requires) && !version_compare($remote->requires, get_bloginfo('version'), '<')) {
            return $transient;
        }

        $res              = new stdClass();
        $res->id          = $remote->id;
        $res->slug        = self::$slug;
        $res->plugin      = self::$hook;

        $res->package     = $remote->download_url;
        $res->new_version = $remote->version;

        $res->tested      = isset($remote->tested) ? $remote->tested : '';
        $res->url         = isset($remote->url) ? $remote->url : '';

        if (isset($remote->icons1x)) {
            $res->icons['1x'] = $remote->icons1x;
        }
        if (isset($remote->icons2x)) {
            $res->icons['2x'] = $remote->icons2x;
        }

        if (isset($remote->iconssvg)) {
            $res->icons['svg'] = $remote->iconssvg;
        }

        $transient->response[$res->plugin] = $res;

        return $transient;
    }

    /**
     * attach.
     */
    public static function attach()
    {
        self::register_init();
        self::register_cron_schedules();
        self::register_plugin_hooks();
        self::register_admin_hooks();
        self::register_purge_hooks();
        self::register_integrations();
        self::register_update();
    }
}

RunCloud_Hub::attach();
