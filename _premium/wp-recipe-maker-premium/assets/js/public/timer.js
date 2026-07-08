import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import '../../css/public/timer.scss';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.timer = {
    cookModeTimers: new Map(),
    cookModeTimerId: 0,
    runningTimer: false,
    runningTotal: 0,
    runningRemaining: 0,
    lastUpdated: 0,

    init: () => {
        // Not on print pages.
        const body = document.querySelector('body');
        if ( body && body.classList.contains( 'wprm-print' ) ) {
            return;
        }

        window.WPRecipeMaker.timer.bindTimers();
    },

    bindTimers: ( root = document ) => {
        if ( ! root || ! root.querySelectorAll ) {
            return;
        }

        const timers = root.querySelectorAll( '.wprm-timer' );

        for ( const timer of timers ) {
            const rawSeconds = parseFloat( timer.dataset.seconds );

            if ( ! rawSeconds || rawSeconds <= 0 ) {
                continue;
            }

            let link = timer.parentNode;
            if ( ! link || ! link.classList || ! link.classList.contains( 'wprm-timer-link' ) ) {
                link = document.createElement('a');
                link.href = '#';
                link.classList.add( 'wprm-timer-link' );

                if ( timer.parentNode ) {
                    timer.parentNode.insertBefore( link, timer );
                }

                link.appendChild( timer );

                tippy( link, {
                    theme: 'wprm',
                    content: wprmp_public.timer.text.start_timer,
                });
            }

            if ( ! link.dataset.timerBound ) {
                link.dataset.timerBound = '1';
                link.addEventListener( 'click', ( e ) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const cookModeElement = timer.closest( '.wprm-cook-mode' );
                    const cookModeModal = cookModeElement ? cookModeElement.closest( '.wprm-popup-modal' ) : null;
                    const label = timer.dataset.label ? timer.dataset.label.trim() : timer.textContent.trim();

                    window.WPRecipeMaker.timer.start( rawSeconds, {
                        context: cookModeElement ? 'cook-mode' : 'global',
                        cookModeModal,
                        label,
                    } );
                });
            }
        }
    },

    update: () => {
        // Update remaining seconds.
        const elapsed = Date.now() - window.WPRecipeMaker.timer.lastUpdated;
        window.WPRecipeMaker.timer.runningRemaining -= elapsed;
        window.WPRecipeMaker.timer.lastUpdated = Date.now();

        // Check if finished and update display.
        const total = window.WPRecipeMaker.timer.runningTotal;
        let remaining = window.WPRecipeMaker.timer.runningRemaining;

        if ( remaining <= 0 ) {
            remaining = 0;
            window.WPRecipeMaker.timer.finished();
        }

        window.WPRecipeMaker.timer.showTime( Math.round( remaining / 1000 ) );

        const percentageElapsed = total > 0 ? ( 100 * ( total - remaining ) / total ) : 0;
        const bar = document.querySelector( '#wprm-timer-bar-elapsed' );
        if ( bar ) {
            bar.style.width = Math.max( 0, Math.min( 100, percentageElapsed ) ) + '%';
        }
    },

    start: ( seconds, options = {} ) => {
        const context = options.context || 'global';

        if ( context === 'cook-mode' && options.cookModeModal ) {
            window.WPRecipeMaker.timer.startCookModeTimer( seconds, options );
        } else {
            window.WPRecipeMaker.timer.startGlobalTimer( seconds );
        }
    },

    startGlobalTimer: ( seconds ) => {
        window.WPRecipeMaker.timer.stop( () => {
            window.WPRecipeMaker.timer.createContainer();

            window.WPRecipeMaker.timer.runningTotal = seconds * 1000;
            window.WPRecipeMaker.timer.runningRemaining = seconds * 1000;
            window.WPRecipeMaker.timer.showTime( seconds );

            window.WPRecipeMaker.timer.play();
        } );
    },

    play: () => {
        const playEl = document.querySelector( '#wprm-timer-play' );
        const pauseEl = document.querySelector( '#wprm-timer-pause' );

        if ( playEl ) {
            playEl.style.display = 'none';
        }

        if ( pauseEl ) {
            pauseEl.style.display = '';
        }
    
        if ( window.WPRecipeMaker.timer.interval ) {
            clearInterval( window.WPRecipeMaker.timer.interval );
        }
        window.WPRecipeMaker.timer.interval = setInterval( window.WPRecipeMaker.timer.update, 1000 );
        window.WPRecipeMaker.timer.lastUpdated = Date.now();
    },

    pauze: () => {
        const playEl = document.querySelector( '#wprm-timer-play' );
        const pauseEl = document.querySelector( '#wprm-timer-pause' );

        if ( playEl ) {
            playEl.style.display = '';
        }

        if ( pauseEl ) {
            pauseEl.style.display = 'none';
        }

        if ( window.WPRecipeMaker.timer.interval ) {
            clearInterval( window.WPRecipeMaker.timer.interval );
        }
    },

    stop: ( callback = false ) => {
        if ( window.WPRecipeMaker.timer.interval ) {
            clearInterval( window.WPRecipeMaker.timer.interval );
        }

        const container = document.querySelector( '#wprm-timer-container' );

        if ( container && container.parentNode ) {
            container.parentNode.removeChild( container );
        }

        if ( callback ) {
            callback();
        }
    },

    finished: () => {
        window.WPRecipeMaker.timer.pauze();

        // Sound alarm once and keep pulsate background until closed.
        try {
            const alarm = new Audio( wprmp_public.timer.sound_file );
            alarm.play();
        } catch ( error ) {
            // Ignore playback issues.
        }

        const container = document.querySelector( '#wprm-timer-container' );
        if ( container ) {
            container.classList.add( 'wprm-timer-finished' );
        }
    },

    createContainer: () => {
        const container = document.createElement('div');
        container.id = 'wprm-timer-container';
        container.innerHTML = '<span id="wprm-timer-play" class="wprm-timer-icon" onclick="window.WPRecipeMaker.timer.play()">' + wprmp_public.timer.icons.play + '</span>';
        container.innerHTML += '<span id="wprm-timer-pause" class="wprm-timer-icon" onclick="window.WPRecipeMaker.timer.pauze()">' + wprmp_public.timer.icons.pause + '</span>';
        container.innerHTML += '<span id="wprm-timer-remaining"></span>';
        container.innerHTML += '<span id="wprm-timer-bar-container"><span id="wprm-timer-bar"><span id="wprm-timer-bar-elapsed"></span></span></span>';
        container.innerHTML += '<span id="wprm-timer-close" class="wprm-timer-icon" onclick="window.WPRecipeMaker.timer.stop()">' + wprmp_public.timer.icons.close + '</span>';

        document.querySelector('body').appendChild( container );
    },

    showTime: ( s ) => {
        const remainingEl = document.querySelector( '#wprm-timer-remaining' );
        if ( remainingEl ) {
            remainingEl.textContent = window.WPRecipeMaker.timer.formatTime( s );
        }
    },

    formatTime: ( s ) => {
        let seconds = Math.max( 0, parseInt( s, 10 ) || 0 );
        const h = Math.floor( seconds / 3600 );
        seconds -= h * 3600;
        const m = Math.floor( seconds / 60 );
        seconds -= m * 60;

        const prefixZero = ( value ) => ( value < 10 ? '0' + value : '' + value );
        
        if ( h > 0 ) {
            return prefixZero( h ) + ':' + prefixZero( m ) + ':' + prefixZero( seconds );
        } else {
            return prefixZero( m ) + ':' + prefixZero( seconds );
        }
    },

    startCookModeTimer: ( seconds, options = {} ) => {
        const cookModeModal = options.cookModeModal;

        if ( ! cookModeModal || ! cookModeModal.querySelector ) {
            window.WPRecipeMaker.timer.startGlobalTimer( seconds );
            return;
        }

        const timersWrapper = cookModeModal.querySelector( '.wprm-cook-mode-timers' );

        if ( ! timersWrapper ) {
            window.WPRecipeMaker.timer.startGlobalTimer( seconds );
            return;
        }

        const label = options.label && options.label.length ? options.label : wprmp_public.timer.text.start_timer;
        const id = 'cook-mode-timer-' + ( ++window.WPRecipeMaker.timer.cookModeTimerId );
        const totalMs = seconds * 1000;

        const timerData = {
            id,
            total: totalMs,
            remaining: totalMs,
            lastUpdated: Date.now(),
            interval: null,
            label,
            cookModeModal,
            finished: false,
            alarmPlayed: false,
        };

        timerData.elements = window.WPRecipeMaker.timer.createCookModeTimerElement( timerData );
        timersWrapper.appendChild( timerData.elements.container );

        window.WPRecipeMaker.timer.cookModeTimers.set( id, timerData );
        window.WPRecipeMaker.timer.updateCookModeTimerDisplay( timerData );
        window.WPRecipeMaker.timer.playCookModeTimer( id );
    },

    createCookModeTimerElement: ( timerData ) => {
        const container = document.createElement('div');
        container.className = 'wprm-cook-mode-timer';
        container.dataset.timerId = timerData.id;

        const topRow = document.createElement('div');
        topRow.className = 'wprm-cook-mode-timer-top';

        const labelEl = document.createElement('span');
        labelEl.className = 'wprm-cook-mode-timer-label';
        labelEl.textContent = timerData.label;
        topRow.appendChild( labelEl );

        const remainingEl = document.createElement('span');
        remainingEl.className = 'wprm-cook-mode-timer-remaining';
        topRow.appendChild( remainingEl );

        const controls = document.createElement('div');
        controls.className = 'wprm-cook-mode-timer-controls';

        const startText = wprmp_public.timer.text.start_timer || 'Start timer';

        const playButton = document.createElement('button');
        playButton.type = 'button';
        playButton.className = 'wprm-cook-mode-timer-button wprm-cook-mode-timer-play';
        playButton.innerHTML = wprmp_public.timer.icons.play;
        playButton.setAttribute( 'aria-label', startText );
        playButton.setAttribute( 'title', startText );
        playButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            window.WPRecipeMaker.timer.playCookModeTimer( timerData.id );
        } );
        controls.appendChild( playButton );

        const pauseButton = document.createElement('button');
        pauseButton.type = 'button';
        pauseButton.className = 'wprm-cook-mode-timer-button wprm-cook-mode-timer-pause';
        pauseButton.innerHTML = wprmp_public.timer.icons.pause;
        pauseButton.setAttribute( 'aria-label', 'Pause timer' );
        pauseButton.setAttribute( 'title', 'Pause timer' );
        pauseButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            window.WPRecipeMaker.timer.pauseCookModeTimer( timerData.id );
        } );
        controls.appendChild( pauseButton );

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'wprm-cook-mode-timer-button wprm-cook-mode-timer-close';
        closeButton.innerHTML = wprmp_public.timer.icons.close;
        closeButton.setAttribute( 'aria-label', 'Remove timer' );
        closeButton.setAttribute( 'title', 'Remove timer' );
        closeButton.addEventListener( 'click', ( event ) => {
            event.preventDefault();
            window.WPRecipeMaker.timer.stopCookModeTimer( timerData.id );
        } );
        controls.appendChild( closeButton );

        topRow.appendChild( controls );
        container.appendChild( topRow );

        const bar = document.createElement('div');
        bar.className = 'wprm-cook-mode-timer-bar';
        const barFill = document.createElement('div');
        barFill.className = 'wprm-cook-mode-timer-bar-fill';
        bar.appendChild( barFill );

        container.appendChild( bar );

        pauseButton.style.display = 'none';

        return {
            container,
            remaining: remainingEl,
            bar: barFill,
            playButton,
            pauseButton,
            closeButton,
        };
    },

    updateCookModeTimerDisplay: ( timerData ) => {
        if ( ! timerData || ! timerData.elements ) {
            return;
        }

        const timeString = window.WPRecipeMaker.timer.formatTime( Math.round( timerData.remaining / 1000 ) );
        if ( timerData.elements.remaining ) {
            timerData.elements.remaining.textContent = timeString;
        }

        if ( timerData.elements.bar ) {
            const percentage = timerData.total > 0 ? ( ( timerData.total - timerData.remaining ) / timerData.total ) * 100 : 0;
            timerData.elements.bar.style.width = Math.max( 0, Math.min( 100, percentage ) ) + '%';
        }
    },

    playCookModeTimer: ( id ) => {
        const timerData = window.WPRecipeMaker.timer.cookModeTimers.get( id );
        if ( ! timerData || ! timerData.elements ) {
            return;
        }

        if ( timerData.interval ) {
            clearInterval( timerData.interval );
        }

        if ( timerData.remaining <= 0 || timerData.finished ) {
            timerData.remaining = timerData.total;
            timerData.finished = false;
            timerData.alarmPlayed = false;
            if ( timerData.elements.container ) {
                timerData.elements.container.classList.remove( 'wprm-cook-mode-timer-finished' );
            }
        }

        if ( timerData.elements.playButton ) {
            timerData.elements.playButton.style.display = 'none';
        }
        if ( timerData.elements.pauseButton ) {
            timerData.elements.pauseButton.style.display = '';
        }

        timerData.lastUpdated = Date.now();
        timerData.interval = setInterval( () => {
            window.WPRecipeMaker.timer.updateCookModeTimer( id );
        }, 1000 );

        window.WPRecipeMaker.timer.updateCookModeTimerDisplay( timerData );
    },

    pauseCookModeTimer: ( id ) => {
        const timerData = window.WPRecipeMaker.timer.cookModeTimers.get( id );
        if ( ! timerData || ! timerData.elements ) {
            return;
        }

        if ( timerData.interval ) {
            clearInterval( timerData.interval );
            timerData.interval = null;
        }

        if ( timerData.elements.playButton ) {
            timerData.elements.playButton.style.display = '';
        }
        if ( timerData.elements.pauseButton ) {
            timerData.elements.pauseButton.style.display = 'none';
        }
    },

    stopCookModeTimer: ( id ) => {
        const timerData = window.WPRecipeMaker.timer.cookModeTimers.get( id );
        if ( ! timerData ) {
            return;
        }

        window.WPRecipeMaker.timer.pauseCookModeTimer( id );

        if ( timerData.elements && timerData.elements.container && timerData.elements.container.parentNode ) {
            timerData.elements.container.parentNode.removeChild( timerData.elements.container );
        }

        window.WPRecipeMaker.timer.cookModeTimers.delete( id );
    },

    updateCookModeTimer: ( id ) => {
        const timerData = window.WPRecipeMaker.timer.cookModeTimers.get( id );
        if ( ! timerData ) {
            return;
        }

        const elapsed = Date.now() - timerData.lastUpdated;
        timerData.remaining -= elapsed;
        timerData.lastUpdated = Date.now();

        if ( timerData.remaining <= 0 ) {
            timerData.remaining = 0;
            window.WPRecipeMaker.timer.finishCookModeTimer( id );
        }

        window.WPRecipeMaker.timer.updateCookModeTimerDisplay( timerData );
    },

    finishCookModeTimer: ( id ) => {
        const timerData = window.WPRecipeMaker.timer.cookModeTimers.get( id );
        if ( ! timerData ) {
            return;
        }

        window.WPRecipeMaker.timer.pauseCookModeTimer( id );
        timerData.finished = true;

        if ( timerData.elements && timerData.elements.container ) {
            timerData.elements.container.classList.add( 'wprm-cook-mode-timer-finished' );
        }

        if ( ! timerData.alarmPlayed ) {
            try {
                const alarm = new Audio( wprmp_public.timer.sound_file );
                alarm.play();
            } catch ( error ) {
                // Ignore playback issues.
            }
            timerData.alarmPlayed = true;
        }
    },

    clearCookModeTimers: ( modalElement = null ) => {
        const idsToRemove = [];

        window.WPRecipeMaker.timer.cookModeTimers.forEach( ( timerData, id ) => {
            if ( ! modalElement || timerData.cookModeModal === modalElement ) {
                idsToRemove.push( id );
            }
        } );

        idsToRemove.forEach( ( id ) => window.WPRecipeMaker.timer.stopCookModeTimer( id ) );
    },
};

ready( () => {
    window.WPRecipeMaker.timer.init();
} );

function ready( fn ) {
    if ( document.readyState != 'loading' ) {
        fn();
    } else {
        document.addEventListener( 'DOMContentLoaded', fn );
    }
}