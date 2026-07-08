<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<!-- preload_post_onn -->
<div class="mb-6 display-none" data-tab-page="runcache-preload" data-tab-page-title="<?php esc_html_e('RunCache Preload', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Preload Post/Page/CPT Cache', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <p class="text-base-800 mb-4"><?php esc_html_e('Preload  post/page cache will generate the cache of a single post/page after purge post/page/CPT is triggered, for example after a post/page/CPT is published/updated.', 'runcloud-hub');?></p>

                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="preload_post_onn" name="<?php self::view_fname('preload_post_onn');?>" value="1" <?php self::view_checked('preload_post_onn');?>>
                    <label class="control-label" for="preload_post_onn"><?php esc_html_e('Automatically preload cache of a single post/page/CPT after purge post/page/CPT cache', 'runcloud-hub');?></label>
                </div>
            </li>
         </ul>
    </fieldset>
</div>
<!-- /preload_post_onn -->

<!-- preload_onn -->
<div class="mb-6 display-none" data-tab-page="runcache-preload" data-tab-page-title="<?php esc_html_e('RunCache Preload', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Preload All Cache', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
                <p class="text-base-800 mb-4"><?php esc_html_e('Preload all cache will generate the cache of all pages, starting from homepage and custom preload path URL (if any), followed by all posts, pages, and public custom post types.', 'runcloud-hub');?></p>

                <div class="mb-6">
                    <div class="label mt-4"><?php esc_html_e('Preload Speed', 'runcloud-hub');?></div>
                    <input class="mr-1 w-20 inline-block" type="number" min="1" max="120" placeholder="60" id="preload_speed" name="<?php self::view_fname('preload_speed');?>" value="<?php self::view_fvalue('preload_speed');?>"> <span class="text-base-800"><?php esc_html_e('posts/pages/CPT per minute', 'runcloud-hub');?></span>
                </div>

                <div class="form-checkbox-setting mb-6">
                    <input type="checkbox" data-action="disabled" id="preload_onn" name="<?php self::view_fname('preload_onn');?>" value="1" <?php self::view_checked('preload_onn');?>>
                    <label class="control-label" for="preload_onn"><?php esc_html_e('Automatically run preload all cache for homepage and all posts/pages after purge all cache', 'runcloud-hub');?></label>
                </div>

                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="preload_path_onn" name="<?php self::view_fname('preload_path_onn');?>" value="1" <?php self::view_checked('preload_path_onn');?>>
                    <label class="control-label" for="preload_path_onn"><?php esc_html_e('Enable custom preload path URL', 'runcloud-hub');?></label>
                </div>

                <div class="ml-8">
                    <textarea data-parent="preload_path_onn" data-parent-action="disabled" id="preload_path_mch" name="<?php self::view_fname('preload_path_mch');?>" placeholder="/login/" <?php self::view_fattr();?> disabled><?php echo sanitize_textarea_field(self::view_rvalue('preload_path_mch'));?></textarea>
                </div>

                <p class="pt-2 ml-8 text-base-800"><?php esc_html_e('Automatically preload cache of matching URL path, one per line, when we run preload all cache.', 'runcloud-hub');?></p>
            </li>
         </ul>
    </fieldset>
</div>
<!-- /preload_onn -->

<!-- preload_schedule_onn -->
<div class="mb-6 display-none" data-tab-page="runcache-preload" data-tab-page-title="<?php esc_html_e('RunCache Preload', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Scheduled Preload All Cache', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-1 mb-2">
            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" data-action="disabled" id="preload_schedule_onn" name="<?php self::view_fname('preload_schedule_onn');?>" value="1" <?php self::view_checked('preload_schedule_onn');?>>
                    <label class="control-label" for="preload_schedule_onn"><?php esc_html_e('Enable Scheduled Preload All Cache', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 mb-0 text-base-800"><?php esc_html_e('Automatically run cache preload for homepage and all posts/pages every scheduled time interval below.', 'runcloud-hub');?></p>

                <div class="ml-8">
                    <input class="mr-2 w-20 inline-block float-left" type="number" min="1" id="preload_schedule_int" name="<?php self::view_fname('preload_schedule_int');?>" value="<?php self::view_fvalue('preload_schedule_int');?>" data-parent="preload_schedule_onn" data-parent-action="disabled" disabled>
                    <select class="w-32 inline-block" id="preload_schedule_unt" name="<?php self::view_fname('preload_schedule_unt');?>" data-parent="preload_schedule_onn" data-parent-action="disabled" disabled>
                        <?php self::view_timeduration_select(self::view_rvalue('preload_schedule_unt'));?>
                    </select>
                </div>

            </li>
        </ul>
    </fieldset>
</div>
<!-- /preload_schedule_onn -->
