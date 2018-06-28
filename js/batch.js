if (typeof exportLargeProjects === 'undefined') {
    exportLargeProjects = {};
}

exportLargeProjects.updateProgressBar = function(progressInfo, percent) {
    $('.progress-info').text(progressInfo);
    $('.progress-filling').css('width', percent);
    $('.progress-percent').text(percent);
};

exportLargeProjects.displayDownloadForm = function(filesize) {
    $('.download-btn-filesize').text(filesize);
    $('#elp-download').show();
};

exportLargeProjects.displayErrorMsg = function(msg = 'An error occurred during the export.') {
    $('#elpErrorMsg span').html(msg);
    $('#elp-container').hide();
    $('#elpErrorMsg').show();
};
