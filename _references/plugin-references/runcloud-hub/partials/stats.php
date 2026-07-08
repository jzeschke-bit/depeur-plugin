<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<?php $data_lastupdate = self::view_stats_lastupdate(); ?>

<?php if (!empty(self::view_rvalue('stats_health_onn'))): ?>
<!-- runcloud-health-stats -->
<?php $data_health = self::view_stats_charts('health');?>
<server-health-stats data-type='<?php echo esc_attr( $data_health['type'] ); ?>' data-meta='<?php echo json_encode($data_health['data'], JSON_UNESCAPED_SLASHES); ?>' data-timestamp='<?php echo esc_attr( $data_lastupdate ); ?>'></server-health-stats>
<!-- /runcloud-health-stats -->
<?php endif;?>

<?php if (!empty(self::view_rvalue('stats_transfer_onn'))): ?>
<!-- runcloud-transfer-stats -->
    <?php $data_transfer = self::view_stats_charts('transfer');?>
    <traffic-stats data-type='<?php echo esc_attr( $data_transfer['type'] ); ?>' data-meta='<?php echo json_encode($data_transfer['data'], JSON_UNESCAPED_SLASHES); ?>' data-timestamp='<?php echo esc_attr( $data_lastupdate ); ?>'></traffic-stats>
<!-- /runcloud-transfer-stats -->
<?php endif;?>
