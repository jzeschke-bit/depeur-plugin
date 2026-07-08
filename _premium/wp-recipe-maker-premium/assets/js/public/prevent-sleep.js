window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.preventSleep = {
	wakeLockApi: false,
	wakeLock: false,
    init() {
		// Check if WakeLockApi is available.
		if ( 'wakeLock' in navigator && 'request' in navigator.wakeLock ) {
			this.wakeLockApi = navigator.wakeLock;
		}

		if ( this.wakeLockApi ) {

			const preventSleepShortcodes = document.querySelectorAll( '.wprm-prevent-sleep' );

			if ( 0 < preventSleepShortcodes.length ) {
				for ( let shortcode of preventSleepShortcodes ) {
					shortcode.style.display = '';

					const checkbox = shortcode.querySelector( '.wprm-prevent-sleep-checkbox' );
					checkbox.addEventListener( 'change', function(e) {
						WPRecipeMaker.preventSleep.checkboxChange( e.target );
					}, false );
				}
			}
		}
	},
	checkboxChange( elem ) {
		if ( elem.checked ) {
			this.lock();
		} else {
			this.unlock();
		}
	},
	setCheckboxesState( locked ) {
		const checkboxes = document.querySelectorAll( '.wprm-prevent-sleep-checkbox' );

		for ( let checkbox of checkboxes ) {
			checkbox.checked = locked;
		}
	},
	async lock() {
		try {
			this.wakeLock = await this.wakeLockApi.request('screen');
			this.wakeLock.addEventListener( 'release', (e) => {
				this.wakeLock = false;
				this.setCheckboxesState( false );
			} );

			this.setCheckboxesState( true );
		} catch (e) {      
			this.setCheckboxesState( false );
		}
	},
	unlock() {
		if ( this.wakeLock ) {
			this.wakeLock.release();
			this.wakeLock = false;
		}
		this.setCheckboxesState( false );
	}
}

ready(() => {
	window.WPRecipeMaker.preventSleep.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}