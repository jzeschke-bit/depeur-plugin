<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<button type="submit" id="btn-submit" class="btn btn-base inline-block mr-3 display-none" value="<?php esc_attr_e('Save Settings', 'runcloud-hub');?>"><?php esc_html_e('Save Settings', 'runcloud-hub');?></button>

<a href="<?php self::view_action_link('reset');?>" id="btn-reset" class="btn btn-secondary inline-block mr-3 display-none"><?php esc_html_e('Reset Settings', 'runcloud-hub');?></a>
