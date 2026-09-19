<?php

/**
 * DataGrab fieldpack_checkboxes fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_fieldpack_checkboxes extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $data["field_id_" . $fieldId] = array();

        // Can the current datatype handle sub-loops (eg, XML)?
        if ($DG->dataType->datatype_info["allow_subloop"]) {
            // Check this field can be a sub-loop
            if ($DG->dataType->initialise_sub_item(
                $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {
                // Loop over sub items
                $tags = array();
                while ($subitem = $DG->dataType->get_sub_item(
                    $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {
                    foreach (preg_split("/,|\|/", $subitem) as $tag) {
                        $tags[] = trim($tag);
                    }
                }
                $data["field_id_" . $fieldId] = $tags;
            }
        } else {
            $tags = preg_split("/,|\|/", $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]));
            foreach ($tags as $tag) {
                $data["field_id_" . $fieldId] = trim($tag);
            }
        }
    }
    /*
    function rebuild_post_data( $DG, $field_id, &$data, $existing_data ) {
        $data[ "field_id_".$field_id ] = $row["rel_child_id"];
    }
    */
}
