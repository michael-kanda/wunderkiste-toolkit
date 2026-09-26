/**
 * Wunderkiste Toolkit - ID column: click an ID to copy it.
 *
 * Strings come from PHP via window.seowkIdColumn.
 */
jQuery(document).ready(function($) {
    var copyTitle = ( window.seowkIdColumn && window.seowkIdColumn.copyTitle ) || '%s';
    $('.column-seowk_id strong').each(function() {
        var $this = $(this), id = $this.text();
        $this.attr('title', copyTitle.replace('%s', id));
        $this.on('click', function(e) {
            e.preventDefault();
            if (navigator.clipboard) { navigator.clipboard.writeText(id); }
            var orig = $this.text();
            $this.text('✓').css('color', '#00a32a');
            setTimeout(function() { $this.text(orig).css('color', '#2271b1'); }, 1000);
        });
    });
});
