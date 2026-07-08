<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<?php if (is_multisite() && is_super_admin()): ?>
    <h4 class="mb-4 text-l font-semibold"><?php esc_html_e('Network Site Switching', 'runcloud-hub'); ?></h4>
    <div>
        <select id="rcsite_switch" name="rcsite_switch" class="p-1 pl-2 pr-6">
            <?php self::view_site_switch();?>
        </select>
        <p class="pt-2 mb-4 text-base-800"><?php esc_html_e('Automatically switch to RunCloud Hub settings page of the network site, for Super Administrator only.', 'runcloud-hub');?></p>
    </div>
<?php endif; ?>

<?php if (is_network_admin()): ?>
    <h4 class="mb-4 text-l font-semibold"><?php esc_html_e('Quick Network Action', 'runcloud-hub'); ?></h4>
    <div>
        <ul>
            <li>
                <a href="<?php self::view_action_link('purgesiteall');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Clear All Sites Cache', 'runcloud-hub'); ?></a>
            </li>
            <?php if (self::redis_is_connect() && self::is_dropin_active() && self::redis_is_enabled()): ?>
            <li>
                <a href="<?php self::view_action_link('purgeredisall');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Clear All Sites Redis Object Cache', 'runcloud-hub'); ?></a>
            </li>
            <?php endif;?>
        </ul>
    </div>

    <h4 class="mt-4 mb-4 text-l font-semibold"><?php esc_html_e('Quick Site Action', 'runcloud-hub'); ?></h4>
<?php else: ?>
    <h4 class="mb-4 text-l font-semibold"><?php esc_html_e('Quick Action', 'runcloud-hub'); ?></h4>
<?php endif; ?>

<div>
    <ul>
        <li>
            <a href="<?php self::view_action_link('purgeall');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Clear All Cache', 'runcloud-hub'); ?></a>
        </li>

        <li>
            <a href="<?php self::view_action_link('purgehomepage');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Clear Homepage Cache', 'runcloud-hub'); ?></a>
        </li>

        <?php if (self::redis_is_connect() && self::is_dropin_active()): ?>
            <li>
                <a href="<?php self::view_action_link('purgeredis');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Clear Redis Object Cache', 'runcloud-hub'); ?></a>
            </li>
        <?php endif;?>
        <li>
            <a href="<?php self::view_action_link('preload');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Run Preload All Cache', 'runcloud-hub'); ?></a>
        </li>
        <?php if (self::stats_is_enabled() && self::is_main_site() && !self::is_subdirectory() && !self::is_client_mode()): ?>
            <li>
                <a href="<?php self::view_action_link('fetchstats');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Fetch Server Stats', 'runcloud-hub'); ?></a>
            </li>
            <li>
                <a href="<?php self::view_action_link('rcapipush');?>" class="run-action block py-2 outline-none hover:text-base-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Push Server Cache Rules', 'runcloud-hub'); ?></a>
            </li>
        <?php endif;?>
        <?php if ( self::redis_is_connect() ): ?>
            <?php if ( self::is_dropin_need_install() && self::redis_is_enabled() ): ?>
                <li>
                    <a href="<?php self::view_action_link('installdropin');?>" class="run-action block py-2 outline-none hover:text-base-1000 text-red-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Install Object Cache Drop-in', 'runcloud-hub'); ?></a>
                </li>
            <?php elseif ( self::is_dropin_need_update() && self::redis_is_enabled() ): ?>
                <li>
                    <a href="<?php self::view_action_link('installdropin');?>" class="run-action block py-2 outline-none hover:text-base-1000 text-red-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Update Object Cache Drop-in', 'runcloud-hub'); ?></a>
                </li>
            <?php elseif ( self::is_dropin_config_need_update() ): ?>
                <li>
                    <a href="<?php self::view_action_link('installdropin');?>" class="run-action block py-2 outline-none hover:text-base-1000 text-red-1000 border-0 focus:outline-none active:outline-none"><?php esc_html_e('Fix Redis Site Config Drop-in', 'runcloud-hub'); ?></a>
                </li>
            <?php endif;?>
        <?php endif;?>
    </ul>
</div>