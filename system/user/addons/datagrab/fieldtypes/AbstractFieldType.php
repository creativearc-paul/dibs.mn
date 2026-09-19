<?php
/**
 * DataGrab Fieldtype Class
 *
 * Provides methods to interact with EE fieldtypes
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 **/

abstract class AbstractFieldType
{
    /**
     * Fetch a list of configuration settings that this field type can use
     *
     * @param string $fieldName the field name
     * @return array of configuration setting names
     */
    public function register_setting(string $fieldName)
    {
        return [];
    }

    /**
     * Generate the form elements to configure this field
     *
     * @param Datagrab_model $DG The DataGrab model object
     * @param string $fieldName  the field's name
     * @param string $fieldLabel the field's label
     * @param string $fieldType  the field's type
     * @param bool   $fieldRequired
     * @param array  $data       array of data that can be used to select from
     * @return array containing form's label and elements
     */
    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = array();
        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";
        $config["value"] = form_dropdown($fieldName, $data["data_fields"],
            isset($data["default_settings"]["cf"][$fieldName]) ?
                $data["default_settings"]["cf"][$fieldName] : '');

        return $config;
    }

    /**
     * When saving an import config provide opportunity for the fieldtype eto modify its settings based on what
     * is being saved. For example, Grid fields with no import settings shouldn't save a "1" in the POST array,
     * which then triggers Datagrab_grid->final_post_data() clear out all Grid fields in the entry, even if no
     * the user isn't intending on importing any data into it.
     *
     * @param Datagrab_model $DG
     * @param string         $fieldName
     * @param array          $customFieldSettings
     * @return mixed
     */
    public function save_configuration(Datagrab_model $DG, string $fieldName, array $customFieldSettings = [])
    {
        return $customFieldSettings[$fieldName] ?? '';
    }

    /**
     * Prepare data for posting
     *
     * @param Datagrab_model $DG            The DataGrab model object
     * @param array          $item          The current row of data from the data source
     * @param int            $fieldId       The id of the field
     * @param string         $fieldName     The name of the field
     * @param array          $data          The data array to insert into the channel
     * @param int            $updateEntryId If we have an entryId then it's an update
     */
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
    }

    /**
     * As prepare_post_data but set after the check for existing entries
     *
     * @param Datagrab_model $DG            The DataGrab model object
     * @param array          $item          The current row of data from the data source
     * @param int            $fieldId       The id of the field
     * @param string         $fieldName     The name of the field
     * @param array          $data          The data array to insert into the channel
     * @param int            $updateEntryId If we have an entryId then it's an update
     */
    public function final_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
    }

    /**
     * As prepare_post_data but set after entry has been added
     *
     * @param Datagrab_model $DG            The DataGrab model object
     * @param array          $item          The current row of data from the data source
     * @param int            $fieldId       The id of the field
     * @param string         $fieldName     The name of the field
     * @param array          $data          The data array to insert into the channel
     * @param int            $updateEntryId If we have an entryId then it's an update
     */
    public function post_process_entry(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
    }

    /**
     * Rebuild the POST data of from existing entry
     *
     * @param Datagrab_model $DG
     * @param string         $fieldId
     * @param array          $data
     * @param array          $existingData
     * @return void
     */
    public function rebuild_post_data(Datagrab_model $DG, int $fieldId = 0, array &$data = [], array $existingData = [])
    {
        if (isset($existingData["field_id_" . $fieldId])) {
            $data["field_id_" . $fieldId] = $existingData["field_id_" . $fieldId];
        }
    }

}
