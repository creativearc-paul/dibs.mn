<?php

/**
 * DataGrab Fluid Field fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_fluid_field extends AbstractFieldType
{
    /**
     * Register a setting so it can be saved
     *
     * @param string $field_name
     * @return array
     */
    public function register_setting(string $field_name): array
    {
        return [
            $field_name . "_fields",
            $field_name . "_unique",
            $field_name . '_upload_dir',
            $field_name . '_fetch_url',
        ];
    }

    public function display_configuration(Datagrab_model $DG, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config['label'] = form_label($fieldLabel);

        if ($fieldRequired) {
            $config['label'] .= ' <span class="datagrab_required">*</span>';
        }

        $config['label'] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        $config['value'] = '';
         $config['value'] .= form_hidden($fieldName, '1');

        // Get current saved setting
        if (isset($data['default_settings']['cf'][$fieldName . '_fields'])) {
            $default = $data['default_settings']['cf'][$fieldName . '_fields'];
        } else {
            $default = [];
        }

        $fieldOptions = $this->getFieldOptions($fieldName);

        foreach ($fieldOptions as $field) {
            $config["value"] .= "<p>" .
                $field["label"] . NBS . ":" . NBS;
            $config["value"] .= form_dropdown(
                $fieldName . "_fields[" . $field["id"] . "]",
                $data["data_fields"],
                isset($default[$field["id"]]) ? $default[$field["id"]] : ''
            );

            if ($field['type'] === 'file') {
                $config['value'] .= NBS . NBS . "Upload folder: " . NBS;

                // Get upload folders
                if (!isset($folders)) {
                    ee()->db->select("id, name");
                    ee()->db->from("exp_upload_prefs");
                    ee()->db->order_by("id");
                    $query = ee()->db->get();
                    $folders = [];
                    foreach ($query->result_array() as $folder) {
                        $folders[$folder["id"]] = $folder["name"];
                    }
                }

                $config['value'] .= form_dropdown(
                    $fieldName . "_upload_dir[" . $field['id'] . "]",
                    $folders,
                    isset($data["default_settings"]["cf"][$fieldName . "_upload_dir"][$field['id']]) ? $data["default_settings"]["cf"][$fieldName . "_upload_dir"][$field['id']] : ''
                );
                $config['value'] .= NBS . NBS . "Fetch from remote URL?: " . NBS;
                $config['value'] .= form_dropdown(
                    $fieldName . "_fetch_url[" . $field['id'] . "]",
                    ['No', 'Yes'],
                    isset($data["default_settings"]["cf"][$fieldName . "_fetch_url"][$field['id']]) ? $data["default_settings"]["cf"][$fieldName . "_fetch_url"][$field['id']] : ''
                );
            }

            $config["value"] .= "</p>";
        }

        return $config;
    }

    //$post = [
    //    'field_id_22' =>
    //        [
    //            'fields' =>
    //                [
    //                    'new_field_1' =>
    //                        [
    //                            'field_id_29' => '<p>123</p>',
    //                        ],
    //                    'new_field_2' =>
    //                        [
    //                            'field_id_1' => '<p>abc</p>',
    //                        ],
    //                ],
    //        ]
    //    ];
    public function final_post_data(Datagrab_model $DG, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $fields = $DG->settings["cf"][$fieldName . '_fields'];
        $fieldOptions = $this->getFieldOptions($fieldName);
        $fieldTypes = array_column($fieldOptions, 'type', 'id');
        $fluid = [];
        $order = [];

        foreach ($item as $key => $node) {
            $cleanedKey = strtok($key, '#');
            if ($key === $cleanedKey . '#') {
                continue;
            }
            if (in_array($cleanedKey, $fields)) {
                $order[] = $key;
            }
        }

        $rowNum = 1;
        $search = array_flip(array_filter($fields));

        foreach ($order as $nodeName) {
            $rowIdx = 'new_field_' . $rowNum;
            $rowNum++;
            $fluidFieldName = strtok($nodeName, '#');
            $fluidFieldId = $search[$fluidFieldName];

            $content = $DG->dataType->get_item($item, $nodeName, $DG->settings, $fieldName);

            if ($fieldTypes[$fluidFieldId] === 'file') {
                $fetchFromUrl = $DG->settings["cf"][$fieldName . "_fetch_url"][$fluidFieldId] ?? 'No';
                $content = $DG->getFile(
                    $content,
                    $DG->settings["cf"][$fieldName . "_upload_dir"][$fluidFieldId],
                    get_bool_from_string($fetchFromUrl)
                );
            }

            $fluid[$rowIdx]['field_id_' . $fluidFieldId] = $content;
        }

        $data['field_id_' . $fieldId]['fields'] = $fluid;
    }

    /**
     * @param string $fieldName
     * @return array
     */
    private function getFieldOptions(string $fieldName): array
    {
        $field = ee('Model')->get('ChannelField')->filter('field_name', $fieldName)->first();
        $fieldOptions = $field->field_settings['field_channel_fields'];

        return ee('Model')->get('ChannelField')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->filter('field_id', 'IN', $fieldOptions)
            ->order('field_label')
            ->all()
            ->filter(function ($field) {
                return $field->getField()->acceptsContentType('fluid_field');
            })
            ->map(function ($field) {
                return [
                    'label' => $field->field_label,
                    'id' => $field->getId(),
                    'name' => $field->field_name,
                    'type' => $field->field_type,
                ];
            });
    }
}
