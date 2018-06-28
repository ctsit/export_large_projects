# Export Large Project
This is a REDCap external module that provides functionality to export large projects.

## Easy Installation
- Obtain this module from the Consortium [REDCap Repo] (https://redcap.vanderbilt.edu/consortium/modules/index.php) from the Control Center.

## Manual Installation
- Clone this repo into `<redcap-root>/modules/export_large_projects_v0.0.0`.
- Go to **Control Center > Manage External Modules** and enable Export Large Projects.
- Go to your project home page, click on **Manage External Modules** link, and then enable Export Large Projects.

## Configuration
Access External Modules from the Control Panel to set the number of fields exported in each batch and the maximum execution time for the entire export.

![Config form](images/configuration.png)

## How to use?
Once this module is enabled for a project, go to "Data Exports, Reports, and Stats" section. In here, the Export Large Projects button is presented as shown in the below image.
Click that button to start the export.  A progress indicator will show the stepwise export of the data. After all the chunks have been exported and assembled into a single CSV, a download link is presented to download the entire file.

![Export Large Projects button](images/export_large_project_button.png)

![Download page](images/download.png)
