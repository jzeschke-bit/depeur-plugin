<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<!-- redis-status -->
<div class="mb-6 display-none" data-tab-page="redis" data-tab-page-title="<?php esc_html_e('Redis Object Cache', 'runcloud-hub');?>">
    <?php if ( self::redis_is_enabled() ): ?>
        <?php if ( self::is_dropin_exists() ):?>
            <?php if( self::is_dropin_hub() ): ?>
                <?php if ( defined('RCWP_REDIS_DISABLED') && RCWP_REDIS_DISABLED ): ?>
                    <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache is disabled via RCWP_REDIS_DISABLED constant.', 'runcloud-hub');?></p>
                <?php else: ?>
                    <?php if ( self::is_dropin_valid() ):?>
                        <?php if ( self::is_dropin_config_need_update() ): ?>
                            <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache should be enabled for this site, but redis site config drop-in file is not valid and needs update.', 'runcloud-hub');?></p>
                        <?php else: ?>
                            <?php if ( self::redis_is_connect() ): ?>
                                <p class="px-6 py-3 bg-blue-100 border border-blue-300 text-blue-700 rounded-sm shadow"><?php esc_html_e('Object cache is enabled for this site.', 'runcloud-hub');?></p>
                            <?php else: ?>
                                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache is not active because Redis is not connected.', 'runcloud-hub');?></p>
                                <?php if ( self::redis_is_enabled() && self::redis_can_enable() ): ?>
                                    <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Please disable Redis object cache option to completely disable object cache.', 'runcloud-hub');?></p>
                                <?php endif;?>
                            <?php endif;?>
                        <?php endif;?>
                    <?php else: ?>
                        <?php if ( self::redis_is_connect() ): ?>
                            <p class="px-6 py-3 bg-blue-100 border border-blue-300 text-blue-700 rounded-sm shadow"><?php esc_html_e('Object cache is enabled for this site.', 'runcloud-hub');?></p>
                            <p class="mt-6 px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache drop-in file is outdated and needs to be updated.', 'runcloud-hub');?></p>
                        <?php else: ?>
                            <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache should be enabled for this site but drop-in file needs update.', 'runcloud-hub');?></p>
                        <?php endif;?>
                    <?php endif;?>
                <?php endif;?>
            <?php else: ?>
                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Unknown object cache drop-in file. Object cache is handled by other plugin.', 'runcloud-hub');?></p>
            <?php endif;?>
        <?php else: ?>
            <?php if ( defined('RUNCLOUD_HUB_INSTALL_DROPIN') && !RUNCLOUD_HUB_INSTALL_DROPIN ): ?>
                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache drop-in is not installed because RUNCLOUD_HUB_INSTALL_DROPIN constant is false.', 'runcloud-hub');?></p>
            <?php else: ?>
                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache drop-in has not been installed.', 'runcloud-hub');?></p>
            <?php endif;?>
        <?php endif;?>
    <?php else: ?>
        <?php if ( self::is_dropin_exists() && self::is_dropin_hub() && !(defined('RCWP_REDIS_DISABLED') && RCWP_REDIS_DISABLED) ): ?>
            <?php if ( self::redis_is_connect() ): ?>
                <p class="px-6 py-3 bg-blue-100 border border-blue-300 text-blue-700 rounded-sm shadow"><?php esc_html_e('Object cache is still enabled for this site.', 'runcloud-hub');?></p>
            <?php else: ?>
                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache is disabled for this site.', 'runcloud-hub');?></p>
                <?php if ( self::is_dropin_need_update() ): ?>
                    <p class="mt-6 px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache drop-in file is outdated and needs to be updated.', 'runcloud-hub');?></p>
                <?php endif;?>
            <?php endif;?>
        <?php else: ?>
            <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php esc_html_e('Object cache is disabled for this site.', 'runcloud-hub');?></p>
        <?php endif;?>
    <?php endif;?>
</div>
<!-- /redis-status -->

