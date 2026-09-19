<?php

/**
 * DataGrab Tagger fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_tagger extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        /*
            [field_id_124] => Array (
          [tags] => Array (
            [0] => hello
            [1] => another
          )
            )
        */

        $data["field_id_" . $fieldId]["tags"] = array();

        // Can the current datatype handle sub-loops (eg, XML)?
        if (
            $DG->dataType->datatype_info["allow_subloop"] &&
            $DG->dataType->initialise_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)
        ) {
            // Loop over sub items
            $tags = array();
            while ($subitem = $DG->dataType->get_sub_item(
                $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {

                foreach (explode(",", $subitem) as $titem) {
                    $tags[] = trim($titem);
                }
            }

            $data["field_id_" . $fieldId]["tags"] = $tags;
        } else {
            foreach (explode(",", $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName])) as $titem) {
                $tags[] = trim($titem);
            }
        }
    }
}
