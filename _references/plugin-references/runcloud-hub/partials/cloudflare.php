<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<?php $cf = get_option(self::$db_cloudflare); ?>
<?php $hostname = sanitize_text_field($_SERVER['SERVER_NAME']); ?>

<?php if (!empty($cf['message'])) : ?>
    <!-- cloudflare-status -->
    <div class="mb-6 display-none" data-tab-page="cloudflare" data-tab-page-title="<?php esc_html_e('Cloudflare','runcloud-hub');?>">
        <?php if (!empty($cf['zoneid'])) : ?>
            <?php if (isset($cf['hostname']) && $cf['hostname'] !== $hostname) : ?>
                <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php printf( esc_html__('Cloudflare integration is disabled because saved Cloudflare configuration (%s) does not match with the current domain (%s). If you just changed the domain or clone/migrate this website, please re-configure Cloudflare integration.', 'runcloud-hub'), '<strong>'.$cf['hostname'].'</strong>', '<strong>'.$hostname.'</strong>' );?></p>
            <?php else : ?>
                <p class="px-6 py-3 bg-blue-100 border border-blue-300 text-blue-700 rounded-sm shadow"><?php echo wp_kses_post($cf['message']);?></p>
            <?php endif; ?>
        <?php else : ?>
            <p class="px-6 py-3 bg-red-100 border border-red-300 text-red-700 rounded-sm shadow"><?php echo wp_kses_post($cf['message']);?></p>
        <?php endif; ?>
    </div>
    <!-- /cloudflare-status -->
<?php endif; ?>

<!-- cloudflare-enable -->
<div class="mb-6 display-none" data-tab-page="cloudflare" data-tab-page-title="<?php esc_html_e('Cloudflare','runcloud-hub');?>">
     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="cloudflare_onn" name="<?php self::view_fname('cloudflare_onn');?>" value="1" <?php self::view_checked('cloudflare_onn');?>>
                    <label class="control-label" for="cloudflare_onn"><?php esc_html_e('Enable Cloudflare Integration', 'runcloud-hub');?></label>
                </div>
                <?php if (empty($cf['message'])) : ?>
                    <p class="text-base-800 ml-8 pt-2"><?php esc_html_e('If your site is behind Cloudflare, you can enable Cloudflare integration to allow you to enable/disable Cloudflare page cache and purge Cloudflare cache from WordPress dashboard.', 'runcloud-hub');?></p>
                <?php endif; ?>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /cloudflare-enable -->

<?php if (self::cloudflare_is_enabled()) : ?>

