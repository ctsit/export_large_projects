<?php

$module->checkExportAccess();

// Request to download a processed file
$target_filename = $_POST['filename'];
$target_file = APP_PATH_TEMP . $target_filename;
if (!file_exists($target_file)) {
    $module->renderErrorPage('Unable to locate requested file: ' . $target_filename . '.');
}

// Download file and then delete it from the server.
header('Pragma: anytextexeptno-cache', true);
header('Content-Type: application/octet-stream"');
header('Content-Disposition: attachment; filename="' . $target_filename . '"');
header('Content-Length: ' . filesize($target_file));
readfile_chunked($target_file);

// Log event
REDCap::logEvent('<span style="color: green;">Large project export file downloaded</span>', 'file_name: ' . $target_filename);
