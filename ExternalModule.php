<?php
/**
 * @file
 * Provides ExternalModule class for Exporting Large Projects.
 */

namespace ExportLargeProjects\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Exporting Large Projects.
 */
class ExternalModule extends AbstractExternalModule {

    /**
     * @inheritdoc
     */
    function hook_every_page_top($project_id) {
        include_once 'includes/export_project_button.php';
        if (export_large_projects_condition_check()) {
            $fields_per_batch = $this->getProjectSetting('fields-per-batch', $Proj->project_id);
            $max_execution_time = $this->getProjectSetting('max-execution-time', $Proj->project_id);
            $str = export_large_projects_generate_button($project_id, $this, $fields_per_batch, $max_execution_time);
            
            // Set up js variables.
            $js_vars = array();            
            $js_vars['button_html'] = $str;
            $this->initJsVars($js_vars);

            // Loads js files.
            $js_files = array();
            $js_files[] = 'js/' . "export_project_button" . '.js';
            $this->loadJsFiles($js_files);
        }
    }

    /**
     * Loads js files.
     *
     * @param array $js_files
     *   An array of js files paths within the module.
     */
    function loadJsFiles($js_files) {
        foreach ($js_files as $file) {
            echo '<script src="' . $this->getUrl($file) . '"></script>';
        }
    }

    /**
     * Loads js variables.
     *
     * @param array $varss
     *   An array of js variables to set up.
     */
     function initJsVars($vars) {
        echo '<script>var exportLargeProjects = ' . json_encode($vars) . ';</script>';
    }

}
