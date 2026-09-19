<?php

/**
 * DataGrab MX Google Map fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_pt_multiselect extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $fieldData = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]);

        if ($fieldData == "") {
            $data["field_id_" . $fieldId] = "n";
        } else {
            $data["field_id_" . $fieldId] = explode("|", $fieldData);
        }
    }
}
