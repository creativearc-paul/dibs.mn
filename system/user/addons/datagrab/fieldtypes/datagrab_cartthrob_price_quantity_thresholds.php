<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\Service\Importer;

/**
 * DataGrab cartthrob_price_quantity_thresholds fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_cartthrob_price_quantity_thresholds extends AbstractFieldType
{
    public function register_setting(string $fieldName)
    {
        return [
            $fieldName . "_cartthrob_low",
            $fieldName . "_cartthrob_high"
        ];
    }

    public function display_configuration(Importer $importer, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config["label"] = "<p>" .
            form_label($fieldLabel);
        /*  . NBS .
        anchor("http://brandnewbox.co.uk/support/details/importing_into_playa_fields_with_datagrab", "(?)", 'class="datagrab_help"');
        */
        $config["value"] = "Price: " . NBS . form_dropdown(
                $fieldName, $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName]) ?
                    $data["default_settings"]["cf"][$fieldName] : ''
            ) .
            "</p><p>" . "Low: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_low",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_cartthrob_low"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_cartthrob_low"] : '')
            ) .
            "</p><p>" . "High: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_high",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_cartthrob_high"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_cartthrob_high"] : '')
            ) .
            "</p>";
        return $config;
    }

    public function final_post_data(Importer $importer, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        /*
            [field_id_72] => Array
            (
                [0] => Array
                    (
                        [from_quantity] => 1
                        [up_to_quantity] => 3
                        [price] => 12
                    )

                [1] => Array
                    (
                        [from_quantity] => 4
                        [up_to_quantity] => 10
                        [price] => 10
                    )

                [2] => Array
                    (
                        [from_quantity] => 11
                        [up_to_quantity] => 100
                        [price] => 9
                    )

            )
       */

        // Is this an update?
        if ($updateEntryId) {
            // If so, is this the first update of this import?
            if (in_array($updateEntryId, $importer->entries)) {
                $existing_data = array(
                    "entry_id" => $updateEntryId
                );
                $this->rebuild_post_data($importer, $fieldId, $data, $existing_data);
                $first_row = count($data["field_id_" . $fieldId]);
                // $first_row = 0;
            } else {
                // Initialise data
                $data["field_id_" . $fieldId] = array();
                $first_row = 0;
            }
        } else {
            // Initialise data
            $data["field_id_" . $fieldId] = array();
            $first_row = 0;
        }

        // Can the current datatype handle sub-loops (eg, XML)?
        if ($importer->dataType->datatype_info["allow_subloop"])
        {
            // Check this field can be a sub-loop
            $count = $first_row;
            if ($importer->dataType->initialise_sub_item()) {
                // Loop over sub items
                while ($subitem = $importer->dataType->get_sub_item(
                    $item, $importer->settings["cf"][$fieldName], $importer->settings, $fieldName)) {
                    $row = array(
                        "price" => $subitem
                    );
                    $data["field_id_" . $fieldId][$count++] = $row;
                }
            }

            $count = $first_row;
            if ($importer->dataType->initialise_sub_item()) {
                while ($subitem = $importer->dataType->get_sub_item(
                    $item, $importer->settings["cf"][$fieldName . "_cartthrob_low"], $importer->settings, $fieldName)) {
                    $data["field_id_" . $fieldId][$count++]["from_quantity"] = $subitem;
                }
            }

            $count = $first_row;
            if ($importer->dataType->initialise_sub_item()) {
                while ($subitem = $importer->dataType->get_sub_item(
                    $item, $importer->settings["cf"][$fieldName . "_cartthrob_high"], $importer->settings, $fieldName)) {
                    $data["field_id_" . $fieldId][$count++]["up_to_quantity"] = $subitem;
                }

            }
        }
    }

    public function rebuildPostData(
        Importer $importer,
        int      $fieldId = 0,
        array    $existingData = [],
        array    $entryData = []
    ) {
        // @todo
    }

    public function rebuild_post_data(Importer $importer, int $fieldId = 0, array &$data = [], array $entryData = [])
    {
        ee()->db->select("field_id_" . $fieldId);
        ee()->db->where("entry_id", $entryData["entry_id"]);
        $query = ee()->db->get("exp_channel_data");
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $data["field_id_" . $fieldId] = unserialize(base64_decode($row["field_id_" . $fieldId]));
        }
    }
}
