<?php
/*
 * This script is called when export large projects button is clicked.
 * It collects entire project in bunches and store them in a single csv file in databse for 1 hour.
 * This page also allows users to download the generated CSV file.
 */

while (ob_get_level() > 1) {
    ob_end_clean();
}

include_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
renderPageTitle();

$module->checkExportAccess();
foreach (array('max_execution_time_per_batch', 'fields_per_batch') as $param) {
    if (!isset($_GET[$param]) || !is_numeric($_GET[$param]) || $_GET[$param] <= 0) {
        $module->renderErrorPage('Missing or invalid parameters.');
    }
}

// Generate export ID.
$export_id = generateRandomHash(6);
$msg = RCView::span(array('style' => 'color: green;'), 'Large project export requested');
REDCap::logEvent($msg, 'export_id: ' . $export_id);

// Create temp file.
$target_filename = date('YmdHis') . '_pid' . $project_id . '_' . $export_id . '.csv';
$target_file = APP_PATH_TEMP . $target_filename;

if (!$fh = fopen($target_file, 'w')) {
    $module->renderErrorPage('Unable to create export file.');
}

// Getting all records IDs.
global $Proj;
$ids = REDCap::getData('array', null, $Proj->table_pk);

if (empty($ids)) {
    $module->renderErrorPage('There is nothing to export.');
}

$module->loadJsFile('js/batch.js');
$module->loadCssFile('css/batch.css');

$batch_size = max(1, floor($_GET['fields_per_batch'] / count($Proj->metadata)));
$batches = array_chunk(array_keys($ids), $batch_size);
$total_batches = count($batches);
$total_items = count($ids);
$start_time = microtime(true);
$items_processed = 0;

// Placing hidden error container.
displayMsg(RCView::span(array(),''), 'elpErrorMsg', 'center', 'red', 'exclamation.png', null, false);

include_once 'progress_bar.php';
include_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

ob_flush();
flush();

foreach ($batches as $count => $batch) {
    set_time_limit($_GET['max_execution_time_per_batch']);

    try {
        $records = REDCap::getData('csv', $batch);

        // Trim the header on all but the first row of the first batch.
        if ($count) {
            $first_lb = strpos($records, "\n");
            if ($first_lb !== false) {
                $records = substr($records, $first_lb + 1);
            }
        }

        fwrite($fh, $records);
    }
    catch (Exception $e) {
        $module->callJsCallback('displayErrorMsg');
    }

    $count++;

    // Calculating export duration so far.
    $duration = round(microtime(true) - $start_time);
    $items_processed += count($batch);
    $percent = floor(($count / $total_batches) * 100) . '%';

    if ($count == $total_batches) {
        break;
    }

    // Calculate time estimate.
    $batch_avg = $duration / $count;
    $batch_remaining = $total_batches - $count;
    $time_remaining = round($batch_remaining * $batch_avg);

    $msg = 'Processed ' . $items_processed . ' records of ' . $total_items . '.';
    $msg .= ' Approximately ' . $time_remaining . ' seconds remaining...';

    // Updating progress bar status.
    $module->callJsCallback('updateProgressBar', array($msg, $percent));

    ob_flush();
    flush();
}

fclose($fh);

// Export is completed.
$duration .= $duration == 1 ? ' second' : ' seconds';
$report = array(
    'export_id: ' . $export_id,
    'total_records: ' . $total_items,
    'duration: ' . $duration,
    'fields: "' . implode(', ', REDCap::getFieldNames()) . '"',
);

$msg = RCView::span(array('style' => 'color: green;'), 'Large project export completed');
REDCap::logEvent($msg, implode(",\n", $report));

$filesize = filesize($target_file);
foreach (array('b', 'kb', 'mb', 'gb', 'tb') as $unit) {
    if ($filesize / 1024 < 1) {
        break;
    }

    $filesize /= 1024;
}

$filesize .= ' ' . $unit;

$msg = 'Processed ' . $items_processed . ' records in ' . $duration . '.';
$module->callJsCallback('updateProgressBar', array($msg, $percent));
$module->callJsCallback('displayDownloadForm', array($filesize));

ob_end_flush();
