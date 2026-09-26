/**
 * Wunderkiste Toolkit - Conversion Tracker (frontend).
 *
 * Fires the events in window.seowkConversions through an existing gtag().
 * The plugin never loads Google scripts itself.
 */
window.addEventListener( 'load', function() {
    if ( typeof window.gtag !== 'function' || ! Array.isArray( window.seowkConversions ) ) {
        return;
    }
    window.seowkConversions.forEach( function( event ) {
        if ( event.params ) {
            window.gtag( 'event', event.name, event.params );
        } else {
            window.gtag( 'event', event.name );
        }
    } );
} );
