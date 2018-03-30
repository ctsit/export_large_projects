<?php
/*
 * This script is called when export large projects button is clicked.
 * It collects entire project in bunches and store them in a single csv file in databse for 1 hour.
 * This page also allows users to download the generated CSV file.
 */

$module->checkExportAccess();
foreach (array('max_execution_time', 'items_per_batch') as $param) {
    if (!isset($_GET[$param]) || !is_numeric($_GET[$param])) {
        $module->renderErrorPage('Missing or invalid parameters.');
    }
}

include_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
renderPageTitle();

// Generate export ID.
$token = generateRandomHash(6);
$msg = RCView::span(array('style' => 'color: green;'), 'Large project export requested');
REDCap::logEvent($msg, 'export_id: ' . $token);

// Create temp file.
$target_filename = date('YmdHis') . '_pid' . $project_id . '_' . $token . '.csv';
$target_file = APP_PATH_TEMP . $target_filename;

if (!$fh = fopen($target_file, 'w')) {
    $module->renderErrorPage('Unable to create export file.');
}

$module->loadJsFile('js/batch.js');
$module->loadCssFile('css/batch.css');
$module->initJsVars(array(
    'batchUrl' => $module->getUrl('plugins/ajax/batch.php') . '&token=' . $token . '&max_execution_time=' . $_GET['max_execution_time'],
));

// Getting all records IDs.
global $Proj;
$ids = REDCap::getData('array', null, $Proj->table_pk);
$ids = array_keys($ids);

$batches = array_reverse(array_chunk($ids, $_GET['items_per_batch']));

// Store all the required session variables.
$_SESSION['elp'][$token] = array(
    'target_file' => $target_file,
    'target_filename' => $target_filename,
    'batches' => $batches,
    'total_batches' => count($batches),
    'total_items' => count($ids),
    'batches_processed' => 0,
    'items_processed' => 0,
    'start_time' => microtime(true),
);

// Displaying a hidden error message, to be shown in case of AJAX errors.
displayMsg(RCView::span(array('class' => 'ajax-error-msg')), 'ajaxErrorMsg', 'center', 'red', 'exclamation.png', null, false);
?>

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

<?php include_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
