<?php
/**
 * DataGrab Fieldtype Class
 *
 * Provides methods to interact with EE fieldtypes
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 **/

class Datagrab_zoo_visitor extends AbstractFieldType
{
    public function register_setting(string $field_name): array
    {
        return [
            $field_name . "_zoo_password",
            $field_name . "_zoo_email",
            $field_name . "_zoo_username",
            $field_name . "_zoo_screen_name",
            $field_name . "_zoo_member_group"
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $hidden = form_hidden($fieldName, "1");

        ee()->db->select("group_id, group_title");
        ee()->db->from("exp_member_groups");
        ee()->db->order_by("group_id ASC");
        $query = ee()->db->get();
        $groups = [];
        foreach ($query->result_array() as $row) {
            $groups[$row["group_id"]] = $row["group_title"];
        }

        $username = "<p>Username: " . NBS . form_dropdown($fieldName . '_zoo_username', $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . '_zoo_username']) ?
                    $data["default_settings"]["cf"][$fieldName . '_zoo_username'] : '') . "</p>";

        $screen_name = "<p>Screen name: " . NBS . form_dropdown($fieldName . '_zoo_screen_name', $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . '_zoo_screen_name']) ?
                    $data["default_settings"]["cf"][$fieldName . '_zoo_screen_name'] : '') . "</p>";

        $password = "<p>Password: " . NBS . form_dropdown($fieldName . '_zoo_password', $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . '_zoo_password']) ?
                    $data["default_settings"]["cf"][$fieldName . '_zoo_password'] : '') . "</p>";

        $email = "<p>Email address: " . NBS . form_dropdown($fieldName . '_zoo_email', $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName . '_zoo_email']) ?
                    $data["default_settings"]["cf"][$fieldName . '_zoo_email'] : '') . "</p>";

        $group = "<p>Member group: " . NBS . form_dropdown($fieldName . '_zoo_member_group', $groups,
                isset($data["default_settings"]["cf"][$fieldName . '_zoo_member_group']) ?
                    $data["default_settings"]["cf"][$fieldName . '_zoo_member_group'] : '') . "</p>";

        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";
        // . BR .
        // '<a href="http://brandnewbox.co.uk/support/details/" class="datagrab_help">Zoo Visitor notes</a>';
        $config["value"] = $hidden . $email . $username . $screen_name . $password . $group;

        return $config;
    }

    public function final_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // Need to prevent Zoo Visitor from creating 2 entries by disabling this extension for this call
        unset(ee()->extensions->extensions["cp_members_member_create"][1]["Zoo_visitor_ext"]);

        // ZV will handle the hashing
        $password = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_password"]);

        if (!$updateEntryId) {
            $_POST["EE_group_id"] = $DG->settings["cf"][$fieldName . "_zoo_member_group"]; // Hard code for now
            $_POST["EE_member_id"] = "";
            $_POST["EE_username"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_username"]); // "test@brandnewbox.co.uk";
            $_POST["EE_email"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_email"]);
            $_POST["EE_screen_name"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_screen_name"]);
            $_POST["EE_password"] = $password;
            $_POST["EE_new_password_confirm"] = $password;
            //$_POST[ "title" ] = $DG->datatype->get_item( $item, $DG->settings["cf"][ $fieldName."_zoo_email" ] );
            //$_POST[ "status" ] = "Members-id5";
        } else {
            ee()->db->select("author_id");
            ee()->db->from("exp_channel_titles");
            ee()->db->where("entry_id", $updateEntryId);
            $query = ee()->db->get();

            if ($query->num_rows()) {
                $row = $query->row_array();
                $_POST["EE_group_id"] = $DG->settings["cf"][$fieldName . "_zoo_member_group"]; // Hard code for now
                $_POST["EE_member_id"] = $row["author_id"];
                $_POST["EE_username"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_username"]); // "test@brandnewbox.co.uk";
                $_POST["EE_email"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_email"]);
                $_POST["EE_screen_name"] = $DG->dataType->get_item($item, $DG->settings["cf"][$fieldName . "_zoo_screen_name"]);
                $_POST["EE_password"] = $password;
                $_POST["EE_new_password_confirm"] = $password;
            }
        }
    }
}
