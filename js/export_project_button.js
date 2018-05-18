document.addEventListener('DOMContentLoaded', function() {
    $('#reprow_ALL .rprt_btns').append(exportLargeProjects.buttonHtml);
    $('#export-large-projects-btn').click(function() {
        location.href = exportLargeProjects.url;
        return false;
    });
});
