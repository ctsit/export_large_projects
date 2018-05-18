<?php
/**
 * @file
 * Provides ExternalModule class for Exporting Large Projects.
 */

namespace ExportLargeProjects\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use RCView;

/**
 * ExternalModule class for Exporting Large Projects.
 */
class ExternalModule extends AbstractExternalModule {

    /**
     * @inheritdoc
     */
    function hook_every_page_top($project_id) {
        if (PAGE == 'DataExport/index.php' && !isset($_GET['create']) && !isset($_GET['other_export_options'])) {
            // Setting up button.
            $this->buildExportButton($project_id);
        }
    }

    /**
     * Display an error page containing the given message.
     */
    function renderErrorPage($error_msg) {
        displayMsg($error_msg, 'errorMsg', 'center', 'red', 'exclamation.png', null, false);
        include_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
        ob_end_flush();
        exit;
    }

    /**
     * Throws an access denied error message if the current user has no access
     * to Export Large Projects.
     */
    function checkExportAccess() {
        if (SUPER_USER) {
            return;
        }

        $rights = REDCap::getUserRights(USERID);
        if (!empty($rights[USERID]['data_export_tool'])) {
            return;
        }

        $this->renderErrorPage('Access denied.');
    }

    /**
     * Loads a local JS file.
     *
     * @param array $path
     *   The relative path of the JS file.
     */
    function loadJsFile($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Loads a local CSS file.
     *
     * @param string $path
     *   The relative path to the css file.
     */
    function loadCssFile($path) {
        echo '<link rel="stylesheet" href="' . $this->getUrl($path) . '">';
    }

    /**
     * Loads JS variables.
     *
     * @param array $vars
     *   An array of JS variables to set up.
     */
    function initJsVars($vars) {
       echo '<script>var exportLargeProjects = ' . json_encode($vars) . ';</script>';
    }

    /**
     * Calls a JS function implemented in the module global object.
     *
     * @param string $function
     *   The callback function.
     * @param array $params
     *   Array containing callback parameters.
     */
    function callJsCallback($function, $params = array()) {
        $input = empty($params) ? '' : '\'' . implode('\', \'', $params) . '\'';
        echo '<script>if (typeof exportLargeProjects.' . $function . ' !== \'undefined\') exportLargeProjects.' . $function . '(' . $input . ');</script>';
    }

    /**
     * Builds up Export Large Projects button.
     */
    protected function buildExportButton($project_id) {
        $url = $this->getUrl('plugins/export.php');
        foreach (array('fields_per_batch', 'max_execution_time_per_batch') as $setting) {
            $url .= '&' . $setting . '=' . $this->getProjectSetting($setting);
        }

        $contents = RCView::img(array('src' => APP_PATH_IMAGES . 'go-down.png')) . ' ' . RCView::span(array(), 'Export Large Projects');
        $contents = RCView::span(array('class' => 'ui-button-text'), $contents);
        $contents = RCView::button(array(
            'id' => 'export-large-projects-btn',
            'class' => 'jqbuttonmed ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
            'type' => 'button',
            'role' => 'button',
        ), $contents);

        $this->initJsVars(array(
            'buttonHtml' => $contents,
            'url' => $url,
        ));

        $this->loadCssFile('css/export_project_button.css');
        $this->loadJsFile('js/export_project_button.js');
    }
}
