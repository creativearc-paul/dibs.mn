<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\App;

/**
 * DataGrab File fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_file extends AbstractFieldType
{
    public function register_setting(string $field_name): array
    {
        return [
            $field_name . "_filedir",
            $field_name . "_fetch"
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $default = [];

        // Get current saved setting
        if (isset($data["default_settings"]["cf"])) {
            $default = $data["default_settings"]["cf"];
        }

        // Get upload folders
        ee()->db->select("id, name");
        ee()->db->from("exp_upload_prefs");
        ee()->db->order_by("id");
        $query = ee()->db->get();
        $folders = [];

        foreach ($query->result_array() as $row) {
            $folders[$row["id"]] = $row["name"];
        }

        // Build config form
        $config = array();
        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= form_label($fieldType) . NBS .
            anchor("https://docs.boldminded.com/datagrab/docs/field-types/file", "(?)", 'class="datagrab_help"');
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        if (App::isGteEE7() && !bool_config_item('file_manager_compatibility_mode')) {
            $defaultValue = $default[$fieldName . "_filedir"] ?? 0;

            // We have an old pre-EE7 file format
            if (strpos($defaultValue, '.') === false) {
                $defaultValue = $defaultValue . '.0';
            }

            $uploadFolderOptions = '<div class="multilevel-select dg-directories">Upload folder: ' . ee('View')->make('ee:_shared/form/fields/dropdown')->render([
                    'field_name' => $fieldName . '_filedir',
                    'choices' => $this->getUploadLocationsAndDirectoriesDropdownChoices(),
                    'value' => $defaultValue ?? '',
                    'fileManager' => true,
                ]) . '</div>';
        } else {
            $uploadFolderOptions = "<p>Upload folder: " . NBS .
                form_dropdown(
                    $fieldName . "_filedir",
                    $folders,
                    $default[$fieldName . "_filedir"] ?? ''
                ) .
                "</p>";
        }

        $config["value"] = "<p>" .
            form_dropdown(
                $fieldName,
                $data["data_fields"],
                $default[$fieldName] ?? ''
            ) .
            "</p>
            " . $uploadFolderOptions . "
            <p>Fetch files from urls: " . NBS .
            form_dropdown(
                $fieldName . "_fetch",
                array("No", "Yes"),
                $default[$fieldName . "_fetch"] ?? ''
            ) .
            "</p>";

        return $config;
    }

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $data["field_id_" . $fieldId] = "";

        // Fetch file from data
        if ($DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]) != "") {

            $filename = $DG->getFile(
                $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]),
                $DG->settings["cf"][$fieldName . '_filedir'],
                $DG->settings["cf"][$fieldName . '_fetch'] == 1
            );

            if ($filename) {
                $data[sprintf('field_id_%d', $fieldId)] = $filename;
            }
        }
    }

    /**
     * Copied from FileManagerTrait, but to keep backwards compatibility with v6, we can't use the Trait :(
     *
     * @return array
     */
    public function getUploadLocationsAndDirectoriesDropdownChoices()
    {
        $uploadLocationsAndDirectoriesDropdownChoices = [];

        if (ee('Permission')->can('upload_new_files')) {
            $upload_destinations = ee('Model')->get('UploadDestination')
                ->fields('id', 'name', 'adapter')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('module_id', 0)
                ->order('name', 'asc')
                ->all();

            if (! ee('Permission')->isSuperAdmin()) {
                $member = ee()->session->getMember();
                $upload_destinations = $upload_destinations->filter(function ($dir) use ($member) {
                    return $dir->memberHasAccess($member);
                });
            }

            foreach ($upload_destinations as $upload_pref) {
                $uploadLocationsAndDirectoriesDropdownChoices[$upload_pref->getId() . '.0'] = [
                    'label' => '<i class="fal fa-hdd"></i>' . $upload_pref->name,
                    'upload_location_id' => $upload_pref->id,
                    'adapter' => $upload_pref->adapter,
                    'directory_id' => 0,
                    'path' => '',
                    'children' => !bool_config_item('file_manager_compatibility_mode') ? $upload_pref->buildDirectoriesDropdown($upload_pref->getId(), true) : []
                ];
            }
        }
        return $uploadLocationsAndDirectoriesDropdownChoices;
    }
}
