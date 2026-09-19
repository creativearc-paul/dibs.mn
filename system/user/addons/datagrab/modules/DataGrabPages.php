<?php

/**
 * Why does anyone still use this module?!
 */
class DataGrabPages extends AbstractModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'pages';
    }

    public function getDisplayName(): string
    {
        return 'Pages';
    }

    public function displayConfiguration(Datagrab_model $DG, array $data = []): array
    {
        $options = [
            '' => 'Create or Update', // Default, also backwards compatible
            'create' => 'Create Only',
            'update' => 'Update Only',
        ];

        return [
            [
                form_label('Execution Time') . '<div class="datagrab_subtext">When should DataGrab perform Pages updates?</div>',
                form_dropdown($this->getName() .'[execute_on_action]', $options, $this->getSettingValue('execute_on_action'))
            ],
            [
                form_label('Add as page?') . '<div class="datagrab_subtext">Check this box if you want to add the imported entries as Pages</div>',
                form_checkbox($this->getName() .'[pages]', 'y', $this->getSettingValue('pages') === 'y')
            ],
            [
                form_label('Page URL') . '<div class="datagrab_subtext">Leave blank to generate the page url title automatically</div>',
                form_dropdown($this->getName() .'[pages_url]', $data['data_fields'], $this->getSettingValue('pages_url'))
            ],
            [
                form_label('Page Template') . '<div class="datagrab_subtext">Leave blank to use the channel\'s default Page template</div>',
                form_dropdown($this->getName() .'[pages_template]', $data['data_fields'], $this->getSettingValue('pages_template'))
            ],
        ];
    }

    public function saveConfiguration(Datagrab_model $DG): array
    {
        $data = ee()->input->post($this->getName());

        return [
            'execute_on_action' => $data['execute_on_action'] ?? '',
            'pages' => $data['pages'] ?? '',
            'pages_url' => $data['pages_url'] ?? '',
            'pages_template' => $data['pages_template'] ?? '',
        ];
    }

    public function handle(Datagrab_model $DG, array &$data = [], array $item = [], array $custom_fields = [], string $action = '')
    {
        $onAction = $this->getSettingValue('execute_on_action');

        // We have a specific execution time, and now is not the time.
        if ($onAction !== $action && $onAction !== '') {
            return;
        }

        if (!$this->getSettingValue('pages')) {
            return;
        }

        // Not 100% sure I understand this, but core EE is checking for this field and it has been in DG's core for a long time.
        $data["cp_call"] = true;

        if ($this->getSettingValue('pages_url') === '') {
            $entryUrlTitle = ee('Format')->make('Text', $data["title"])->urlSlug()->compile();
            $data["pages__pages_uri"] = $entryUrlTitle;
            $_POST["pages__pages_uri"] = $entryUrlTitle;
        } else {
            $data["pages__pages_uri"] = $DG->dataType->get_item($item, $this->getSettingValue('pages_url'));
            $_POST["pages__pages_uri"] = $DG->dataType->get_item($item, $this->getSettingValue('pages_url'));
        }

        $DG->db->select("configuration_value");
        $DG->db->where("configuration_name", "template_channel_" . $DG->channelDefaults["channel_id"]);
        $query = $DG->db->get("exp_pages_configuration");

        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $default_template = $row["configuration_value"];
        } else {
            $DG->db->select("exp_templates.template_id");
            $DG->db->from("exp_templates");
            $DG->db->join("exp_template_groups", "exp_template_groups.group_id = exp_templates.group_id");
            $DG->db->where("is_site_default", "y");
            $DG->db->where("template_name", "index");
            $query = $DG->db->get();
            $row = $query->row_array();
            $default_template = $row["template_id"] ?? 1;
        }

        if ($this->getSettingValue('pages_template')) {
            $template = $DG->dataType->get_item($item, $this->getSettingValue('pages_template'));
            $template_segments = explode("/", $template);

            if (count($template_segments) == 2) {
                $DG->db->select("exp_templates.template_id");
                $DG->db->from("exp_templates");
                $DG->db->join("exp_template_groups", "exp_template_groups.group_id = exp_templates.group_id");
                $DG->db->where("group_name", $template_segments[0]);
                $DG->db->where("template_name", $template_segments[1]);
                $query = $DG->db->get();
                if ($query->num_rows() > 0) {
                    $row = $query->row_array();
                    $default_template = $row["template_id"];
                }
            }
        }

        $data["pages__pages_template_id"] = $default_template;
        $_POST["pages__pages_template_id"] = $default_template;
    }
}
