<?php
if (!defined('RUNCLOUD_HUB_PATH')) {
    exit;
}

class RunCloud_Hub_CLI extends \WP_CLI_Command
{
    private function print_data($data, $pretty = true)
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        if (!$pretty) {
            $options = JSON_UNESCAPED_SLASHES;
        }

        $result = json_encode($data, $options);
        WP_CLI::Log($result);
    }

    private function is_pretty_print($assoc_args)
    {
        return (isset($assoc_args) && is_array($assoc_args) && isset($assoc_args['pretty-print']));
    }

    private function is_all($assoc_args)
    {
        return (isset($assoc_args) && is_array($assoc_args) && isset($assoc_args['all']));
    }

    private function is_status($assoc_args)
    {
        return (isset($assoc_args) && is_array($assoc_args) && isset($assoc_args['status']));
    }

    //@NOTE: wp help runcloud-hub purgeall
    /**
     * Purge All Cache
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub purgeall
     *     wp runcloud-hub purgeall --status
     *
     */
    public function purgeall($args, $assoc_args)
    {
        $is_status = $this->is_status($assoc_args);

        if (is_multisite()) {
            $status = RunCloud_Hub::wpcli_nginx_purge_all_sites();
        }
        else {
            $status = RunCloud_Hub::wpcli_nginx_purge_all();
        }

        $ok = (200 === (int) $status['code'] ? true : false);
        if ( $is_status ) {
            $message = $status['status'];
        }
        else {
            $message = RunCloud_Hub::array_export($status);
        }

        if ($ok) {
            WP_CLI::success($message, false);
            WP_CLI::halt(0);
        }
        else {
            WP_CLI::error($message, false);
            WP_CLI::halt(1);
        }
    }

    //@NOTE: wp help runcloud-hub purgeredis
    /**
     * Purge All Redis Object Cache
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub purgeredis
     *     wp runcloud-hub purgeredis --status
     *
     */
    public function purgeredis($args, $assoc_args)
    {
        $is_status = $this->is_status($assoc_args);

        if (is_multisite()) {
            $status = RunCloud_Hub::purge_cache_redis_all();
        }
        else {
            $status = RunCloud_Hub::purge_cache_redis();
        }

        $ok = (200 === (int) $status['code'] ? true : false);
        if ( $is_status ) {
            $message = $status['status'];
        }
        else {
            $message = RunCloud_Hub::array_export($status);
        }

        if ($ok) {
            WP_CLI::success($message, false);
            WP_CLI::halt(0);
        }
        else {
            WP_CLI::error($message, false);
            WP_CLI::halt(1);
        }
    }

    //@NOTE: wp help runcloud-hub update-dropin
    /**
     * Install or replace any existing object cache drop-in
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub update-dropin
     *
     * @subcommand update-dropin
     */
    public function update_dropin()
    {
        $status = RunCloud_Hub::reinstall_dropin(true);
        $ok = (200 === (int) $status['code'] ? true : false);
        $message = RunCloud_Hub::array_export($status);

        if ($ok) {
            WP_CLI::success($message, false);
            WP_CLI::halt(0);
        }

        WP_CLI::error($message, false);
        WP_CLI::halt(1);
    }

    //@NOTE: wp help runcloud-hub options
    /**
     * Display, Update, Reset Plugin Settings
     *
     * ## OPTIONS
     *
     * [<print>]
     * : Display Plugin Settings. By default display for primary site
     *
     * [<reset>]
     * : Reset Plugin Settings. By default only apply to primary site
     *
     * [<update>]
     * : Update Plugin Settings. By default apply to primary site
     *
     * [--blog-id=<blog-id>]
     * : Switch to another site if multisite
     *
     * [--pretty-print]
     * : Pretty print
     *
     * [--all]
     * : If multisite apply to all sites
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub options print
     *     wp runcloud-hub options print --all
     *     wp runcloud-hub options print --pretty-print
     *     wp runcloud-hub options print --all --pretty-print
     *
     *     wp runcloud-hub options reset
     *     wp runcloud-hub options reset --blog-id=1
     *     wp runcloud-hub options reset --all
     *
     *     wp runcloud-hub options update '{"preload_onn":"0"}'
     *     wp runcloud-hub options update '{"preload_onn":"0"}' --blog-id=1
     *     wp runcloud-hub options update '{"preload_onn":"0"}' --all
     *
     */
    public function options($args, $assoc_args)
    {
        if (!empty($args) && is_array($args)) {
            $arg = $args[0];

            $blog_id = null;
            if (!empty($assoc_args['blog-id']) && RunCloud_Hub::is_num($assoc_args['blog-id'])) {
                $blog_id = $assoc_args['blog-id'];
            }

            $is_all = $this->is_all($assoc_args);
            $is_pretty = $this->is_pretty_print($assoc_args);

            if ('update' === $arg) {
                if (!empty($args[1])) {
                    $data = json_decode($args[1], true);

                    if (!empty($data) && is_array($data)) {
                        $is_switch = false;
                        if (!is_null($blog_id)) {
                            $is_switch = switch_to_blog($blog_id);
                        }

                        $input_var = RunCloud_Hub::get_setting();

                        foreach ($data as $n => $m) {
                            if (!isset($input_var[ $n ])) {
                                unset($data[ $n ]);
                            }
                        }

                        if (!empty($data)) {
                            foreach ($input_var as $k => $v) {
                                if (!isset($data[ $k ])) {
                                    $data[ $k ] = $input_var[ $k ];
                                }
                            }

                            RunCloud_Hub::update_setting_cli($data);
                            if ($is_switch) {
                                restore_current_blog();
                            }

                            WP_CLI::success('Updating settings was successful', false);
                            WP_CLI::halt(0);
                        }
                    }
                }

                WP_CLI::Log('Invalid input');
                WP_CLI::halt(1);
            } elseif ('print' === $arg) {
                $is_switch = false;
                if (!is_null($blog_id) && !$is_all) {
                    $is_switch = switch_to_blog($blog_id);
                }

                $this->print_data(RunCloud_Hub::dump_setting($is_all), $is_pretty);

                if ($is_switch) {
                    restore_current_blog();
                }

                WP_CLI::halt(0);
            } elseif ('reset' === $arg) {
                if ($is_all) {
                    RunCloud_Hub::reinstall_options(true);
                } else {
                    $is_switch = false;
                    if (!is_null($blog_id)) {
                        $is_switch = switch_to_blog($blog_id);
                    }

                    RunCloud_Hub::reinstall_options();

                    if ($is_switch) {
                        restore_current_blog();
                    }
                }
                WP_CLI::success('Reinstall settings was successful', false);
                WP_CLI::halt(0);
            }
        }

        WP_CLI::Log('Usage: runcloud-hub options print [--blog-id=<blog-id>] [--pretty-print] [--all]');
        WP_CLI::Log('   or: runcloud-hub options reset [--blog-id=<blog-id>] [--all]');
        WP_CLI::Log('   or: runcloud-hub options update <json-data> [--blog-id=<blog-id>] [--all]');
        WP_CLI::halt(1);
    }

    //@NOTE: wp help runcloud-hub install
    /**
     * Install Plugin API Settings
     *
     * ## OPTIONS
     *
     * <hub-api-key>
     * : HUB API Key.
     *
     * <hub-api-secret>
     * : Hub API Secret.
     *
     * <webapp-id>
     * : WebApp Id.
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub install apikey apisecret webappid
     *
     */
    public function install($args)
    {
        if (!empty($args) && is_array($args) && count($args) === 3) {
            $data = [];
            $data['rcapi_key'] = $args[0];
            $data['rcapi_secret'] = $args[1];
            $data['rcapi_webapp_id'] = $args[2];

            if (!RunCloud_Hub::update_setting_cli($data)) {
                WP_CLI::error('Installing Panel API has failed', false);
                WP_CLI::halt(1);
            }

            WP_CLI::success('Installing Panel API was successful', false);
            WP_CLI::halt(0);
        }

        WP_CLI::Log('Usage: runcloud-hub install <hub-api-key> <hub-api-secret> <webapp-id>');
        WP_CLI::halt(1);
    }

    //@NOTE: wp help runcloud-hub magic-link
    /**
     * Generate Auto Login URL
     *
     * ## OPTIONS
     *
     * <blog-id>
     * : WordPress Blog Id.
     *
     * <user-id>
     * : WordPress User Id.
     *
     * [--pretty-print]
     * : Pretty print
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub magic-link <user-id> <blog-id>
     *
     * @subcommand magic-link
     */
    public function magic_link($args, $assoc_args)
    {
        if (!empty($args) && is_array($args) && count($args) <= 2) {
            $is_pretty = $this->is_pretty_print($assoc_args);

            $blog_id = $args[0];

            if (is_multisite()) {
                switch_to_blog($blog_id);
            } else {
                if ((int)$blog_id !== get_current_blog_id()) {
                    $response = [
                        'active' => false,
                        'msg' => 'blog_id not match with current blog id'
                    ];
                    $this->print_data($response, $is_pretty);
                    WP_CLI::halt(1);
                }
            }

            $options = RunCloud_Hub::get_setting();
            if (empty($options['rcapi_magiclink_onn'])) {
                $response = [
                    'active' => false,
                    'msg' => 'Magic Link has been disabled'
                ];
                $this->print_data($response, $is_pretty);
                WP_CLI::halt(1);
            }
            if (is_multisite()) {
                restore_current_blog();
            }

            $user_id = $args[1];
            $data = RunCloud_Hub::generate_magic_link($user_id, $blog_id);
            $response['active'] = true;
            $response = array_merge($response, $data);
            $this->print_data($response, $is_pretty);
            WP_CLI::halt(0);
        }

        WP_CLI::Log('Usage: runcloud-hub magiclink <blog-id> <user-id>');
        WP_CLI::halt(1);
    }

    //@NOTE: wp help runcloud-hub update-cache-type with parameter
    /**
     * Update Runcloud Hub Cache Type 
     * 
     *  
     * ## OPTIONS
     * 
     *      <type>
     *      : type of cache either sr or fastcgi
     * 
     * ## EXAMPLES
     *
     *      wp runcloud-hub update-cache-type sr
     *
     * @subcommand update-cache-type
     */
    public function update_cache_type($args)
    {
        
        if (!empty($args) && is_array($args)) {
            $type = $args[0];

            $ok = RunCloud_Hub::update_cache_type($type, $message);
            if ($ok) {
                WP_CLI::success($message, false);
                WP_CLI::halt(0);
            }
            else {
                WP_CLI::error($message, false);
                WP_CLI::halt(1);
            }
        }

        WP_CLI::Log('Usage: runcloud-hub update-cache-type <type>');
        WP_CLI::halt(1);
    }

    //@NOTE: wp help runcloud-hub magic-user
    /**
     * Display magic link users
     *
     * ## OPTIONS
     *
     * [--pretty-print]
     * : Pretty print
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub magic-user
     *     wp runcloud-hub magic-user --pretty-print
     *
     * @subcommand magic-user
     */
    public function magic_user($args, $assoc_args)
    {
        $is_pretty = $this->is_pretty_print($assoc_args);

        self::print_data(RunCloud_Hub::get_magic_users(), $is_pretty);
        WP_CLI::halt(0);
    }

    //@NOTE: wp help runcloud-hub rcapi-push
    /**
     * Send configuration to RunCloud Panel
     *
     * ## EXAMPLES
     *
     *     wp runcloud-hub rcapi-push
     *
     * @subcommand rcapi-push
     */
    public function rcapi_push()
    {
        $status = RunCloud_Hub::rcapi_push(true);
        $ok = (200 === (int) $status['code'] ? true : false);
        $message = RunCloud_Hub::array_export($status);

        if ($ok) {
            WP_CLI::success($message, false);
            WP_CLI::halt(0);
        }

        WP_CLI::error($message, false);
        WP_CLI::halt(1);
    }
}
