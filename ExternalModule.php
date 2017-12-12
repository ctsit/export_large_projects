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
            $str = export_large_projects_generate_button($project_id);
            $js_vars = array();
            $js_files = array();

            $js_vars['button_html'] = $str;
            // Set up js variables.
            $this->initJsVars($js_vars);
            $js_files[] = 'js/' . "export_project_button" . '.js';
            // Loads js files.
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
