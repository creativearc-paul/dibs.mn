<?php

/**
 * DataGrab Toggle fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_toggle extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $value = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]);
        $data["field_id_" . $fieldId] = 0;

        if ($value == "y" || $value == "yes" || $value == "true" || $value == 1 || $value == "on") {
            $data["field_id_" . $fieldId] = 1;
        }
    }
}
