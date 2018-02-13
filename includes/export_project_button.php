<?php
/**
 * @file
 * Provides helper functions for export large projects module.
 */

/**
 * Condition check to run the hook on the page.
 *
 * @return bool
 * checks if hook should be running on the same page or not.
 */
function export_large_projects_condition_check() {

    $webroot_path = APP_PATH_WEBROOT ;

    if (PAGE != "DataExport/index.php") {
        return false;
    }

    $params = $_GET;

    if (array_key_exists("create", $params) || array_key_exists("other_export_options", $params)) {
        return false;
    }
    return true;
}

// Remove the first row from a CSV export
function trimHeader($records) {
	//$left_records = substr($records,0,50);
	$first_cr = strpos($records, "\n");
	if ($first_cr === false) {
		print "ERROR: UNABLE TO FIND HEADER!";
		return $records;
	}
	//$header = substr($records, 0, $first_cr);
	$body = substr($records, $first_cr+1);	
	//$left_body = substr($body,0,50);
	//print "<hr><pre>Left Input: $left_records\nfirst_cr= $first_cr\nLeft part of body after strip: $left_body\n</pre>"; 
	return $body;
}

function export_large_projects_generate_button($pid, $external_module_obj, $fields_per_batch, $max_execution_time) {
    $url = $external_module_obj->getUrl("includes/index.php") 
            . "&fields_per_batch=" . $fields_per_batch . "&max_execution_time=" . $max_execution_time;
    pp1($url);
    $str = "<button class - \"jqbuttonmed ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" "
        . "style=\"display: block; margin-top: 10px;\" " . "onclick=\"window.location.href = '" 
        . $url
        ."'\" role=\"button\"><span class=\"ui-button-text\">"
        . "<img src=\"" . APP_PATH_WEBROOT . "Resources/images/layout.png\" style=\"vertical-align:middle;\">"
        . "<span style=\"vertical-align:middle;\">Export Large Projects</span>"
        . "</span></button>";
    return $str;
}

function pp1($a) {
    echo "<pre>";
    echo print_r($a,1);
    echo "</pre>";
}

?>