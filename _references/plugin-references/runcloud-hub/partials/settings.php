<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<!-- runcloud-api -->
<div class="mb-6 display-none" data-tab-page="setting" data-tab-page-title="<?php esc_html_e('Settings','runcloud-hub');?>">
     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="py-2">
            <li>
				<div class="form-group">
				    <label class="control-label" for="rcapi_key"><?php esc_html_e('HUB API Key', 'runcloud-hub');?></label>
				    <input type="text" id="rcapi_key" name="<?php self::view_fname('rcapi_key');?>" value="<?php self::view_fvalue('rcapi_key');?>" <?php self::view_fattr();?>>
				</div>

				<div class="form-group">
				    <label class="control-label" for="rcapi_secret"><?php esc_html_e('HUB API Secret', 'runcloud-hub');?></label>
				    <input type="text" id="rcapi_secret" name="<?php self::view_fname('rcapi_secret');?>" value="<?php self::view_fvalue('rcapi_secret');?>" <?php self::view_fattr();?>>
				</div>

				<div class="form-group mb-0 pb-0">
				    <label class="control-label" for="rcapi_webapp_id"><?php esc_html_e('WebApp ID', 'runcloud-hub');?></label>
				    <input type="number" min="1" id="rcapi_webapp_id" name="<?php self::view_fname('rcapi_webapp_id');?>" value="<?php self::view_fvalue('rcapi_webapp_id');?>" <?php self::view_fattr();?>>
				</div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /runcloud-api -->

<!-- stats -->
<div class="mb-6 display-none" data-tab-page="setting" data-tab-page-title="<?php esc_html_e('Settings', 'runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Stats Panel', 'runcloud-hub');?></h3>

    <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul class="pt-2 pb-4">
            <li>
                <div class="form-checkbox-setting ">
                    <input type="checkbox" data-action="disabled" id="stats_onn" name="<?php self::view_fname('stats_onn');?>" value="1" <?php self::view_checked('stats_onn');?>>
                    <label class="control-label" for="stats_onn"><?php esc_html_e('Enable Stats Panel', 'runcloud-hub');?></label>
                </div>

                <p class="pt-4 ml-8 mb-0"><?php esc_html_e('Automatically update stats data every scheduled time interval below.', 'runcloud-hub');?></p>

                <div class="ml-8">
                    <input class="mr-2 w-20 inline-block float-left" type="number" min="1" id="stats_schedule_int" name="<?php self::view_fname('stats_schedule_int');?>" value="<?php self::view_fvalue('stats_schedule_int');?>" data-parent="stats_onn" data-parent-action="disabled" disabled>
                    <select class="w-32 inline-block" id="stats_schedule_unt" name="<?php self::view_fname('stats_schedule_unt');?>" data-parent="stats_onn" data-parent-action="disabled" disabled>
                        <?php self::view_timeduration_select(self::view_rvalue('stats_schedule_unt'), ['minute','week','year']);?>
                    </select>

                    <div class="form-checkbox-setting mt-6">
                        <input type="checkbox" data-action="disabled" id="stats_health_onn" name="<?php self::view_fname('stats_health_onn');?>" value="1" <?php self::view_checked('stats_health_onn');?> data-parent="stats_onn" data-parent-action="disabled" disabled>
                        <label class="control-label" for="stats_health_onn"><?php esc_html_e('Display Stats of Server Health Data', 'runcloud-hub');?></label>
                    </div>

                    <select class="ml-8 w-32" id="stats_health_var" name="<?php self::view_fname('stats_health_var');?>" data-parent="stats_health_onn" data-parent-action="disabled" disabled>
                        <?php self::view_stats_select(self::view_rvalue('stats_health_var'), 'monthly');?>
                    </select>

                    <div class="form-checkbox-setting mt-6">
                        <input type="checkbox" data-action="disabled" id="stats_transfer_onn" name="<?php self::view_fname('stats_transfer_onn');?>" value="1" <?php self::view_checked('stats_transfer_onn');?> data-parent="stats_onn" data-parent-action="disabled" disabled>
                        <label class="control-label" for="stats_transfer_onn"><?php esc_html_e('Display Stats of Web Traffic Data', 'runcloud-hub');?></label>
                    </div>

                    <select class="ml-8 w-32" id="stats_transfer_var" name="<?php self::view_fname('stats_transfer_var');?>" data-parent="stats_transfer_onn" data-parent-action="disabled" disabled>
                        <?php self::view_stats_select(self::view_rvalue('stats_transfer_var'), 'hourly');?>
                    </select>

                </div>
            </li>
        </ul>
    </fieldset>
</div>
<!-- /stats -->

<!-- allow-cloudflare -->
<div class="mb-6 display-none" data-tab-page="setting" data-tab-page-title="<?php esc_html_e('Settings','runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Cloudflare Integration', 'runcloud-hub');?> <span class="font-semibold inline-block px-2 py-1 text-xs rounded-full text-blue-700 bg-blue-200">Beta</span></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul>

            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" id="allow_cloudflare_onn" name="<?php self::view_fname('allow_cloudflare_onn');?>" value="1" <?php self::view_checked('allow_cloudflare_onn');?>>
                    <label class="control-label" for="allow_cloudflare_onn"><?php esc_html_e('Allow Cloudflare Integration', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 text-base-800"><?php esc_html_e('Enable this option to allow Cloudflare integration when your site is behind Cloudflare.', 'runcloud-hub');?></p>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /allow-cloudflare -->

<!-- magic-link -->
<div class="mb-6 display-none" data-tab-page="setting" data-tab-page-title="<?php esc_html_e('Settings','runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Magic Link Login', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul>

            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" id="rcapi_magiclink_onn" name="<?php self::view_fname('rcapi_magiclink_onn');?>" value="1" <?php self::view_checked('rcapi_magiclink_onn');?>>
                    <label class="control-label" for="rcapi_magiclink_onn"><?php esc_html_e('Allow Magic Link Login', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 text-base-800"><?php esc_html_e('Enable this option to allow login automatically from RunCloud Panel.', 'runcloud-hub');?></p>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /magic-link -->

<!-- beta-tester -->
<div class="mb-6 display-none" data-tab-page="setting" data-tab-page-title="<?php esc_html_e('Settings','runcloud-hub');?>">
    <h3 class="pb-4 text-xl font-bold text-base-1000 leading-tight"><?php esc_html_e('Beta Tester', 'runcloud-hub');?></h3>

     <fieldset class="px-6 py-4 bg-white rounded-sm shadow rci-field">
        <ul>

            <li>
                <div class="form-checkbox-setting">
                    <input type="checkbox" id="beta_tester_onn" name="<?php self::view_fname('beta_tester_onn');?>" value="1" <?php self::view_checked('beta_tester_onn');?>>
                    <label class="control-label" for="beta_tester_onn"><?php esc_html_e('Enable Beta Tester', 'runcloud-hub');?></label>
                </div>

                <p class="pt-1 ml-8 text-base-800"><?php esc_html_e('Become a Beta Tester and stay in the loop about the latest beta versions of RunCloud Hub. Rest assured, the beta update will not automatically install on your website. You will always have the option to either proceed with the beta version or ignore it.', 'runcloud-hub');?></p>
            </li>

        </ul>
    </fieldset>
</div>
<!-- /beta-tester -->
