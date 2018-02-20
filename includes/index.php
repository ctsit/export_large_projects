<?php
/**

This is test script to download an entire project that is causing memory issues...

**/


// require_once \ExternalModules\ExternalModules::getProjectHeaderPath();


//error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Enable flushing of output for progress meter...
ini_set("zlib.output_compression", 0);	// off
ini_set("implicit_flush", 1);			// on

$max_execution_time = $_GET["max_execution_time"];
ini_set('max_execution_time', $max_execution_time);	//1800 seconds = 30 minutes

$fields_per_batch_value = $_GET["fields_per_batch"];
$fields_per_batch = $fields_per_batch_value;
// pp1($fields_per_batch);

// // Connect to REDCap
// require_once "../../redcap_connect.php";

// // include export project button php file
// include_once "export_project_button.php";

// Check user privs
if (!SUPER_USER) {
	$rights = REDCap::getUserRights(USERID);
	$rights = $rights[USERID];
	//echo "<pre>" . print_r($rights,true) . "</pre>";
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
//	unlink($target_file);
	REDCap::logEvent("Full Export Downloaded");
	exit();
}
require_once \ExternalModules\ExternalModules::getProjectHeaderPath();

// Connect to REDCap
require_once "../../redcap_connect.php";

// include export project button php file
include_once "export_project_button.php";
// // Render the page
// include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
//header( 'Content-type: text/html; charset=utf-8' );

// Get name of first field:
$fields = REDCap::getFieldNames();
$field_count = count($fields);
$first_field = array_shift($fields);
// echo "First field of $field_count is $first_field\n";

// Get all record_ids
$ids = REDCap::getData('array',NULL,array($first_field));
$ids = array_keys($ids);
$id_count = count($ids);
//echo "Records are: <pre>" . print_r($ids,true) . "</pre>";
//echo "\nRecord count is " . count($ids);

// Determine batching size (with a minimum of 1 record per export).
$records_per_batch = max(1,floor($fields_per_batch / $field_count));
$batches = array_chunk($ids,$records_per_batch);
$batch_total = count($batches);
// print "<pre>" . print_r($batches,true) . "</pre>";

// Create temp file - Set the target file to be saved in the temp dir (set timestamp in filename as 1 hour from now so that it gets deleted automatically in 1 hour)
$inOneHour = date("YmdHis", mktime(date("H")+1,date("i"),date("s"),date("m"),date("d"),date("Y")));
// pp1($inOneHour);
$target_filename = "{$inOneHour}_pid{$project_id}_".generateRandomHash(6).".csv";
$target_file = APP_PATH_TEMP . $target_filename;
// pp1($target_file);

echo RCView::div(array('class'=>'round chklist','id'=>'Large Data Export'),
RCView::div(array('class'=>'chklisthdr','style'=>'color:rgb(128,0,0);margin-top:10px;'), "Exporting Complete CSV").
RCView::p(array(),"Breaking $id_count records into $batch_total batch exports...").
RCView::div(array('id'=>'progress','style'=>'width:600px;border:1px solid #ccc;margin-bottom:10px;')).
RCView::div(array('id'=>'progress_info','style'=>'width'))
);

// Start Export
REDCap::logEvent("Full Export Requested");
$fh = fopen($target_file, 'w') or die("can't open file");
$time_start = microtime(true);
$batch_times = array();

foreach ($batches as $b => $batch) {
	print "<pre>Batch $b starts at " . $batch[0] . "</pre>";
	$batch_start = microtime(true);
	if (!empty($batch_times)) {
		// Calculate projected time remaining
		$batch_avg = floatval(array_sum($batch_times) / count($batch_times));
		$batch_remaining = intval($batch_total - count($batch_times));
		$time_remaining = round($batch_remaining * $batch_avg);
		$time_remaining_msg = "Approximately " . $time_remaining . " seconds remaining...";
	}
	$percent = intval(($b+1)/$batch_total * 100).'%';
	$msg = "Processing batch " . ($b + 1) . " of $batch_total.";
	echo "
	<script language='javascript'>
		//console.log($('#progress').html());
		$('#progress').html('<div style=\"width:$percent;background-color:#ddd;\">&nbsp;</div>');
		$('#progress_info').html('$msg<br>$percent complete<br>$time_remaining_msg');
	</script>";
	echo str_repeat(' ',1024*64);
	flush();
	ob_flush();
//if ($b == 0) {
	$records = REDCap::getData('csv', $batch);	
	// Trim the header on all but the first row of the first batch
	if ($b != 0) $records = trimHeader($records);
	//print "Records: <pre>" . print_r($records,true) . "</pre>";
	fwrite($fh, $records);
//}
	$batch_times[$b] = microtime(true) - $batch_start;
}
fclose($fh);

// Data CSV file download icon
$html = RCView::form(
	array('method'=>'post', 'name'=>'full_export', 'action'=>$_SERVER['REQUEST_URI'], 'enctype'=>'multipart/form-data'),
	RCView::input(array('type'=>'hidden', 'name'=>'download', 'value'=>$target_filename)) .
	RCView::button(array(
		'name'=>'submit-btn-download',
		'class'=>'jqbutton',
		'style'=>'color:#800000;width:200px;',
		'onclick'=>'$(this).submit();'),"<div style='float:left;padding-top:2px;'>" . trim(DataExport::getDownloadIcon('csv', false)) . "</div><div style='padding-top:8px;'>Download export<div style='font-size:smaller;'>Filesize: " . round(filesize($target_file)/1024) . "kb</div></div>"
	) . 
	RCView::div(array('style'=>'margin:10px;'),"This file will be automatically deleted from the server in approximately one hour")
);

$msg = "Processed $id_count records in " . round(array_sum($batch_times)) . " seconds.";
echo "
	<script language='javascript'>
		$('#progress_info').html('$msg');
	</script>";
echo "<br>" . $html;
echo str_repeat(' ',1024*64);
flush();

//print "\nRecords: $records";
//echo "\n".print_r($records,true);
exit();


pp1("It works!");



?>