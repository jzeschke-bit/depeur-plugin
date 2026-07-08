<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<!-- exclude-cache -->
<div class="mb-6 display-none" data-tab-page="runcache-rules" data-tab-page-title="<?php esc_html_e('RunCache Rules', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cache Exclusion Settings', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>

            <!-- url -->
                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="exclude_url_onn" name="<?php self::view_fname('exclude_url_onn');?>" value="1" <?php self::view_checked('exclude_url_onn');?>>
                    <label class="control-label" for="exclude_url_onn"><?php esc_html_e('Exclude URL Path', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="exclude_url_onn" data-parent-action="disabled" id="exclude_url_mch" name="<?php self::view_fname('exclude_url_mch');?>" placeholder="/checkout/" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('exclude_url_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Exclude page cache based on matching URL path, one per line.', 'runcloud-hub');?></p>
            <!-- /url -->

            <!-- cookie -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="exclude_cookie_onn" name="<?php self::view_fname('exclude_cookie_onn');?>" value="1" <?php self::view_checked('exclude_cookie_onn');?>>
                    <label class="control-label" for="exclude_cookie_onn"><?php esc_html_e('Exclude Cookie', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="exclude_cookie_onn" data-parent-action="disabled" id="exclude_cookie_mch" name="<?php self::view_fname('exclude_cookie_mch');?>" placeholder="tracking_" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('exclude_cookie_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Exclude page cache based on matching cookie name, one per line.', 'runcloud-hub');?></p>

                <button type="button" class="rc-toggle-title ml-8 mt-2 text-blue-900 focus:outline-none" style="font-size:13px;">
                    <?php esc_html_e('Check list of WordPress cookies', 'runcloud-hub');?>
                </button>
                <div class="rc-toggle-content ml-8" style="display:none;">
                    <p class="text-base-800 pt-2">
                        <?php esc_html_e('IMPORTANT: If you add the cookie below to exclude cookie list (except wordpress_sec_), then cache will be excluded for all pages once the cookie is detected in the visitor browser.', 'runcloud-hub');?>
                    </p>
                    <table class="table-fixed text-base-800 my-2" style="font-size:13px;">
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">wordpress_sec_[a-f0-9]+</code>
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(session cookie)', 'runcloud-hub');?>, <?php esc_html_e('store your authentication details, its use is limited to the Administration Screen area /wp-admin/ area.', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">wordpress_logged_in</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(session cookie)', 'runcloud-hub');?>, <?php esc_html_e('indicates when you are logged in, and who you are.', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">wp-postpass</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(persistent cookie, 10 days)', 'runcloud-hub');?>, <?php esc_html_e('used to maintain session if a post is password protected', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">comment_author</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(persistent cookie, 347 days)', 'runcloud-hub');?>, <?php esc_html_e('used to track comment author details, when "Save my name, email, and website in this browser for the next time I comment." checkbox is checked', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">woocommerce_items_in_cart</code>
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(session cookie)', 'runcloud-hub');?>, <?php esc_html_e('helps WooCommerce determine when cart contents/data changes.', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">woocommerce_cart_hash</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(session cookie)', 'runcloud-hub');?>, <?php esc_html_e('helps WooCommerce determine when cart contents/data changes.', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">wp_woocommerce_session</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(persistent cookie, 2 days)', 'runcloud-hub');?>, <?php esc_html_e('contains a unique code for each customer so that WooCommerce knows where to find the cart data in the database for each customer.', 'runcloud-hub');?>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 sm:w-1/3 border px-3 py-1">
                                <code class="inline-block sm:whitespace-no-wrap text-xs">woocommerce_recently_viewed</code> 
                            </td> 
                            <td class="w-1/2 sm:w-2/3 border px-3 py-1">
                                <?php esc_html_e('(session cookie)', 'runcloud-hub');?>, <?php esc_html_e('powers the WooCommerce "Recent Viewed Products" widget.', 'runcloud-hub');?>
                            </td>
                        </tr>
                    </table>
                </div>
            <!-- /cookie -->

            <!-- auto -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="exclude_auto_onn" name="<?php self::view_fname('exclude_auto_onn');?>" value="1" <?php self::view_checked('exclude_auto_onn');?>>
                    <label class="control-label" for="exclude_auto_onn"><?php echo wp_kses_post( __('Exclude <code>DONOTCACHEPAGE</code> constant', 'runcloud-hub') );?></label>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php echo wp_kses_post( __('Automatically exclude page cache when <code>DONOTCACHEPAGE</code> constant is detected by creating special <code>wordpress_no_cache</code> session cookie and <code>no-cache</code> cache-control header. It is useful for automatic plugin integration, WordPress multisite, and Cloudflare integration. <strong>Please clear all cache after enable/disable this option.</strong>', 'runcloud-hub') );?></p>
            <!-- /auto -->

            <!-- browser -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="exclude_browser_onn" name="<?php self::view_fname('exclude_browser_onn');?>" value="1" <?php self::view_checked('exclude_browser_onn');?>>
                    <label class="control-label" for="exclude_browser_onn"><?php esc_html_e('Exclude Browser User-Agent', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="exclude_browser_onn" data-parent-action="disabled" id="exclude_browser_mch" name="<?php self::view_fname('exclude_browser_mch');?>" placeholder="googlebot" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('exclude_browser_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Exclude page cache based on matching browser user-agent, one per line.', 'runcloud-hub');?></p>
            <!-- /browser -->

            <!-- visitorip -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="exclude_visitorip_onn" name="<?php self::view_fname('exclude_visitorip_onn');?>" value="1" <?php self::view_checked('exclude_visitorip_onn');?>>
                    <label class="control-label" for="exclude_visitorip_onn"><?php esc_html_e('Exclude Visitor IP Address', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="exclude_visitorip_onn" data-parent-action="disabled" id="exclude_visitorip_mch" name="<?php self::view_fname('exclude_visitorip_mch');?>" placeholder="8.8.8.8" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('exclude_visitorip_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Exclude page cache based on matching visitor IP address, one per line.', 'runcloud-hub');?></p>
            <!-- /visitorip -->

            </li>
        </ul>
    </fieldset>
</div>
<!-- /exclude-cache -->

<!-- query-cache -->
<div class="mb-6 display-none" data-tab-page="runcache-rules" data-tab-page-title="<?php esc_html_e('RunCache Rules', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cache Query String Settings', 'runcloud-hub');?></h3>
    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <p class="text-base-800 etxt-xl"><?php esc_html_e('By default a page with query string will not be cached.', 'runcloud-hub');?></p>
            <!-- ignore-query -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="ignore_query_onn" name="<?php self::view_fname('ignore_query_onn');?>" value="1" <?php self::view_checked('ignore_query_onn');?>>
                    <label class="control-label" for="ignore_query_onn"><?php esc_html_e('Ignore Cache Query String', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="ignore_query_onn" data-parent-action="disabled" id="ignore_query_mch" name="<?php self::view_fname('ignore_query_mch');?>" placeholder="utm_source" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('ignore_query_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Ignore query string, one per line. Page cache will be served when all query strings on the current page are in the list of ignored query strings.', 'runcloud-hub');?></p>
            <!-- /ignore-query -->
            <!-- allow-query -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="allow_query_onn" name="<?php self::view_fname('allow_query_onn');?>" value="1" <?php self::view_checked('allow_query_onn');?>>
                    <label class="control-label" for="allow_query_onn"><?php esc_html_e('Include Cache Query String', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="allow_query_onn" data-parent-action="disabled" id="allow_query_mch" name="<?php self::view_fname('allow_query_mch');?>" placeholder="country" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('allow_query_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Allow cache based on matching query string, one per line. Page cached will be served with a separate cache file for each value of allowed query string.', 'runcloud-hub');?></p>
            <!-- /allow-query -->
            <!-- exlude-query -->
                <div class="form-checkbox-setting mt-6">
                    <input type="checkbox" data-action="disabled" id="exclude_query_onn" name="<?php self::view_fname('exclude_query_onn');?>" value="1" <?php self::view_checked('exclude_query_onn');?>>
                    <label class="control-label" for="exclude_query_onn"><?php esc_html_e('Exclude Cache Query String', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="exclude_query_onn" data-parent-action="disabled" id="exclude_query_mch" name="<?php self::view_fname('exclude_query_mch');?>" placeholder="nocache" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('exclude_query_mch'));?></textarea>
                </div>
                <p class="ml-8 text-base-800 pt-2"><?php esc_html_e('Prevent page cache when matching query string exists, one per line.', 'runcloud-hub');?></p>
            <!-- /exlude-query -->
            </li>
        </ul>
    </fieldset>
</div>
<!-- /query-cache -->

<!-- cache-key -->
<div class="mb-6 display-none" data-tab-page="runcache-rules" data-tab-page-title="<?php esc_html_e('RunCache Rules', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Additional Cache Key', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="cache_key_extra_onn" name="<?php self::view_fname('cache_key_extra_onn');?>" value="1" <?php self::view_checked('cache_key_extra_onn');?>>
                    <label class="control-label" for="cache_key_extra_onn"><?php esc_html_e('Set Additional Cache Key', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <input type="text" data-parent="cache_key_extra_onn" data-parent-action="disabled" id="cache_key_extra_var" name="<?php self::view_fname('cache_key_extra_var');?>" value="<?php self::view_fvalue('cache_key_extra_var');?>" <?php self::view_fattr();?> disabled>
                </div>
                <p class="pt-2 ml-8 text-base-800"><?php esc_html_e('Enable this option to add additional cache keys. The input should be a recognized and valid NGINX variables.', 'runcloud-hub');?></p>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /cache-key -->
