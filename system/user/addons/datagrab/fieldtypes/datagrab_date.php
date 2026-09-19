<?php

/**
 * DataGrab Date fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_date extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        if ($DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]) != "") {
            $data["field_id_" . $fieldId] = $DG->parseDate(
                $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName])
            );
            $data["field_id_" . $fieldId] -= $DG->settings["config"]["offset"];
        } else {
            $data["field_id_" . $fieldId] = "";
        }

        $data["field_offset_" . $fieldId] = 'n';
    }
}
