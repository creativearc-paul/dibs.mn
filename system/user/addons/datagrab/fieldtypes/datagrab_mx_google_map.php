<?php

/**
 * DataGrab MX Google Map fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_mx_google_map extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $field_data = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]);
        $coords = explode("|", $field_data);

        $data["field_id_" . $fieldId] = array(
            "field_data" => $field_data,
            "order" => array(
                "0" => 564
            ),
            "564" => array(
                "address" => "",
                "city" => "",
                "zipcode" => "",
                "state" => "",
                "long" => $coords[1],
                "icon" => "",
                "lat" => $coords[0]
            )
        );
    }
}
