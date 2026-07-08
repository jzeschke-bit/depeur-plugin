<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<!-- purge-status -->
<?php 
if (self::is_srcache()) {
    $cache_type = esc_html__('Redis Full-Page Caching', 'runcloud-hub');
    $cache_switch = esc_html__('FastCGI/Proxy Page Caching', 'runcloud-hub');
}
else {
    $cache_type = esc_html__('FastCGI/Proxy Page Caching', 'runcloud-hub');
    $cache_switch = esc_html__('Redis Full-Page Caching', 'runcloud-hub');
}
?>
<div class="mb-6 px-6 py-2 bg-blue-100 border border-blue-300 text-blue-700 rounded-sm shadow display-none" data-tab-page="runcache" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <p class="leading-loose">
        <?php esc_html_e('Current NGINX Page Caching Method:', 'runcloud-hub');?> <strong><?php echo esc_html( $cache_type ); ?></strong>
    </p>
    <!-- 
    <p class="pt-1 text-base-800">
        <?php echo esc_html( sprintf( esc_html__( 'If this information is not correct and %s is installed in the server,', 'runcloud-hub'), $cache_switch) ) ;?> 
        <br/><a href="<?php self::view_action_link('switchpurger');?>#runcache"><span class="text-red-800"><?php echo esc_html( sprintf( esc_html__( 'Switch Cache Purger To %s', 'runcloud-hub'), $cache_switch) ) ;?></span></a>
    </p>
    -->
</div>
<!-- /purge-status -->

