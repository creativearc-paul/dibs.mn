<?php

/**
 * DataGrab Assets fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_assets extends AbstractFieldType
{

    public function prepare_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $data["field_id_" . $fieldId] = array();

        // Can the current datatype handle sub-loops (eg, XML)?
        if (
            $DG->dataType->datatype_info["allow_subloop"] &&
            $DG->dataType->initialise_sub_item($item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)
        ) {
            // Loop over sub items
            while ($subitem = $DG->dataType->get_sub_item(
                $item, $DG->settings["cf"][$fieldName], $DG->settings, $fieldName)) {

                if (preg_match('/{filedir_([0-9]+)}/', $subitem, $matches)) {
                    $file = array(
                        "filedir" => $matches[1],
                        "filename" => str_replace($matches[0], '', $subitem)
                    );

                    ee()->db->select("file_id");
                    ee()->db->where("file_name", $file["filename"]);
                    ee()->db->where("filedir_id", $file["filedir"]);
                    $query = ee()->db->get("exp_assets_files");
                    if ($query->num_rows() > 0) {
                        $row = $query->row_array();
                        $data["field_id_" . $fieldId][] = $row["file_id"];
                    }
                } else {
                    ee()->db->select("file_id");
                    ee()->db->where("file_name", $subitem);
                    $query = ee()->db->get("exp_assets_files");
                    if ($query->num_rows() > 0) {
                        $row = $query->row_array();
                        $data["field_id_" . $fieldId][] = $row["file_id"];
                    }
                }

            }
        }
    }

    public function rebuild_post_data(Datagrab_model $DG, int $fieldId = 0, array &$data = [], array $existingData = [])
    {
        $data["field_id_" . $fieldId] = array();

        $where = array(
            'entry_id' => $existingData["entry_id"],
            'field_id' => $fieldId
        );

        // -------------------------------------------
        //  'ajw_datagrab_rebuild_assets_query' hook
        //
        if ($DG->extensions->active_hook('ajw_datagrab_rebuild_assets_query')) {
            $DG->logger->log('Calling ajw_datagrab_rebuild_assets_query() hook.');
            $query = $DG->extensions->call('ajw_datagrab_rebuild_assets_query', $where);
        } else {
            ee()->db->select("file_id");
            ee()->db->from("exp_assets_selections");
            ee()->db->where($where);
            ee()->db->order_by("sort_order");
            $query = ee()->db->get();
        }
        //
        // -------------------------------------------

        foreach ($query->result_array() as $row) {
            $data["field_id_" . $fieldId][] = $row["file_id"];
        }
    }
}
