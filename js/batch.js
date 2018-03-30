$(document).ready(function() {
    var lastPercent = 0;
    var defaultErrorMsg = 'An error occurred during the export.';

    exportLargeProjects.displayErrorMsg = function(msg) {
        $('.ajax-error-msg').text(msg);
        $('#elp-container').hide();
        $('#ajaxErrorMsg').show();
    };

    exportLargeProjects.batchUnqueue = function() {
        $.get(exportLargeProjects.batchUrl, function(data) {
            if (typeof data.success === 'undefined') {
                exportLargeProjects.displayErrorMsg(defaultErrorMsg);
            }

            if (!data.success) {
                exportLargeProjects.displayErrorMsg(data.errorMsg);
                return;
            }

            // If processed percent hasn't increased, there is something wrong.
            if (!data.percent || data.percent === lastPercent) {
                exportLargeProjects.displayErrorMsg(defaultErrorMsg);
                return;
            }

            lastPercent = data.percent;

            $('.progress-info').html(data.progressInfoMsg);
            $('.progress-filling').css('width', data.percent + '%');
            $('.progress-percent').text(data.percent + '%');

            if (data.percent < 100) {
                // Process next batch.
                exportLargeProjects.batchUnqueue();
                return;
            }

            // Showing download form.
            $('.download-btn-filesize').text(data.filesize);
            $('#elp-download').show();
        }, 'json').fail(function() {
            exportLargeProjects.displayErrorMsg(defaultErrorMsg);
        });
    };

    // Init export.
    exportLargeProjects.batchUnqueue();
});
