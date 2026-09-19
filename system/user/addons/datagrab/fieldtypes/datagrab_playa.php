<?php

/**
 * DataGrab Playa fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_playa extends AbstractFieldType
{
    public function register_setting(string $field_name): array
    {
        return [$field_name . "_playa_field"];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";
        //  . BR .
        //		anchor("http://brandnewbox.co.uk/support/details/importing_into_playa_fields_with_datagrab", "Playa notes", 'class="datagrab_help"');
        $config["value"] = "<p>" . form_dropdown(
                $fieldName, $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName]) ?
                    $data["default_settings"]["cf"][$fieldName] : ''
            )
            . "</p><p>Field to match: " . NBS .
            form_dropdown(
                $fieldName . "_playa_field",
                $data["all_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_playa_field"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_playa_field"] : '')
            ) . "</p>";
        return $config;
    }

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // Fetch fieldtype settings
        $field_model = ee('Model')->get('ChannelField', $fieldId)->first();
        $field_settings = $field_model->getSettingsValues();

        // Initialise playa post data
        $data["field_id_" . $fieldId] = array();
        // $data[ "field_id_" . $field_id ]["old"] = "";
        $data["field_id_" . $fieldId]["selections"] = array();
        //$data[ "field_id_" . $field_id ]["selections"][] = "";

        // Can the current datatype handle sub-loops (eg, XML)?
        if (
            $DG->dataType->datatype_info["allow_subloop"] &&
            $DG->dataType->initialise_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)
        ) {
            // Loop over sub items
            while ($subitem = $DG->dataType->get_sub_item(
                $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {

                // Check whether item matches a valid entry and create a playa relationship
                ee()->db->select('exp_channel_titles.entry_id');
                ee()->db->join('exp_channel_data', 'exp_channel_titles.entry_id = exp_channel_data.entry_id');
                if (isset($field_settings["channels"])) {
                    ee()->db->where_in('exp_channel_titles.channel_id', $field_settings["channels"]);
                }
                if (!isset($DG->settings["cf"][$fieldName . "_playa_field"])) {
                    ee()->db->where('title', $subitem);
                } else {
                    ee()->db->where($DG->settings["cf"][$fieldName . "_playa_field"], $subitem);
                }
                $query = ee()->db->get('exp_channel_titles');
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $data["field_id_" . $fieldId]["selections"][] = $row["entry_id"];
                }
            }
        }
    }

    public function rebuild_post_data(Datagrab_model $DG, int $fieldId = 0, array &$data = [], array $existingData = [])
    {
        $where = array(
            'parent_entry_id' => $existingData["entry_id"],
            'parent_field_id' => $fieldId
        );

        // -------------------------------------------
        //  'ajw_datagrab_rebuild_playa_query' hook
        //
        if ($DG->extensions->active_hook('ajw_datagrab_rebuild_playa_query')) {
            $DG->logger->log('Calling ajw_datagrab_rebuild_playa_query() hook.');
            $query = $DG->extensions->call('ajw_datagrab_rebuild_playa_query', $where);
        } else {
            // Fetch relationships from exp_playa_relationships
            ee()->db->select("child_entry_id");
            ee()->db->where($where);
            $query = ee()->db->get("exp_playa_relationships");
        }
        //
        // -------------------------------------------

        $selections = array();
        foreach ($query->result_array() as $row) {
            $selections[] = $row["child_entry_id"];
        }

        // Rebuild selections array
        $data["field_id_" . $fieldId] = array(
            "selections" => $selections
        );
    }
}
