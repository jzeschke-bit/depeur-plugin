<?php defined('RUNCLOUD_HUB_INIT') || exit;
$ref = '?utm_source='.self::$slug.'&t='.time();
?>
<li><a href="<?php echo esc_url('https://runcloud.io/docs'.$ref); ?>" target="_blank" rel="noopener" role="button" class="block py-2 font-medium leading-none text-base-700 hover:text-base-1000"><?php esc_html_e('Help', 'runcloud-hub');?></a></li>
<li><a href="<?php echo esc_url('https://runcloud.io/changelog'.$ref); ?>" target="_blank" rel="noopener" role="button" class="block py-2 font-medium leading-none text-base-700 hover:text-base-1000"><?php esc_html_e("What's New", 'runcloud-hub');?></a></li>
<li><a href="<?php echo esc_url('https://manage.runcloud.io'.$ref); ?>" target="_blank" rel="noopener" role="button" class="block py-2 font-medium leading-none text-base-700 hover:text-base-1000"><?php esc_html_e('RunCloud Dashboard', 'runcloud-hub');?></a></li>
