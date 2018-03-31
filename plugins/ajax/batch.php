<?php

$module->checkExportAccess(true);

// Getting max excecution time.
if (empty($_GET['max_execution_time']) || !is_numeric($_GET['max_execution_time'])) {
    $max_exec_time = $module->getProjectSetting('max_execution_time');
}
else {
    $max_exec_time = $_GET['max_execution_time'];
}

set_time_limit($max_exec_time);

if (empty($_GET['token'])) {
    $module->returnAjaxError('Missing export ID.');
}

$token = $_GET['token'];
if (!isset($_SESSION['elp'][$token])) {
    $module->returnAjaxError('Invalid export ID.');
}

if (empty($_SESSION['elp'][$token]['batches'])) {
    $module->returnAjaxError('There is nothing to export.');
}

if (!$fh = fopen($_SESSION['elp'][$token]['target_file'], 'a')) {
    $module->returnAjaxError('Unable to open export file.');
}

$batch_start = microtime(true);

try {
    $curr_batch = array_pop($_SESSION['elp'][$token]['batches']);
    $records = REDCap::getData('csv', $curr_batch);

    // Trim the header on all but the first row of the first batch.
    if ($_SESSION['elp'][$token]['batches_processed']) {
        $first_cr = strpos($records, "\n");
        if ($first_cr !== false) {
            $records = substr($records, $first_cr + 1);
        }
    }

    fwrite($fh, $records);
}
catch (Exception $e) {
    $module->returnAjaxError('An error occurred during export.');
}

fclose($fh);

// Updating batch queue info.
$_SESSION['elp'][$token]['items_processed'] += count($curr_batch);
$_SESSION['elp'][$token]['batches_processed']++;

extract($_SESSION['elp'][$token]);

// Calculating export duration so far.
$duration = round(microtime(true) - $start_time);

if ($batches_processed < $total_batches) {
    // Calculate time estimate.
    $batch_avg = $duration / $batches_processed;
    $batch_remaining = $total_batches - $batches_processed;
    $time_remaining = round($batch_remaining * $batch_avg);

    $msg = 'Processed ' . $items_processed . ' records of ' . $total_items . '.';
    $msg .= ' Approximately ' . $time_remaining . ' seconds remaining...';

    echo json_encode(array(
        'success' => true,
        'progressInfoMsg' => $msg,
        'percent' => floor(($batches_processed / $total_batches) * 100),
    ));
    exit;
}

// Export is completed.
$duration .= $duration == 1 ? ' second' : ' seconds';
$report = array(
    'export_id: ' . $token,
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

echo json_encode(array(
    'success' => true,
    'percent' => 100,
    'progressInfoMsg' => 'Processed ' . $items_processed . ' records in ' . $duration . '.',
    'filesize' => $filesize . ' ' . $unit,
));

// Cleaning up queue info from session.
unset($_SESSION['elp']);
