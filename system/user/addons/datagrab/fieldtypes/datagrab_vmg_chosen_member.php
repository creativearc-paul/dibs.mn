<?php

/**
 * DataGrab VMG Chosen Member fieldtype class
 * see: https://github.com/vector/VMG-Chosen-Member
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_vmg_chosen_member extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $field_data = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]);
        $data["field_id_" . $fieldId] = explode(",", $field_data);
    }
}
