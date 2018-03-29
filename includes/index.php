<?php
/*
 * This script is called when export large projects button is clicked.
 * It collects entire project in bunches and store them in a single csv file in databse for 1 hour.
 * This page also allows users to download the generated csv file.
 */

// Enable flushing of output for progress meter...
ini_set("zlib.output_compression", 0);	// off
ini_set("implicit_flush", 1);			// on

// Get max excecution time and number of fields per batch value from the project configuration.
$max_execution_time = $_GET["max_execution_time"];
ini_set('max_execution_time', $max_execution_time);	//1800 seconds = 30 minutes

$fields_per_batch_value = $_GET["fields_per_batch"];
$fields_per_batch = $fields_per_batch_value;

// Check user privs
if (!SUPER_USER) {
	$rights = REDCap::getUserRights(USERID);
	$rights = $rights[USERID];
	if ($rights['data_export_tool'] != 1) {
		include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
		displayMsg("You do not have proper data export rights to use this tool ({$rights[USERID]['data_export_tool']})", "errorMsg","center","red","exclamation_frame.png", 600);
		exit();
	}
}

// Check if we are posting back for a download
if(isset($_POST['download'])) {
	// Request to download a processed file
	$target_filename = $_POST['download'];
	$target_file = APP_PATH_TEMP . $target_filename;
	if (!file_exists($target_file)) {
		include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
		displayMsg("Unable to located requested file: $target_filename", "errorMsg","center","red","exclamation_frame.png", 600);
		exit();
	}
	
	// Download file and then delete it from the server
	header('Pragma: anytextexeptno-cache', true);
	header('Content-Type: application/octet-stream"');
	header('Content-Disposition: attachment; filename="'.$target_filename.'"');
	header('Content-Length: ' . filesize($target_file));
	ob_end_flush();
    readfile_chunked($target_file);
    
    // Log event
	REDCap::logEvent('<span style="color: green;">Large project export file downloaded</span>', 'file_name: ' . $target_filename);
	exit();
}

// add redcap header
require_once \ExternalModules\ExternalModules::getProjectHeaderPath();
// Connect to REDCap
require_once "../../redcap_connect.php";

// include export project button php file
include_once "export_project_button.php";

