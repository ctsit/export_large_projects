<div id="elp-container">
    <div class="round chklist">
        <div class="chklisthdr">Exporting Project</div>
        <div id="progress-bar">
            <div class="progress-filling">&nbsp;</div>
            <div class="progress-percent">0%</div>
        </div>
        <div class="progress-info">Initializing...</div>
    </div>

    <form method="post" id="elp-download" name="elp-download" action="<?php echo $module->getUrl('plugins/download.php'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="filename" value="<?php echo $target_filename; ?>">
        <button type="submit" class="jqbutton" name="submit">
            <div class="download-btn-image"><?php echo DataExport::getDownloadIcon('csv', false); ?></div>
            <div class="download-btn-label">Download export</div>
            <small>File size: <span class="download-btn-filesize"></span></small>
        </button>
        <div class="download-info">This file will be automatically deleted from the server in approximately one hour.</div>
    </form>
</div>
