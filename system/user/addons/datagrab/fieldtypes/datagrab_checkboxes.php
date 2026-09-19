<?php

/**
 * DataGrab Checkbox fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_checkboxes extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $values = array();

        // Can the current datatype handle sub-loops (eg, XML)?
        if ($DG->dataType->datatype_info["allow_subloop"]) {
            // Check this field can be a sub-loop
            if ($DG->dataType->initialise_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {
                // Loop over sub items
                while ($subitem = $DG->dataType->get_sub_item(
                    $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {

                    $subitem = str_replace("|", ",", $subitem);
                    foreach (explode(",", $subitem) as $titem) {
                        $values[] = trim($titem);
                    }
                }
            }
        } else {
            $subitem = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]);
            $subitem = str_replace("|", ",", $subitem);

            foreach (explode(",", $subitem) as $titem) {
                $values[] = trim($titem);
            }
        }

        $data["field_id_" . $fieldId] = $values;
    }
}
