<?php

/**
 * DataGrab Relationship fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_rel extends AbstractFieldType
{
    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        ee()->db->where('channel_id', ee()->api_channel_fields->settings[$fieldId]['field_related_id']);
        ee()->db->where('title', $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName]));
        ee()->db->select('entry_id');
        $query = ee()->db->get('exp_channel_titles');
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $data["field_id_" . $fieldId] = $row["entry_id"];
        }
    }

    function rebuild_post_data(Datagrab_model $DG, int $fieldId = 0, array &$data = [], array $existingData = [])
    {
        $rel_id = $existingData["field_id_" . $fieldId];

        if ($rel_id != "") {
            // Fetch relationships from exp_relationships
            ee()->db->select("rel_child_id");
            ee()->db->where("rel_id", $rel_id);
            $query = ee()->db->get("exp_relationships");

            if ($query->num_rows() > 0) {
                $row = $query->row_array();

                // Rebuild selections array
                $data["field_id_" . $fieldId] = $row["rel_child_id"];
            }
        }
    }
}
