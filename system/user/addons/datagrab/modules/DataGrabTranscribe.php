<?php

class DataGrabTranscribe extends AbstractModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'transcribe';
    }

    public function getDisplayName(): string
    {
        return 'Transcribe';
    }

    public function displayConfiguration(Datagrab_model $DG, array $data = []): array
    {
        ee()->db->select('id, name');
        $query = ee()->db->get('exp_transcribe_languages');
        $languageFields = [];
        $languageFields[0] = 'None';

        foreach ($query->result_array() as $row) {
            $languageFields[$row['id']] = ucfirst($row['name']);
        }

        $options = [
            '' => 'Create or Update', // Default, also backwards compatible
            'create' => 'Create Only',
            'update' => 'Update Only',
        ];

        return [
            [
                form_label('Execution Time') . '<div class="datagrab_subtext">When should DataGrab perform Transcribe updates?</div>',
                form_dropdown($this->getName() .'[execute_on_action]', $options, $this->getSettingValue('execute_on_action'))
            ],
            [
                form_label("Language") . '<div class="datagrab_subtext">Choose language to import these entries as</div>',
                form_dropdown($this->getName() .'[transcribe_language]', $languageFields, $this->getSettingValue('transcribe_language'))
            ],
            [
                form_label("Related entry") . '<div class="datagrab_subtext">Select field to identify related entries (ie, the same entry in a different language)</div>',
                form_dropdown($this->getName() .'[transcribe_related_entry]', $data['unique_fields'], $this->getSettingValue('transcribe_related_entry'))
            ],
        ];
    }

    public function saveConfiguration(Datagrab_model $DG): array
    {
        $data = ee()->input->post($this->getName());

        return [
            'execute_on_action' => $data['execute_on_action'] ?? '',
            'transcribe_language' => $data['transcribe_language'] ?? '',
            'transcribe_related_entry' => $data['transcribe_related_entry'] ?? '',
        ];
    }

    public function handle(Datagrab_model $DG, array &$data = [], array $item = [], array $custom_fields = [], string $action = '')
    {
        $onAction = $this->getSettingValue('execute_on_action');

        // We have a specific execution time, and now is not the time.
        if ($onAction !== $action && $onAction !== '') {
            return;
        }

        if (
            $this->getSettingValue('transcribe_language') &&
            $this->getSettingValue('transcribe_language') !== 0
        ) {
            // $data["transcribe__transcribe_language"] = $DG->settings["config"]["ajw_transcribe_language"];
            $_POST["transcribe__transcribe_language"] = $this->getSettingValue('transcribe_language');
            $transcribe__transcribe_language = $this->getSettingValue('transcribe_language');
        } else {
            // Find default language
            $DG->db->select("language_id");
            $DG->db->where("site_id", $DG->config->item('site_id'));
            $query = $DG->db->get("exp_transcribe_settings");
            $transcribe__transcribe_language = 0;
            if ($query->num_rows()) {
                $row = $query->row_array();
                // $data["transcribe__transcribe_language"] = $row["language_id"];
                $_POST["transcribe__transcribe_language"] = $row["language_id"];
                $transcribe__transcribe_language = $row["language_id"];
            }
        }

        if (
            $this->getSettingValue('transcribe_related_entry') &&
            $this->getSettingValue('transcribe_related_entry') !== ''
        ) {
            $field_id = $custom_fields[$this->getSettingValue('transcribe_related_entry')]["id"];
            $rel_field_id = $DG->settings["cf"][$this->getSettingValue('transcribe_related_entry')];

            $DG->db->select('t.relationship_id');
            $DG->db->from('exp_transcribe_entries_languages t');
            $DG->db->join('exp_channel_data d', 'd.entry_id = t.entry_id');
            $DG->db->where("field_id_" . $field_id, $DG->dataType->get_item($item, $rel_field_id));
            $DG->db->where("language_id !=", $transcribe__transcribe_language);
            $query = $DG->db->get();
            if ($query->num_rows()) {
                $row = $query->row_array();
                $_POST["transcribe__transcribe_related_entries"] = $transcribe__transcribe_language . "__" . $row["relationship_id"];
            }
        }

        $data["cp_call"] = true;
    }
}
