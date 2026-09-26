/**
 * Wunderkiste Toolkit - Image Resizer (media library).
 *
 * Config and strings come from PHP via window.seowkResizer.
 */
jQuery(document).ready(function($) {
    var cfg = window.seowkResizer || { i18n: {} };
    var t = cfg.i18n;

    // Single image resize
    $(document).on('click', '.seowk-resizer-trigger', function(e) {
        e.preventDefault();
        var btn = $(this),
            container = btn.closest('td, .setting, .compat-field-seowk_resizer_resize'),
            status = container.find('.seowk-resizer-status'),
            sizeDisplay = container.find('.seowk-resizer-current-size');
        var id = btn.data('id'), size = btn.data('size') || 800, nonce = btn.data('nonce');

        if (!confirm(t.confirmSingle.replace('%d', size))) return;

        container.find('.seowk-resizer-trigger').prop('disabled', true);
        status.text(t.working).css('color', '#2271b1').show();

        $.post(ajaxurl, {
            action: 'seowk_resizer_resize_image',
            attachment_id: id,
            target_size: size,
            security: nonce
        }, function(r) {
            if (r.success) {
                status.text(t.done + ' ' + r.data.dimensions).css('color', 'green');
                // Update the size label
                if (sizeDisplay.length) {
                    // Details view shows "Current: <strong>…</strong>", the list column only the size.
                    if (sizeDisplay.find('strong').length) {
                        sizeDisplay.empty().append(document.createTextNode(t.current + ' '), $('<strong>').text(r.data.dimensions));
                    } else {
                        sizeDisplay.text(r.data.dimensions);
                    }
                }
                // Re-enable the buttons after 3 seconds
                setTimeout(function() {
                    container.find('.seowk-resizer-trigger').prop('disabled', false);
                    status.fadeOut();
                }, 3000);
            } else {
                status.text('✗ ' + (r.data || t.error)).css('color', 'red');
                container.find('.seowk-resizer-trigger').prop('disabled', false);
            }
        }).fail(function() {
            status.text('✗ ' + t.connection).css('color', 'red');
            container.find('.seowk-resizer-trigger').prop('disabled', false);
        });
    });

    // Bulk Action Handler
    $(document).on('click', '#doaction, #doaction2', function(e) {
        var action = $(this).prev('select').val();
        if (action !== 'seowk_resizer_bulk_800' && action !== 'seowk_resizer_bulk_1200') return;

        e.preventDefault();
        var size = action === 'seowk_resizer_bulk_800' ? 800 : 1200;
        var checked = $('input[name="media[]"]:checked');

        if (checked.length === 0) {
            alert(t.selectOne);
            return;
        }

        if (!confirm(t.confirmBulk.replace('%d', size))) {
            return;
        }

        var ids = [];
        checked.each(function() { ids.push($(this).val()); });

        // Build the progress modal
        var modal = $('<div id="seowk-resizer-bulk-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;display:flex;align-items:center;justify-content:center;">' +
            '<div style="background:#fff;padding:30px;border-radius:8px;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.3);">' +
            '<h2 style="margin:0 0 20px;color:#1d2327;"></h2>' +
            '<div class="seowk-resizer-progress-bar" style="background:#ddd;height:24px;border-radius:12px;overflow:hidden;margin-bottom:15px;">' +
            '<div class="seowk-resizer-progress-fill" style="background:linear-gradient(90deg,#2271b1,#135e96);height:100%;width:0%;transition:width 0.3s;"></div></div>' +
            '<div class="seowk-resizer-progress-text" style="text-align:center;font-size:14px;color:#50575e;"></div>' +
            '<div class="seowk-resizer-progress-log" style="max-height:200px;overflow-y:auto;margin-top:15px;font-size:12px;background:#f6f7f7;padding:10px;border-radius:4px;"></div>' +
            '</div></div>');
        modal.find('h2').text('🖼️ ' + t.scaling);
        $('body').append(modal);

        var processed = 0, success = 0, errors = 0;
        var progressFill = modal.find('.seowk-resizer-progress-fill');
        var progressText = modal.find('.seowk-resizer-progress-text').text('0 / ' + ids.length);
        var progressLog = modal.find('.seowk-resizer-progress-log');

        // Titles and server messages are inserted as text, never as HTML.
        function logLine(color, text) {
            progressLog.prepend($('<div>').css({ color: color, marginBottom: '3px' }).text(text));
        }

        function processNext(index) {
            if (index >= ids.length) {
                // Done
                progressText.empty().append(
                    $('<strong>').css('color', '#00a32a').text('✓ ' + t.finished),
                    document.createTextNode(' ' + success + ' ' + t.successful + ', ' + errors + ' ' + t.errors)
                );
                setTimeout(function() {
                    modal.fadeOut(300, function() {
                        $(this).remove();
                        location.reload();
                    });
                }, 2000);
                return;
            }

            var id = ids[index];
            var row = $('input[name="media[]"][value="' + parseInt(id, 10) + '"]').closest('tr');
            var title = row.find('.title a').text() || row.find('.column-title strong').text() || 'ID: ' + id;

            $.post(ajaxurl, {
                action: 'seowk_resizer_resize_image',
                attachment_id: id,
                target_size: size,
                security: cfg.bulkNonce
            }, function(r) {
                processed++;
                var percent = Math.round((processed / ids.length) * 100);
                progressFill.css('width', percent + '%');
                progressText.text(processed + ' / ' + ids.length);

                if (r.success) {
                    success++;
                    logLine('#00a32a', '✓ ' + title + ' → ' + r.data.dimensions);
                } else {
                    errors++;
                    logLine('#d63638', '✗ ' + title + ': ' + (r.data || t.error));
                }

                // Next image after a short pause
                setTimeout(function() { processNext(index + 1); }, 200);
            }).fail(function() {
                processed++;
                errors++;
                logLine('#d63638', '✗ ' + title + ': ' + t.connection);
                setTimeout(function() { processNext(index + 1); }, 200);
            });
        }

        processNext(0);
    });
});