$token = $_GET["token"];
if (!$token) {
    // Generating token.
	$token = generateRandomHash(6);
    REDCap::logEvent('<span style="color: green;">Large project export requested</span>', 'export_id: ' . $token);

	// Get name of first field:
    $fields = REDCap::getFieldNames();
	$field_count = count($fields);

	// Get all record_ids
	$ids = REDCap::getData('array',NULL,array($fields[0]));
	$ids = array_keys($ids);
	$id_count = count($ids);

	// Determine batching size (with a minimum of 1 record per export).
	$records_per_batch = max(1,floor($fields_per_batch / $field_count));
	$all_batches = array_chunk($ids,$records_per_batch);
	$batch_total = count($all_batches);
	$batches = array_reverse($all_batches);
	$curr_batch = array_pop($batches);
	$curr_batch_count = 0;

	// Create temp file - Set the target file to be saved in the temp dir (set timestamp in filename as 1 hour from now so that it gets deleted automatically in 1 hour)
	$inOneHour = date("YmdHis", mktime(date("H")+1,date("i"),date("s"),date("m"),date("d"),date("Y")));

	$target_filename = "{$inOneHour}_pid{$project_id}_".$token.".csv";
	$target_file = APP_PATH_TEMP . $target_filename;

	$time_start = microtime(true);
	$batch_times = array();

    // store all the required session variables.
    $_SESSION['elp'] = array(
        'token' => $token,
        'target_file' => $target_file,
        'target_filename' => $target_filename,
        'batches' => $batches,
        'id_count' => $id_count,
        'batch_total' => $batch_total,
        'curr_batch_count' => 0,
    );

	$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$redirect_url = $actual_link . "&token=" . $token;

	$fh = fopen($target_file, 'w') or die("can't open file");

} else {
	if ($_SESSION['elp']['token'] != $token) {
		echo "<pre>" . "Exiting as tokens are different." ."</pre>";
		exit();
	}
	// $target_file should be fetched from session.
	$target_file = $_SESSION['elp']['target_file'];
	$target_filename = $_SESSION['elp']['target_filename'];

	//fetch $batches from session
	$batches = $_SESSION['elp']['batches'];
	$curr_batch = array_pop($batches);

	// store the remainging batches in session.
	$_SESSION['elp']['batches'] = $batches;
	$batch_times = $_SESSION['elp']['batch_times'];

	//fetch and increment $curr_batch_count
	$curr_batch_count = $_SESSION['elp']['curr_batch_count']+1;
	$_SESSION['elp']['curr_batch_count']++;
	$batch_total = $_SESSION['elp']['batch_total'];
	$id_count = $_SESSION['elp']['id_count'];

	$fh = fopen($target_file, 'a') or die("can't open file");
	$redirect_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

$bar = RCView::div(array('class'=>'chklisthdr','style'=>'color:rgb(128,0,0);margin:10px 0;'), "Exporting Complete CSV");
if ($batch_total > 1) {
    $bar .= RCView::p(array(),"Breaking $id_count records into $batch_total batch exports...");
}

$bar .= RCView::div(array('id'=>'progress','style'=>'width:600px;border:1px solid #ccc;margin-bottom:10px;')) .
        RCView::div(array('id'=>'progress_info','style'=>'width'));

echo RCView::div(array('class'=>'round chklist','id'=>'Large Data Export'), $bar);

$batch_start = microtime(true);
if (!empty($batch_times)) {
	// Calculate projected time remaining
	$batch_avg = floatval(array_sum($batch_times) / count($batch_times));
	$batch_remaining = intval($batch_total - count($batch_times));
	$time_remaining = round($batch_remaining * $batch_avg);
	$time_remaining_msg = "Approximately " . $time_remaining . " seconds remaining...";
}

$percent = '100%';
if ($batch_total) {
    $percent = intval(($curr_batch_count+1)/$batch_total * 100).'%';
}

$msg = "Processing batch " . ($curr_batch_count + 1) . " of $batch_total.";
echo '<script>var batch_number = ' . json_encode($curr_batch_count) . ';</script>';
echo "
<script language='javascript'>
	$('#progress').html('<div style=\"width:$percent;background-color:#cce5ff;\">&nbsp;</div>');
	$('#progress_info').html('$msg<br>$percent complete<br>$time_remaining_msg');
</script>";
echo str_repeat(' ',1024*64);
flush();
ob_flush();

$records = REDCap::getData('csv', $curr_batch);
// Trim the header on all but the first row of the first batch
if ($curr_batch_count != 0) {
	$first_cr = strpos($records, "\n");
	if ($first_cr !== false) {
        $records = substr($records, $first_cr+1);
    }
}

sleep(1);
fwrite($fh, $records);
$batch_times[$curr_batch_count] = microtime(true) - $batch_start;
$_SESSION['elp']['batch_times'] = $batch_times;

fclose($fh);

if ($curr_batch_count+1 == $batch_total || !$batch_total) {
	
    //Batch writing is completed.
    $duration = round(array_sum($batch_times));
    $report = array(
        'export_id: ' . $token,
        'total_records: ' . $id_count,
        'duration: ' . $duration . ' seconds',
        'fields: "' . implode(', ', REDCap::getFieldNames()) . '"',
    );
    REDCap::logEvent('<span style="color: green;">Large project exported completed</span>', implode(",\n", $report));

    $filesize = filesize($target_file);
    if ($filesize < 1024) {
        $filesize .= $filesize > 1 ? ' bytes' : ' byte';
    }
    else {
        $filesize = ($filesize / 1024) . ' kb';
    }

    // Data CSV file download icon
	$html = RCView::form(
		array('method'=>'post', 'name'=>'full_export', 'action'=>$_SERVER['REQUEST_URI'], 'enctype'=>'multipart/form-data'),
		RCView::input(array('type'=>'hidden', 'name'=>'download', 'value'=>$target_filename)) .
		RCView::button(array(
			'name'=>'submit-btn-download',
			'class'=>'jqbutton',
			'style'=>'color:#800000;width:200px;',
			'onclick'=>'$(this).submit();'),"<div style='float:left;padding-top:2px;'>" . trim(DataExport::getDownloadIcon('csv', false)) . "</div><div style='padding-top:8px;'>Download export<div style='font-size:smaller;'>File size: " . $filesize . "</div></div>"
		) . 
		RCView::div(array('style'=>'margin:10px;'),"This file will be automatically deleted from the server in approximately one hour.")
	);

	$id_count = $_SESSION['elp']['id_count'];
	echo "
		<script language='javascript'>
			$('#progress_info').html('Processed $id_count records in " . $duration . " seconds.');
		</script>";
	echo "<br>" . $html;
    echo str_repeat(' ',1024*64);

    // Cleaning up session.
    unset($_SESSION['elp']);

	flush();

} else {
	//reload the page again for processing the next batch.
	redirect($redirect_url);
}

exit();

?>
