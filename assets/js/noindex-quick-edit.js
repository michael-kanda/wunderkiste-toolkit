/**
 * Wunderkiste Toolkit - Bulk NoIndex: prefill the Quick Edit checkbox.
 */
jQuery(function($) {
    if ( typeof inlineEditPost === 'undefined' ) {
        return;
    }

    var wpInlineEdit = inlineEditPost.edit;

    inlineEditPost.edit = function( id ) {
        wpInlineEdit.apply( this, arguments );

        var postId = 0;
        if ( typeof( id ) === 'object' ) {
            postId = parseInt( this.getId( id ), 10 );
        }

        if ( postId > 0 ) {
            // Read the hidden data attribute rendered in the list column.
            var $data = $( '#post-' + postId ).find( '.seowk-noindex-data' );
            var isNoindex = $data.length > 0 && $data.data( 'noindex' ) === 1;

            $( 'input[name="seowk_noindex"]' ).prop( 'checked', isNoindex );
        }
    };
});
