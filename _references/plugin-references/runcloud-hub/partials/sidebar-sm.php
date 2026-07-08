<?php defined('RUNCLOUD_HUB_INIT') || exit;?>

<header>
    <div class="flex items-center justify-between p-2">
        <div>
            <button @click="navbarIsOpen = !navbarIsOpen" type="button" class="text-black hover:text-base-1000 focus:text-base-1000 focus:outline-none v-display-click">
                <svg class="w-6 h-6 fill-current inline-block mr-2" width="24" height="24" viewBox="0 0 28 28">
                    <path d="M8 19.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM8 11.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM18 19.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM8 3.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM18 11.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM28 19.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM18 3.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM28 11.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5zM28 3.5v3c0 0.828-0.672 1.5-1.5 1.5h-5c-0.828 0-1.5-0.672-1.5-1.5v-3c0-0.828 0.672-1.5 1.5-1.5h5c0.828 0 1.5 0.672 1.5 1.5z"></path>
                </svg>
            </button>
        </div>
        <div>
            <button @click="quickActionMenuIsOpen = !quickActionMenuIsOpen" type="button" class="text-black hover:text-base-1000 focus:text-base-1000 focus:outline-none v-display-click">
                <svg class="w-6 h-6 fill-current inline-block" width="24" height="24" viewBox="0 0 28 28">
                    <path d="M16 22h10v-2h-10v2zM10 14h16v-2h-16v2zM20 6h6v-2h-6v2zM28 19v4c0 0.547-0.453 1-1 1h-26c-0.547 0-1-0.453-1-1v-4c0-0.547 0.453-1 1-1h26c0.547 0 1 0.453 1 1zM28 11v4c0 0.547-0.453 1-1 1h-26c-0.547 0-1-0.453-1-1v-4c0-0.547 0.453-1 1-1h26c0.547 0 1 0.453 1 1zM28 3v4c0 0.547-0.453 1-1 1h-26c-0.547 0-1-0.453-1-1v-4c0-0.547 0.453-1 1-1h26c0.547 0 1 0.453 1 1z"></path>
                </svg>
            </button>
        </div>
    </div>

    <div :class="navbarIsOpen ? 'block' : 'hidden'" class="absolute bottom-0 left-0 top-0 right-0 z-10 px-4 pt-4 pt-2 pb-4 mt-12 bg-white md:hidden block v-display-none display-none">
        <button @click="navbarIsOpen = !navbarIsOpen" id="btn-vueclose" type="button" class="text-black hover:text-base-1000 focus:text-base-1000 focus:outline-none absolute right-0 pr-4">
            <?php self::view_page('btnclose-sm');?>
        </button>

        <h4 class="mb-4 text-l font-semibold"><?php esc_html_e('Menu', 'runcloud-hub'); ?></h4>

        <?php self::view_page('sidebar');?>

        <?php if ( !self::is_client_mode() ) : ?>
            <ul class="mt-6 absolute">
                <?php self::view_page('links');?>
            </ul>
        <?php endif; ?>
    </div>

    <div :class="quickActionMenuIsOpen ? 'block' : 'hidden'" class="absolute bottom-0 left-0 top-0 right-0 z-10 px-4 pt-4 pt-2 pb-4 mt-12 bg-white md:hidden block v-display-none display-none">
        <button @click="quickActionMenuIsOpen = !quickActionMenuIsOpen" type="button" class="text-black hover:text-base-1000 focus:text-base-1000 focus:outline-none absolute right-0 pr-4">
            <?php self::view_page('btnclose-sm');?>
        </button>

        <?php self::view_page('command');?>
    </div>
</header>