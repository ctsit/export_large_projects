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
     * Builds up Export Large Projects button.
     */
    protected function buildExportButton($project_id) {
        $url = $this->getUrl('includes/index.php');
        $url .= '&fields_per_batch=' . $this->getProjectSetting('fields-per-batch', $project_id);
        $url .= '&max_execution_time=' . $this->getProjectSetting('max-execution-time', $project_id);

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

    /**
     * Loads a local JS file.
     *
     * @param array $path
     *   The relative path of the JS file.
     */
    protected function loadJsFile($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Loads a local CSS file.
     *
     * @param string $path
     *   The relative path to the css file.
     */
    protected function loadCssFile($path) {
        echo '<link rel="stylesheet" href="' . $this->getUrl($path) . '">';
    }

    /**
     * Loads JS variables.
     *
     * @param array $vars
     *   An array of JS variables to set up.
     */
     protected function initJsVars($vars) {
        echo '<script>var exportLargeProjects = ' . json_encode($vars) . ';</script>';
    }

}
