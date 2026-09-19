<?php

/**
 * DataGrab Matrix fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_matrix extends AbstractFieldType
{
    public function register_setting($field_name)
    {
        return [
            $field_name . "_columns",
            $field_name . "_unique",
            $field_name . "_extra1",
            $field_name . "_extra2"
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = array();

        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        // . BR . anchor("http://brandnewbox.co.uk/support/details/datagrab_and_matrix_fields", "Matrix notes", 'class="datagrab_help"');

        // Get list of matrix columns and map column id to label
        ee()->db->select("col_id, col_label,col_type");
        $query = ee()->db->get("exp_matrix_cols");
        $matrix_columns = array();
        $matrix_column_types = array();
        foreach ($query->result_array() as $row) {
            $matrix_columns[$row["col_id"]] = $row["col_label"];
            $matrix_column_types[$row["col_id"]] = $row["col_type"];
        }

        $cells = form_hidden($fieldName, "1");

        // Loop over all columns
        foreach ($data["field_settings"][$fieldName]["col_ids"] as $col_id) {

            // Get current settings if this is a saved import
            if (isset($data["default_settings"]["cf"][$fieldName . "_columns"])) {
                $default_cells = $data["default_settings"]["cf"][$fieldName . "_columns"];
            } else {
                $default_cells = array();
            }

            // Build configuration interface
            $cells .= "<p>" .
                $matrix_columns[$col_id] . NBS . ":" . NBS;

            $cells .= form_dropdown(
                $fieldName . "_columns[" . $col_id . "]",
                $data["data_fields"],
                isset($default_cells[$col_id]) ? $default_cells[$col_id] : ''
            );

            if ($matrix_column_types[$col_id] == "file") {
                $cells .= NBS . NBS . "Upload folder: " . NBS;

                // Get upload folders
                if (!isset($folders)) {
                    ee()->db->select("id, name");
                    ee()->db->from("exp_upload_prefs");
                    ee()->db->order_by("id");
                    $query = ee()->db->get();
                    $folders = array();
                    foreach ($query->result_array() as $row) {
                        $folders[$row["id"]] = $row["name"];
                    }
                }

                $cells .= form_dropdown(
                    $fieldName . "_extra1[" . $col_id . "]",
                    $folders,
                    isset($data["default_settings"]["cf"][$fieldName . "_extra1"][$col_id]) ? $data["default_settings"]["cf"][$fieldName . "_extra1"][$col_id] : ''
                );
                $cells .= NBS . NBS . "Fetch?: " . NBS;
                $cells .= form_dropdown(
                    $fieldName . "_extra2[" . $col_id . "]",
                    array("No", "Yes"),
                    isset($data["default_settings"]["cf"][$fieldName . "_extra2"][$col_id]) ? $data["default_settings"]["cf"][$fieldName . "_extra2"][$col_id] : ''
                );
            }

            $cells .= "</p>";

        }

        // Pulldown menu to determin what to do for updates
        $column_options = array();
        $column_options["0"] = "Keep existing rows and append new";
        $column_options["-1"] = "Delete all existing rows";
        $sub_options = array();
        foreach ($data["field_settings"][$fieldName]["col_ids"] as $col_id) {
            $sub_options[$col_id] = $matrix_columns[$col_id];
        }
        $column_options["Update the row if this column matches:"] = $sub_options;

        $cells .= "<p>" .
            "Action to take when an entry is updated: " .
            form_dropdown(
                $fieldName . "_unique",
                $column_options,
                (isset($data["default_settings"]["cf"][$fieldName . "_unique"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_unique"] : '')
            ) .
            "</p>";

        // return config interface
        $config["value"] = $cells;
        return $config;
    }

    public function final_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // Fetch fieldtype settings
        // We need to know which matrix columns are used in this matrix
        $field_model = ee('Model')->get('ChannelField', $fieldId)->first();
        $field_settings = $field_model->getSettingsValues();
        $field_settings = $field_settings["field_settings"];

        // Get matrix column details (eg, the column type - playa, text, etc)
        ee()->db->select("*");
        ee()->db->where_in("col_id", $field_settings["col_ids"]);
        $query = ee()->db->get("exp_matrix_cols");
        $columns = array();
        foreach ($query->result_array() as $row) {
            $columns[$row["col_id"]] = $row;
        }

        // $fields contains a list of matrix columns mapped to data elements
        // eg, $fields[3] => 5 means map data element 5 to matrix column 3
        $fields = $DG->settings["cf"][$fieldName . "_columns"];

        // Initialise empty matrix array
        $matrix = array();
        $col_num = 0;
        $empty = true;

        // Loop over columns in matrix
        foreach ($field_settings["col_ids"] as $col_id) {

            if ($fields[$col_id] == "") {
                continue;
            }

            // Loop over data items
            if ($DG->dataType->initialise_sub_item($item, $fields[$col_id], $DG->settings, $fieldName)) {
                $row_num = 0;
                $subItem = $DG->dataType->get_sub_item($item, $fields[$col_id], $DG->settings, $fieldName);

                while ($subItem !== false) {
                    // Pre-fill row matrix with empty values
                    if (!isset($matrix[$row_num])) {
                        $matrix[$row_num] = array();
                        foreach ($field_settings["col_ids"] as $c_id) {
                            if ($fields[$c_id] != "") {
                                // Dont prefill unassigned columns
                                $matrix[$row_num]["col_id_" . $c_id] = "";
                            }
                        }
                    }

                    // Add data to row matrix
                    switch ($columns[$col_id]["col_type"]) {
                        case "playa" :
                        {
                            $matrix[$row_num]["col_id_" . $col_id] = array();
                            ee()->db->select("entry_id");
                            ee()->db->where("title", $subItem);
                            $query = ee()->db->get("exp_channel_titles");
                            if ($query->num_rows() > 0) {
                                $row = $query->row_array();
                                $matrix[$row_num]["col_id_" . $col_id] = array(
                                    "selections" => array(
                                        "", $row["entry_id"]
                                    )
                                );
                                $empty = false;
                            }
                            break;
                        }
                        case "file" :
                        {

                            $matrix[$row_num]["col_id_" . $col_id] = array(
                                "filedir" => "",
                                "filename" => ""
                            );

                            $subItem = $DG->getFile(
                                $subItem,
                                $DG->settings["cf"][$fieldName . "_extra1"][$col_id],
                                $DG->settings["cf"][$fieldName . "_extra2"][$col_id] == 1
                            );

                            if (preg_match('/{filedir_([0-9]+)}/', $subItem, $matches)) {
                                $matrix[$row_num]["col_id_" . $col_id] = array(
                                    "filedir" => $matches[1],
                                    "filename" => str_replace($matches[0], '', $subItem)
                                );
                                $empty = false;
                            }
                            break;
                        }
                        case "assets":
                        {
                            $filename = $subItem;
                            $matrix[$row_num]["col_id_" . $col_id] = array();
                            if (preg_match('/{filedir_([0-9]+)}/', $filename, $matches)) {
                                $file = array(
                                    "filedir" => $matches[1],
                                    "filename" => str_replace($matches[0], '', $filename)
                                );
                                ee()->db->select("file_id");
                                ee()->db->where("file_name", $file["filename"]);
                                ee()->db->where("filedir_id", $file["filedir"]);
                                $query = ee()->db->get("exp_assets_files");
                                if ($query->num_rows() > 0) {
                                    $row = $query->row_array();
                                    $matrix[$row_num]["col_id_" . $col_id][] = $row["file_id"];
                                    $empty = false;
                                }
                            }
                            break;
                        }

                        case "date":
                        {
                            $timestamp = $DG->parseDate($subItem);
                            $date = date("Y-m-d g:i A", $timestamp);// 2011-07-01 1:02 PM
                            $matrix[$row_num]["col_id_" . $col_id] = $date;
                            $empty = false;
                            break;
                        }
                        default:
                        {
                            $matrix[$row_num]["col_id_" . $col_id] = $subItem;
                            if (trim($subItem) != "") $empty = false;
                        }

                    }

                    $subItem = $DG->dataType->get_sub_item($item, $fields[$col_id], $DG->settings, $fieldName);

                    $row_num++;
                }
            }

            $col_num++;
        }

        // var_dump( $field );

        // print "<p>New matrix</p>";
        // print_r( $matrix );

        // Is this updating an existing entry?
        if ($updateEntryId) {
            // Fetch existing data
            $old_matrix = $this->_rebuild_matrix_data($updateEntryId, $DG, $fieldId);

            // print "<p>Old matrix</p>";
            // var_dump( $old_matrix );

            // Find out what to do with existing data (delete or keep?)
            $unique = 0;
            if (isset($DG->settings["cf"][$fieldName . "_unique"])) {
                $unique = $DG->settings["cf"][$fieldName . "_unique"];
            }

            // print "<p>Unique</p>";
            // var_dump( $unique );

            // Is this the first update in this import?
            if (!in_array($updateEntryId, $DG->entries) && $unique == -1) {
                // Delete existing matrix rows
                $data["field_id_" . $fieldId]["deleted_rows"] = array();
                foreach ($old_matrix as $key => $value) {
                    if (substr($key, 0, 7) == "row_id_") {
                        $data["field_id_" . $fieldId]["deleted_rows"][] = $key;
                    }
                }
            }
        }

        // Rebuild existing matrix data
        $data["field_id_" . $fieldId]["row_order"] = array();
        if ($updateEntryId) {
            foreach ($old_matrix as $key => $mrow) {
                if (substr($key, 0, 7) == "row_id_") {
                    $data["field_id_" . $fieldId][$key] = $mrow;
                    $data["field_id_" . $fieldId]["row_order"][] = $key;
                }
            }
        }

        // If there is some new matrix data, then add it to the existing
        if (!$empty) {
            foreach ($matrix as $row_num => $mrow) {
                $found = false;
                if ($updateEntryId && $unique > 0) {
                    // Check whether this is a new row or an update
                    foreach ($old_matrix as $key => $row) {
                        if (substr($key, 0, 7) == "row_id_") {
                            $col_type = $columns[$unique]['col_type'];
                            switch ($col_type) {
                                case "date" :
                                {
                                    if ($DG->parseDate($row["col_id_" . $unique]) == $DG->parseDate($mrow["col_id_" . $unique])) {
                                        $data["field_id_" . $fieldId][$key] = $mrow;
                                        $found = true;
                                    }
                                    break;
                                }
                                default:
                                {
                                    if ($row["col_id_" . $unique] == $mrow["col_id_" . $unique]) {
                                        $data["field_id_" . $fieldId][$key] = array_merge($data["field_id_" . $fieldId][$key], $mrow);
                                        $found = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$found) {
                    $data["field_id_" . $fieldId]["row_new_" . $row_num] = $mrow;
                    $data["field_id_" . $fieldId]["row_order"][] = "row_new_" . $row_num;
                }
            }
        }

        if (!isset($data["field_id_" . $fieldId])) {
            $data["field_id_" . $fieldId] = array();
            $data["field_id_" . $fieldId]["row_order"] = array();
        }

        /*
        [field_id_63] => Array (
            [row_order] => Array (
                [0] => row_new_0
                [1] => row_id_5571
            )
            [row_new_0] => Array (
                [col_id_8] => Array (
                    [filedir] => 1
                    [filename] => bnb4.png
                )
                [col_id_7] => fdsfsdfsd
            )
            [row_id_5571] => Array (
                [col_id_8] => Array (
                    [filedir] => 1
                    [filename] => bnb3.png
                )
                [col_id_7] => Label 1
            )
            [deleted_rows] => Array (
                [0] => row_id_5568
            )
        )
        */

    }

    // Rebuild array (format playa, dates and files)
    private function _rebuild_matrix_data($updateEntryId, $DG, $fieldId)
    {
        // Find columns for this field
        // Fetch fieldtype settings
        $field_model = ee('Model')->get('ChannelField', $fieldId)->first();
        $field_settings = $field_model->getSettingsValues();
        $field_settings = $field_settings["field_settings"];

        $col_ids = $field_settings["col_ids"];

        // Get matrix column details
        ee()->db->select("*");
        ee()->db->where_in("col_id", $field_settings["col_ids"]);
        $query = ee()->db->get("exp_matrix_cols");
        $columns = array();
        foreach ($query->result_array() as $row) {
            $columns[$row["col_id"]] = $row;
        }

        $where = array(
            'entry_id' => $updateEntryId,
            'field_id' => $fieldId
        );

        // -------------------------------------------
        //  'ajw_datagrab_rebuild_matrix_query' hook
        //
        if ($DG->extensions->active_hook('ajw_datagrab_rebuild_matrix_query')) {
            $DG->logger->log('Calling ajw_datagrab_rebuild_matrix_query() hook.');
            $query = $DG->extensions->call('ajw_datagrab_rebuild_matrix_query', $where);
        } else {
            // Get existing matrix entries
            ee()->db->select("*");
            ee()->db->where($where);
            ee()->db->order_by("row_order");
            $query = ee()->db->get("exp_matrix_data");
        }
        //
        // -------------------------------------------

        $data = array();
        $data["row_order"] = array();
        foreach ($query->result_array() as $row) {
            $matrix_row = array();
            foreach ($col_ids as $col_id) {
                switch ($columns[$col_id]["col_type"]) {
                    case "playa":
                    {
                        $playa = $row["col_id_" . $col_id];
                        $matrix_row["col_id_" . $col_id]["selections"] = array(
                            "0" => ""
                        );
                        $pno = 1;
                        foreach (preg_split("/[\r\n]+/", $playa) as $prow) {
                            $prow = substr($prow, 1, strpos($prow, ']') - 1);
                            $matrix_row["col_id_" . $col_id]["selections"][$pno] = $prow;
                            $pno++;
                        }
                        break;
                    }
                    case "date":
                    {
                        $matrix_row["col_id_" . $col_id] = ee()->localize->human_time($row["col_id_" . $col_id]);
                        break;
                    }
                    case "file":
                    {
                        $filename = $row["col_id_" . $col_id];
                        $matrix_row["col_id_" . $col_id] = "";
                        if (preg_match('/{filedir_([0-9]+)}/', $filename, $matches)) {
                            $matrix_row["col_id_" . $col_id] = array(
                                "filedir" => $matches[1],
                                "filename" => str_replace($matches[0], '', $filename)
                            );
                        }
                        break;
                    }
                    case "assets":
                    {
                        $filename = $row["col_id_" . $col_id];
                        $matrix_row["col_id_" . $col_id] = array();
                        if (preg_match('/{filedir_([0-9]+)}/', $filename, $matches)) {
                            $file = array(
                                "filedir" => $matches[1],
                                "filename" => str_replace($matches[0], '', $filename)
                            );
                            ee()->db->select("file_id");
                            ee()->db->where("file_name", $file["filename"]);
                            ee()->db->where("filedir_id", $file["filedir"]);
                            $query = ee()->db->get("exp_assets_files");
                            if ($query->num_rows() > 0) {
                                $row = $query->row_array();
                                $matrix_row["col_id_" . $col_id][] = $row["file_id"];
                            }
                        }
                        break;
                    }
                    default:
                    {
                        $matrix_row["col_id_" . $col_id] = $row["col_id_" . $col_id];
                    }
                }
            }

            $data["row_id_" . $row["row_id"]] = $matrix_row;
            $data["row_order"][] = "row_id_" . $row["row_id"];
        }

        return $data;
    }
}