<!-- redis-server -->
<div class="mb-6 display-none" data-tab-page="redis" data-tab-page-title="<?php esc_html_e('Redis Object Cache', 'runcloud-hub');?>">
     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li class="" data-action-css-id="redis_cache_onn" data-action-css="">
                <?php if(self::redis_can_enable()) : ?>
                <!-- enable -->
                    <input type="hidden" id="redis_cache_onn" name="<?php self::view_fname('redis_cache_onn');?>" value="<?php self::view_fvalue('redis_cache_onn');?>">
                    <?php $redis_action = self::get_setting('redis_cache_onn') ? 'disableredis' : 'enableredis'; ?>

                    <p class="mb-4 rc-toggle-title">
                        <?php esc_html_e('For any types of highly dynamic websites, you can use Redis Object Cache to use less database resources by caching the results of complex database queries, speed up PHP execution time in your server, and make your dynamic website load much faster.', 'runcloud-hub');?>  
                        <span class="text-blue-900 cursor-pointer"><?php esc_html_e('Read more', 'runcloud-hub');?></span>
                    </p>
                    <div class="rc-toggle-content" style="display:none;">
                        <p class="mb-4"><?php esc_html_e('If page caching works on caching the HTML page output, then object caching (Redis Object Cache) works on caching your database queries.', 'runcloud-hub');?></p>
                        <p class="mb-4"><?php esc_html_e('When a user visits a WordPress page, many complex database queries are performed on your server and the results are cached by Redis Object Cache.', 'runcloud-hub');?></p>
                        <p class="mb-4"><?php esc_html_e('When user visit the page again or another user visits the same page, your website will not perform the same complex database queries again, because the results are already cached and served by the Redis Object Cache.', 'runcloud-hub');?></p>
                        <p class="mb-4"><?php esc_html_e('It will reduce your database queries, reduce your server load, and make your dynamic WordPress site load faster.', 'runcloud-hub');?></p>
                        <p class="mb-4"><strong><?php esc_html_e('WARNING! If your website becomes slower after enabling Redis Object Cache and no server issue, then it could be one of your active plugin/theme is not fully compatible with Redis Object Cache. For this case, it is better to disable Redis Object Cache.', 'runcloud-hub');?></strong></p>
                    </div>

                    <?php if ( ! is_multisite() ) : ?>
                        <a href="<?php self::view_action_link($redis_action);?>#redis" class="button button-primary">
                            <?php if ($redis_action == 'enableredis') : ?>
                                <?php esc_html_e('Enable Object Cache', 'runcloud-hub');?>
                            <?php else : ?>
                                <?php esc_html_e('Disable Object Cache', 'runcloud-hub');?>
                            <?php endif; ?>
                        </a>
                    <?php else : ?>
                        <?php if ( defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL ): ?>
                            <?php if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) : ?>
                                <a href="<?php self::view_action_link($redis_action);?>#redis" class="button button-primary">
                                    <?php if ($redis_action == 'enableredis') : ?>
                                        <?php esc_html_e('Enable Object Cache For All Sites', 'runcloud-hub');?>
                                    <?php else : ?>
                                        <?php esc_html_e('Disable Object Cache For All Sites', 'runcloud-hub');?>
                                    <?php endif; ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php self::view_action_link($redis_action.'site');?>#redis" class="button button-primary">
                                    <?php if ($redis_action == 'enableredis') : ?>
                                        <?php esc_html_e('Enable Object Cache', 'runcloud-hub');?>
                                    <?php else : ?>
                                        <?php esc_html_e('Disable Object Cache', 'runcloud-hub');?>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <a href="<?php self::view_action_link($redis_action);?>#redis" class="button button-primary">
                                <?php if ($redis_action == 'enableredis') : ?>
                                    <?php esc_html_e('Enable Object Cache For All Sites', 'runcloud-hub');?>
                                <?php else : ?>
                                    <?php esc_html_e('Disable Object Cache For All Sites', 'runcloud-hub');?>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <!-- /enable -->
                <?php endif; ?>

                <!-- action -->
                <?php if ( self::is_dropin_need_install() ): ?>
                    <?php if (self::is_main_site()) : ?>
                        <a href="<?php self::view_action_link('installdropin');?>#redis" class="button button-secondary"><?php esc_html_e('Install Object Cache Drop-in', 'runcloud-hub'); ?></a>
                    <?php endif; ?>
                <?php elseif ( self::is_dropin_need_update() ): ?>
                    <?php if (self::is_main_site()) : ?>
                        <a href="<?php self::view_action_link('installdropin');?>#redis" class="button button-secondary"><?php esc_html_e('Update Object Cache Drop-in', 'runcloud-hub'); ?></a>
                    <?php endif; ?>
                <?php elseif ( self::is_dropin_need_replace() ): ?>
                    <?php if (self::is_main_site()) : ?>
                        <a href="<?php self::view_action_link('installdropin');?>#redis" class="button button-secondary"><?php esc_html_e('Replace Object Cache Drop-in', 'runcloud-hub'); ?></a>
                    <?php endif; ?>
                <?php elseif ( self::is_dropin_config_need_update() ): ?>
                    <?php if (self::is_main_site()) : ?>
                        <a href="<?php self::view_action_link('installdropin');?>#redis" class="button button-secondary"><?php esc_html_e('Fix Redis Site Config Drop-in', 'runcloud-hub'); ?></a>
                    <?php endif; ?>
                <?php endif;?>
                <!-- /action -->
            </li>

            <?php if(self::redis_can_enable() && is_multisite() && is_network_admin()) : ?>
                <li class="leading-loose">
                    <?php if(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) : ?>
                        <?php if(defined('RCWP_REDIS_NETWORK_ACTIVE') && RCWP_REDIS_NETWORK_ACTIVE) : ?>
                            <p class="rc-toggle-title">
                                <?php esc_html_e('This network site uses WordPress Multisite Subdomain. Currently, Redis Object Cache can be enabled/disabled on network-wide level, it can not be enabled/disabled on subsite level.', 'runcloud-hub');?>
                                <span class="text-blue-900 cursor-pointer"><?php esc_html_e('Read more', 'runcloud-hub');?></span>
                            </p>
                            <div class="rc-toggle-content" style="display:none;">
                                <p class="mt-4"><?php esc_html_e('Allowing subsite Administrator to enable/disable Redis Object Cache is useful for small WordPress Multisite network, less than 100 subsites, where you have full total control for all subsites.', 'runcloud-hub');?></p>
                                <p class="mt-4"><?php esc_html_e('If you want to show enable/disable Redis Object Cache on subsite, you can remove/edit the constant below in the wp-config.php file.', 'runcloud-hub');?></p>
                                <p class="mt-4"><code>define( 'RCWP_REDIS_NETWORK_ACTIVE', false );</code></p>
                            </div>
                        <?php else : ?>
                            <p class="rc-toggle-title">
                                <?php esc_html_e('This network site uses WordPress Multisite Subdomain. Currently, Redis Object Cache can be enabled/disabled on subsite level by user with Administrator level on the subsite.', 'runcloud-hub');?> 
                                <span class="text-blue-900 cursor-pointer"><?php esc_html_e('Read more', 'runcloud-hub');?></span>
                            </p>
                            <div class="rc-toggle-content" style="display:none;">
                                <p class="mt-4"><?php esc_html_e('Allowing subsite Administrator to enable/disable Redis Object Cache is useful for small WordPress Multisite network, where you have full total control for all subsites.', 'runcloud-hub');?></p>
                                <p class="mt-4"><?php esc_html_e('For big WordPress Multisite network, for example WaaS network, with more than 100 subsites, it is better to hide enable/disable Redis Object Cache on subsite by adding this constant to wp-config.php file.', 'runcloud-hub');?></p>
                                <p class="mt-4"><code>define( 'RCWP_REDIS_NETWORK_ACTIVE', true );</code></p>
                            </div>
                            <p class="mt-4">
                                <a href="<?php self::view_action_link('enableredisall');?>#redis" class="button button-secondary">
                                    <?php esc_html_e('Enable For All Subsites', 'runcloud-hub');?>
                                </a>
                                <a href="<?php self::view_action_link('disableredisall');?>#redis" class="button button-secondary">
                                    <?php esc_html_e('Disable For All Subsites', 'runcloud-hub');?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p><?php esc_html_e('This network site uses WordPress Multisite Subdirectory. Redis Object Cache can be enabled/disabled on network-wide level, it can not be enabled/disabled on subsite level.', 'runcloud-hub');?></p>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ( self::is_main_site() && self::is_dropin_active() && self::redis_is_connect() ): ?>
            <li class="leading-loose">
                <?php
                global $wp_object_cache;
                $redis_client = '';
                if ( isset( $wp_object_cache->diagnostics[ 'client' ] ) ) {
                    $redis_client = $wp_object_cache->diagnostics[ 'client' ];
                }
                ?>
                <span class="font-bold"><?php esc_html_e('Client', 'runcloud-hub');?></span> : <code><?php echo esc_html( $redis_client ); ?></code>

                <?php if ( method_exists( $wp_object_cache, 'redis_version' ) ) : ?>
                    <br/><span class="font-bold"><?php esc_html_e('Redis Version', 'runcloud-hub');?></span> : <code><?php echo esc_html( $wp_object_cache->redis_version() ); ?></code>
                <?php endif;?>

                <?php if ( property_exists( $wp_object_cache, 'diagnostics' ) ) : $diagnostics = $wp_object_cache->diagnostics; ?>
                    <?php if ( ! empty( $diagnostics['host'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Host', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['host'] ); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['cluster'] ) && is_array( $diagnostics['cluster'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Cluster', 'runcloud-hub');?></span> : <code><?php echo esc_html(implode( ', ', $diagnostics['cluster'] )); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['shards'] ) && is_array( $diagnostics['shards'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Shards', 'runcloud-hub');?></span> : <code><?php echo esc_html(implode( ', ', $diagnostics['shards'] )); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['servers'] ) && is_array( $diagnostics['servers'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Servers', 'runcloud-hub');?></span> : <code><?php echo esc_html(implode( ', ', $diagnostics['servers'] )); ?></code>
                    <?php endif; ?>

                    <?php if ( ! empty( $diagnostics['port'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Port', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['port'] ); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['password'][0] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Username', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['password'][0] ); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['password'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Password', 'runcloud-hub');?></span> : <code><?php echo str_repeat( '&#8226;', 8 ); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['database'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Database', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['database'] ); ?></code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['timeout'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Connection Timeout', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['timeout'] ); ?>s</code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['read_timeout'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Read Timeout', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['read_timeout'] ); ?>s</code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['retry_interval'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Retry Interval', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['retry_interval'] ); ?>ms</code>
                    <?php endif; ?>

                    <?php if ( defined( 'RCWP_REDIS_MAXTTL' ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Max TTL', 'runcloud-hub');?></span> : <code><?php echo esc_html( RCWP_REDIS_MAXTTL ); ?>s</code>
                    <?php endif; ?>

                    <?php if ( isset( $diagnostics['prefix'] ) ) : ?>
                        <br/><span class="font-bold"><?php esc_html_e('Key Prefix', 'runcloud-hub');?></span> : <code><?php echo esc_html( $diagnostics['prefix'] ); ?></code>
                    <?php endif; ?>

                    <p class="mt-2 rc-toggle-title">
                        <span class="text-blue-900 cursor-pointer"><?php esc_html_e('Learn how to customize the config', 'runcloud-hub');?></span>
                    </p>
                    <div class="rc-toggle-content" style="display:none;">
                        <p class="mt-4"><?php esc_html_e('You can customize Redis object cache by adding the constant to your wp-config.php file, for example:', 'runcloud-hub');?></p>
                        <p class="mt-4"><code>define( 'RCWP_REDIS_HOST', '127.0.0.1' );</code></p>
                        <p class="mt-2"><code>define( 'RCWP_REDIS_PORT', 6379 );</code></p>
                        <p class="mt-2"><code>define( 'RCWP_REDIS_DATABASE', 0 );</code></p>
                        <p class="mt-2"><code>define( 'RCWP_REDIS_TIMEOUT', 1 );</code></p>
                        <p class="mt-2"><code>define( 'RCWP_REDIS_READ_TIMEOUT', 1 );</code></p>
                        <p class="mt-2"><code>define( 'RCWP_REDIS_MAXTTL', 86400 );</code></p>
                        <p class="mt-4"><?php esc_html_e('WARNING! Please clear Redis Object Cache and then disable Redis Object Cache first before you customize the configuration to prevent any unnecessary issue.', 'runcloud-hub');?></p>
                    </div>
                <?php endif;?>
            </li>
            <?php endif;?>
        </ul>
    </fieldset>
</div>
<!-- /redis-server -->

<?php if (self::is_main_site()): ?>

<!-- redis-cache-options -->
<div class="mb-6 display-none" data-tab-page="redis" data-tab-page-title="<?php esc_html_e('Redis Object Cache', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold leading-tight text-base-1000"><?php esc_html_e('Redis Object Cache Options', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
            <!-- key-prefix -->
                <div class="form-group pb-0">
                    <label class="control-label" for="redis_prefix"><?php esc_html_e('Custom Object Cache Key Prefix', 'runcloud-hub');?></label>
                    <input type="text" id="redis_prefix" name="<?php self::view_fname('redis_prefix');?>" value="<?php self::view_fvalue('redis_prefix');?>" <?php self::view_fattr();?>>
                    <p class="mb-4 text-base-800 pt-2"><?php esc_html_e('Set custom prefix for object cache keys. Leave blank for auto generated prefix.', 'runcloud-hub');?></p>
                </div>
            <!-- /key-prefix -->

            <!-- ignored-groups -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="redis_ignored_groups_onn" name="<?php self::view_fname('redis_ignored_groups_onn');?>" value="1" <?php self::view_checked('redis_ignored_groups_onn');?>>
                    <label class="control-label" for="redis_ignored_groups_onn"><?php esc_html_e('Exclude Object Cache Groups', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="redis_ignored_groups_onn" data-parent-action="disabled" id="redis_ignored_groups_mch" name="<?php self::view_fname('redis_ignored_groups_mch');?>" placeholder="utm_source" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('redis_ignored_groups_mch'));?></textarea>
                </div>

                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Exclude object cache groups that should not be cached in Redis, one per line.', 'runcloud-hub');?></p>
            <!-- /ignored-groups -->

            <!-- maxttl -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="redis_maxttl_onn" name="<?php self::view_fname('redis_maxttl_onn');?>" value="1" <?php self::view_checked('redis_maxttl_onn');?>>
                    <label class="control-label" for="redis_maxttl_onn"><?php esc_html_e('Set maximum TTL (time-to-live) for cached objects', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <input class="mr-2 w-20 inline-block float-left" type="number" min="1" id="redis_maxttl_int" name="<?php self::view_fname('redis_maxttl_int');?>" value="<?php self::view_fvalue('redis_maxttl_int');?>" data-parent="redis_maxttl_onn" data-parent-action="disabled" disabled>
                    <select class="w-32 inline-block" id="redis_maxttl_unt" name="<?php self::view_fname('redis_maxttl_unt');?>" data-parent="redis_maxttl_onn" data-parent-action="disabled" disabled>
                        <?php self::view_timeduration_select(self::view_rvalue('redis_maxttl_unt'));?>
                    </select>
                </div>
            <!-- /maxttl -->
            </li>

        </ul>
    </fieldset>
</div>
<!-- /redis-cache-options -->

<!-- redis-debug -->
<div class="mb-6 display-none" data-tab-page="redis" data-tab-page-title="<?php esc_html_e('Redis Object Cache', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Debug Options', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul>
            <li>
                <div class="form-checkbox-setting ">
                    <input type="checkbox" data-action="disabled" id="redis_debug_onn" name="<?php self::view_fname('redis_debug_onn');?>" value="1" <?php self::view_checked('redis_debug_onn');?>>
                    <label class="control-label" for="redis_debug_onn"><?php esc_html_e('Enable Redis Object Cache stats in the footer', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 text-base-800"><strong><?php esc_html_e('For debug purpose only, not for production.', 'runcloud-hub');?></strong> <?php esc_html_e('Automatically show statistics of Redis object cache (hits, misses, size) in the footer, both admin and frontend, for administrator user only.', 'runcloud-hub');?></p>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /debug-purge -->

<?php endif; ?>
