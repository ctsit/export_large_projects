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

function export_large_projects_generate_button($pid) {

    $str = "<button class - \"jqbuttonmed ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" "
            . "style=\"display: block; margin-top: 10px;\" "
            . "onclick=\"window.location.href = '" . APP_PATH_WEBROOT . "DataExport/index.php?pid=" . $pid . "&amp;report_id=ALL'"
            . "+getSelectedInstrumentList();\" role=\"button\"><span class=\"ui-button-text\">"
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