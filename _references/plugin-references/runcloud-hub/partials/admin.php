<?php defined('RUNCLOUD_HUB_INIT') || exit;?>
<!-- runcloud-wrap -->
<div class="h-full bg-base-2-100 -mb-18 clear-both<?php echo (self::$optpage ? ' runcloud-optpage' : ''); ?>" id="runcloud-wrap">
    <h1 class="screen-reader-text">
        <?php printf(esc_html__('%s Page', 'runcloud-hub'), self::$name);?>
    </h1>
    <!-- runcloud-body -->
    <div>
        <?php self::view_do_preload();?>
        <?php self::view_do_network();?>

        <!-- runcloud-content -->
        <section class="runcloud-content">

            <div class="h-full md:flex rci-desktop">
                <aside class="block w-full p-2 bg-white shadow rci-sidemenu md:hidden">
                    <?php self::view_page('sidebar-sm');?>
                </aside>

                <main class="relative flex w-full h-full md:pl-40 lg:pl-48 xl:pl-56 md:pr-40 lg:pr-48 xl:pr-64 rci-settings bg-base-2-100">
                    <div class="w-full p-4 md:p-6 pb-12 md:pb-12 md:pt-2 bg-body">
                        <h1 class="pt-3 pb-6 text-2xl font-bold leading-tight text-base-1000 v-display-title">
                            <!-- title here -->
                        </h1>

                        <form action="<?php echo esc_url( admin_url('options.php') ); ?>" method="POST" id="<?php echo esc_attr(self::$slug); ?>-options">
                            <?php settings_fields(self::$slug);?>

                            <?php if (self::stats_is_enabled() && self::is_main_site() && !self::is_subdirectory() && !self::is_client_mode()) : ?>
                                <?php self::view_page(['stats']);?>
                            <?php endif;?>

                            <?php self::view_page(['purger']);?>

                            <?php if (self::is_main_site() && !self::is_subdirectory()): ?>
                                <?php self::view_page(['rules']);?>
                            <?php endif; ?>

                            <?php self::view_page(['preload']);?>

                            <?php self::view_page(['redis']);?>

                            <?php self::view_page(['cloudflare']);?>

                            <?php if (self::is_main_site() && !self::is_subdirectory() ) : ?>
                                <?php self::view_page(['settings']);?>
                            <?php endif; ?>

                            <?php self::view_page('btnsubmit');?>
                        </form>
                    </div>

                </main>

                <aside class="fixed flex-none h-full hidden md:w-40 lg:w-48 xl:w-56 p-6 md:block rci-sidemenu-bg-only bg-base-2-100">
                </aside>

                <?php if ( !self::is_client_mode() ) : ?>
                    <aside class="fixed bottom-0 flex-none hidden md:w-40 lg:w-48 xl:w-56 p-6 md:block rci-linkmenu bg-base-2-100">
                        <ul class="mb-4">
                            <?php self::view_page('links');?>
                        </ul>
                    </aside>
                <?php endif; ?>

                <aside class="fixed flex-none hidden md:w-40 lg:w-48 xl:w-56 p-6 md:block rci-sidemenu bg-base-2-100">
                    <?php self::view_page('sidebar-md');?>
                </aside>

                <aside class="fixed flex-none h-full hidden md:w-40 lg:w-48 xl:w-64 p-6 md:block rci-quickmenu bg-base-2-100 right-0">
                    <?php self::view_page('command');?>
                </aside>
            </div>

        </section>
        <!-- /runcloud-content -->
    </div>
    <!-- /runcloud-body -->
</div>
<!-- /runcloud-wrap -->