<!-- purge-all -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Purge All Cache', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1">

            <li>
                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_permalink_onn" name="<?php self::view_fname('purge_permalink_onn');?>" value="1" <?php self::view_checked('purge_permalink_onn');?>>
                    <label class="control-label" for="purge_permalink_onn"><?php esc_html_e('when custom permalink structure is changed', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_theme_switch_onn" name="<?php self::view_fname('purge_theme_switch_onn');?>" value="1" <?php self::view_checked('purge_theme_switch_onn');?>>
                    <label class="control-label" for="purge_theme_switch_onn"><?php esc_html_e('when switch to a different theme', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_general_onn" name="<?php self::view_fname('purge_general_onn');?>" value="1" <?php self::view_checked('purge_permalink_onn');?>>
                    <label class="control-label" for="purge_general_onn"><?php esc_html_e('when site title / tagline / WordPress address / site address is changed', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_customize_onn" name="<?php self::view_fname('purge_customize_onn');?>" value="1" <?php self::view_checked('purge_customize_onn');?>>
                    <label class="control-label" for="purge_customize_onn"><?php esc_html_e('when customizer is saved/updated  (Appearance-Customize)', 'runcloud-hub');?></label>
                </div>

                <?php if ( self::is_main_site() ) : ?>
                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_upgrader_onn" name="<?php self::view_fname('purge_upgrader_onn');?>" value="1" <?php self::view_checked('purge_upgrader_onn');?>>
                    <label class="control-label" for="purge_upgrader_onn"><?php esc_html_e('when a theme/plugin is updated', 'runcloud-hub');?></label>
                </div>
                <?php endif; ?>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_plugin_onn" name="<?php self::view_fname('purge_plugin_onn');?>" value="1" <?php self::view_checked('purge_plugin_onn');?>>
                    <label class="control-label" for="purge_plugin_onn"><?php esc_html_e('when a plugin is activated/deactivated', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_navmenu_onn" name="<?php self::view_fname('purge_navmenu_onn');?>" value="1" <?php self::view_checked('purge_navmenu_onn');?>>
                    <label class="control-label" for="purge_navmenu_onn"><?php esc_html_e('when a nav menu is updated', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_sidebar_onn" name="<?php self::view_fname('purge_sidebar_onn');?>" value="1" <?php self::view_checked('purge_sidebar_onn');?>>
                    <label class="control-label" for="purge_sidebar_onn"><?php esc_html_e('when a sidebar widget is updated', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="purge_widget_onn" name="<?php self::view_fname('purge_widget_onn');?>" value="1" <?php self::view_checked('purge_widget_onn');?>>
                    <label class="control-label" for="purge_widget_onn"><?php esc_html_e('when a widget parameter is updated', 'runcloud-hub');?></label>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /purge-homepage -->

<!-- purge-homepage -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Purge Homepage', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1">
            <li>
                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="homepage_post_onn" name="<?php self::view_fname('homepage_post_onn');?>" value="1" <?php self::view_checked('homepage_post_onn');?>>
                    <label class="control-label" for="homepage_post_onn"><?php esc_html_e('when a post/page/CPT is published/modified', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="homepage_removed_onn" name="<?php self::view_fname('homepage_removed_onn');?>" value="1" <?php self::view_checked('homepage_removed_onn');?>>
                    <label class="control-label" for="homepage_removed_onn"><?php esc_html_e('when a published post/page/CPT is trashed', 'runcloud-hub');?></label>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /purge-homepage -->

<!-- purge-content -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Purge Post/Page/CPT', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1">
            <li>
                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="content_publish_onn" name="<?php self::view_fname('content_publish_onn');?>" value="1" <?php self::view_checked('content_publish_onn');?>>
                    <label class="control-label" for="content_publish_onn"><?php esc_html_e('when a post/page/CPT is published/modified/trashed', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" id="content_comment_approved_onn" name="<?php self::view_fname('content_comment_approved_onn');?>" value="1" <?php self::view_checked('content_comment_approved_onn');?>>
                    <label class="control-label" for="content_comment_approved_onn"><?php esc_html_e('when a comment is approved/unapproved/trashed', 'runcloud-hub');?></label>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /purge-content -->

<!-- purge-archives -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Purge Archives', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1">
            <li>
                <div class="form-checkbox-setting mb-2">
                    <input type="checkbox" data-action="disabled" id="archives_content_onn" name="<?php self::view_fname('archives_content_onn');?>" value="1" <?php self::view_checked('archives_content_onn');?>>
                    <label class="control-label" for="archives_content_onn"><?php esc_html_e('when a post/page/CPT is published/modified/trashed', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2 ml-8">
                    <input type="checkbox" data-action="disabled" id="archives_cpt_onn" name="<?php self::view_fname('archives_cpt_onn');?>" value="1" <?php self::view_checked('archives_cpt_onn');?> data-parent="archives_content_onn" data-parent-action="disabled" disabled>
                    <label class="control-label" for="archives_cpt_onn"><?php esc_html_e('purge post type archive page', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2 ml-8">
                    <input type="checkbox" data-action="disabled" id="archives_category_onn" name="<?php self::view_fname('archives_category_onn');?>" value="1" <?php self::view_checked('archives_category_onn');?> data-parent="archives_content_onn" data-parent-action="disabled" disabled>
                    <label class="control-label" for="archives_category_onn"><?php esc_html_e('purge category/tag/taxonomy pages', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2 ml-8">
                    <input type="checkbox" data-action="disabled" id="archives_author_onn" name="<?php self::view_fname('archives_author_onn');?>" value="1" <?php self::view_checked('archives_author_onn');?> data-parent="archives_content_onn" data-parent-action="disabled" disabled>
                    <label class="control-label" for="archives_author_onn"><?php esc_html_e('purge author page', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting mb-2 ml-8">
                    <input type="checkbox" data-action="disabled" id="archives_feed_onn" name="<?php self::view_fname('archives_feed_onn');?>" value="1" <?php self::view_checked('archives_feed_onn');?> data-parent="archives_content_onn" data-parent-action="disabled" disabled>
                    <label class="control-label" for="archives_feed_onn"><?php esc_html_e('purge RSS feed page', 'runcloud-hub');?></label>
                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /purge-archives -->

<!-- purge-url-path -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Custom Purge URL', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1 mb-2">
            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="url_path_onn" name="<?php self::view_fname('url_path_onn');?>" value="1" <?php self::view_checked('url_path_onn');?>>
                    <label class="control-label" for="url_path_onn"><?php esc_html_e('Enable custom purge URL path', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="url_path_onn" data-parent-action="disabled" id="url_path_mch" name="<?php self::view_fname('url_path_mch');?>" placeholder="/login/" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('url_path_mch'));?></textarea>
                </div>

                <p class="pt-2 ml-8 text-base-800"><?php esc_html_e('Automatically clear cache of matching URL path, one per line, after we run purge post/page/CPT cache.', 'runcloud-hub');?></p>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /purge-url-path -->

<!-- auto-purge -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Scheduled Purge', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1 mb-2">
            <li>
                <p class="pt-2 mb-6 text-base-800"><?php esc_html_e('Scheduled purge works on the WordPress-side and it could be useful if only you have long "Cache Lifespan" value on the RunCache settings in your server.', 'runcloud-hub');?></p>

                <div class="form-checkbox-setting ">
                    <input type="checkbox" data-action="disabled" id="schedule_purge_onn" name="<?php self::view_fname('schedule_purge_onn');?>" value="1" <?php self::view_checked('schedule_purge_onn');?>>
                    <label class="control-label" for="schedule_purge_onn"><?php esc_html_e('Enable Scheduled Purge', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 mb-0 text-base-800"><?php esc_html_e('Automatically clear all cache every scheduled time interval below.', 'runcloud-hub');?></p>

                <div class="ml-8">
                    <input class="mr-2 w-20 inline-block float-left" type="number" min="1" id="schedule_purge_int" name="<?php self::view_fname('schedule_purge_int');?>" value="<?php self::view_fvalue('schedule_purge_int');?>" data-parent="schedule_purge_onn" data-parent-action="disabled" disabled>
                    <select class="w-32 inline-block" id="schedule_purge_unt" name="<?php self::view_fname('schedule_purge_unt');?>" data-parent="schedule_purge_onn" data-parent-action="disabled" disabled>
                        <?php self::view_timeduration_select(self::view_rvalue('schedule_purge_unt'));?>
                    </select>
                </div>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /auto-purge -->

<!-- debug-purge -->
<div class="mb-6 display-none" data-tab-page="runcache-purger" data-tab-page-title="<?php esc_html_e('RunCache Purger', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Debug Options', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1 mb-2">
            <li>
                <div class="form-checkbox-setting ">
                    <input type="checkbox" data-action="disabled" id="html_footprint_onn" name="<?php self::view_fname('html_footprint_onn');?>" value="1" <?php self::view_checked('html_footprint_onn');?>>
                    <label class="control-label" for="html_footprint_onn"><?php esc_html_e('Enable HTML Footprint in HTML', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 text-base-800"><strong><?php esc_html_e('For debug purpose only, not for production.', 'runcloud-hub');?></strong> <?php esc_html_e('Automatically add RunCloud Hub HTML footprint at the bottom of HTML output. Please clear all cache after enabling this option.', 'runcloud-hub');?></p>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /debug-purge -->