<!-- cloudflare-settings -->
<div class="mb-6 display-none" data-tab-page="cloudflare" data-tab-page-title="<?php esc_html_e('Cloudflare','runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cloudflare Purge Cache', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <p class="text-base-800 mb-4"><?php esc_html_e('Please use this option when you use Cache Everything on Cloudflare, to allow you to automatically clear Cloudflare cache when you clear NGINX cache.', 'runcloud-hub');?></p>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" data-action="disabled" id="cloudflare_purgeall_onn" name="<?php self::view_fname('cloudflare_purgeall_onn');?>" value="1" <?php self::view_checked('cloudflare_purgeall_onn');?>>
                    <label class="control-label" for="cloudflare_purgeall_onn"><?php esc_html_e('Automatically purge all Cloudflare cache after purge all NGINX cache.', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" data-action="disabled" id="cloudflare_purgeurl_onn" name="<?php self::view_fname('cloudflare_purgeurl_onn');?>" value="1" <?php self::view_checked('cloudflare_purgeurl_onn');?>>
                    <label class="control-label" for="cloudflare_purgeurl_onn"><?php esc_html_e('Automatically purge Cloudflare cache of a post/page/URL after purge the NGINX cache.', 'runcloud-hub');?></label>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /cloudflare-settings -->

<!-- cloudflare-actions -->
<div class="mb-6 display-none" data-tab-page="cloudflare" data-tab-page-title="<?php esc_html_e('Cloudflare', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cloudflare Actions', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <div class="md:flex md:justify-between">
                    <div>
                        <h4><?php esc_html_e('Test Cloudflare Page Cache', 'runcloud-hub');?></h4>
                        <p class="text-base-800"><?php esc_html_e('Check if your website is behind Cloudflare and CF page caching is enabled.', 'runcloud-hub');?></p>
                    </div>
                    <div>
                        <p class="text-base-800 mt-2 md:mt-0"><a href="<?php self::view_action_link('checkcfcache');?>#cloudflare" class="button button-secondary mr-0 md:ml-6"><?php esc_html_e('Test Page Cache', 'runcloud-hub');?></a></p>
                    </div>
                </div>
            </li>
            <li>
                <div class="md:flex md:justify-between">
                    <div>
                        <h4><?php esc_html_e('Check Cloudflare APO for WordPress', 'runcloud-hub');?></h4>
                        <p class="text-base-800"><?php esc_html_e('Check if APO is active in your website. APO (Automatic Platform Optimization) is a premium Cloudflare feature that will serves your WordPress site from Cloudflare\' edge network and caches third party fonts.', 'runcloud-hub');?></p>
                    </div>
                    <div>
                        <p class="text-base-800 mt-2 md:mt-0"><a href="<?php self::view_action_link('checkcfapo');?>#cloudflare" class="button button-secondary mr-0 md:ml-6"><?php esc_html_e('Check APO Status', 'runcloud-hub');?></a></p>
                    </div>
                </div>
            </li>
            <li>
                <div class="md:flex md:justify-between">
                    <div>
                        <h4><?php esc_html_e('Check Cloudflare Development Mode', 'runcloud-hub');?></h4>
                        <p class="text-base-800"><?php esc_html_e('Check if your website is on Cloudflare Development Mode and CF cache is bypassed.', 'runcloud-hub');?></p>
                    </div>
                    <div>
                        <p class="text-base-800 mt-2 md:mt-0"><a href="<?php self::view_action_link('checkcfdev');?>#cloudflare" class="button button-secondary mr-0 md:ml-6"><?php esc_html_e('Check Dev Mode', 'runcloud-hub');?></a></p>
                    </div>
                </div>
            </li>
            <li>
                <div class="md:flex md:justify-between">
                    <div>
                        <h4><?php esc_html_e('Purge Cloudflare Cache', 'runcloud-hub');?></h4>
                        <p class="text-base-800"><?php esc_html_e('Clear Cloudflare cached files to force Cloudflare to fetch a fresh version.', 'runcloud-hub');?></p>
                    </div>
                    <div>
                        <p class="text-base-800 mt-2 md:mt-0"><a href="<?php self::view_action_link('purgecfall');?>#cloudflare" class="button button-primary mr-0 md:ml-6"><?php esc_html_e('Purge Everything', 'runcloud-hub');?></a></p>
                    </div>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /cloudflare-actions -->

<?php endif; ?>

<!-- cloudflare-settings -->
<div class="mb-6 display-none" data-tab-page="cloudflare" data-tab-page-title="<?php esc_html_e('Cloudflare','runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cloudflare Settings', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <div class="form-group">
                    <label class="control-label" for="cloudflare_apikey"><?php esc_html_e('Cloudflare API Token or Global API Key', 'runcloud-hub');?></label>
                    <input type="password" id="cloudflare_apikey" name="<?php self::view_fname('cloudflare_apikey');?>" value="<?php self::view_fvalue('cloudflare_apikey');?>" <?php self::view_fattr();?>>
                </div>

                <div class="form-group pb-0">
                    <label class="control-label" for="cloudflare_email"><?php esc_html_e('Cloudflare Email', 'runcloud-hub');?></label>
                    <input type="text" id="cloudflare_email" name="<?php self::view_fname('cloudflare_email');?>" value="<?php self::view_fvalue('cloudflare_email');?>" <?php self::view_fattr();?>>
                    <p class="text-base-800 pt-2"><?php esc_html_e('Note: Cloudflare email is optional for API token.', 'runcloud-hub');?></p>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /cloudflare-settings -->
