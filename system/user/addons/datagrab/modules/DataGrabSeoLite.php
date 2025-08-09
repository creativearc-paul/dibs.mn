<?php

use BoldMinded\DataGrab\Service\Importer;

class DataGrabSeoLite extends AbstractModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'seo_lite';
    }

    public function getDisplayName(): string
    {
        return 'SEO Lite';
    }

    public function displayConfiguration(Importer $importer, array $data = []): array
    {
        $options = [
            '' => 'Create or Update', // Default, also backwards compatible
            'create' => 'Create Only',
            'update' => 'Update Only',
        ];

        return [
            [
                form_label('Execution Time') . '<div class="datagrab_subtext">When should DataGrab perform SEO Lite updates?</div>',
                form_dropdown($this->getName() .'[execute_on_action]', $options, $this->getSettingValue('execute_on_action'))
            ],
            [
                form_label('SEO Lite Title'),
                form_dropdown($this->getName() .'[seo_lite_title]', $data['data_fields'], $this->getSettingValue('seo_lite_title'))
            ],
            [
                form_label('SEO Lite Keywords'),
                form_dropdown($this->getName() .'[seo_lite_keywords]', $data['data_fields'], $this->getSettingValue('seo_lite_keywords'))
            ],
            [
                form_label('SEO Lite Description'),
                form_dropdown($this->getName() .'[seo_lite_description]', $data['data_fields'], $this->getSettingValue('seo_lite_description'))
            ],
        ];
    }

    public function saveConfiguration(Importer $importer): array
    {
        $data = ee()->input->post($this->getName());

        return [
            'execute_on_action' => $data['execute_on_action'] ?? '',
            'seo_lite_title' => $data['seo_lite_title'] ?? '',
            'seo_lite_keywords' => $data['seo_lite_keywords'] ?? '',
            'seo_lite_description' => $data['seo_lite_description'] ?? '',
        ];
    }

    public function handle(Importer $importer, array &$data = [], array $item = [], array $custom_fields = [], string $action = '')
    {
        $onAction = $this->getSettingValue('execute_on_action');

        // We have a specific execution time, and now is not the time.
        if ($onAction !== $action && $onAction !== '') {
            return;
        }

        if (!$this->getSettingValue('seo_lite_title')) {
            return;
        }

        // Not 100% sure I understand this, but core EE is checking for this field and it has been in DG's core for a long time.
        $data["cp_call"] = true;

        $data["seo_lite__seo_lite_title"] = $importer->dataType->get_item($item, $this->getSettingValue('seo_lite_title'));
        $data["seo_lite__seo_lite_keywords"] = $importer->dataType->get_item($item, $this->getSettingValue('seo_lite_keywords'));
        $data["seo_lite__seo_lite_description"] = $importer->dataType->get_item($item, $this->getSettingValue('seo_lite_description'));
    }
}
