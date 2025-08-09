<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\FieldTypes\ImportField;
use BoldMinded\DataGrab\Service\Importer;

/**
 * DataGrab Date fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_date extends AbstractFieldType
{
    public function register_setting(string $fieldName): array
    {
        return [
            $fieldName => [
                'value',
                'localized',
            ],
        ];
    }

    public function display_configuration(Importer $importer, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config['label'] = $this->displayLabel($fieldLabel, $fieldName, $fieldRequired, 'date');
        $fieldSettings = $data['field_settings'][$fieldName] ?? [];
        $savedFieldValues = $this->getSavedFieldValues($data, $fieldName);

        $fieldSets = ee('View')
            ->make('ee:_shared/form/section')
            ->render([
                'name' => 'fieldset_group',
                'settings' => $this->getFormFields(
                    $fieldName,
                    $fieldSettings,
                    $data,
                    $savedFieldValues,
                )
            ]);

        $config['value'] = $fieldSets;

        return $config;
    }

    public function getFormFields(
        string $fieldName,
        array $fieldSettings,
        array $data = [],
        array $savedFieldValues = [],
        string $contentType = 'channel',
    ): array {
        $fieldOptions[] = [
            'title' => 'Import Value',
            'desc' => '',
            'fields' => [
                $fieldName . '[value]' => [
                    'type' => 'dropdown',
                    'choices' => $data['data_fields'],
                    'value' => $savedFieldValues['value'] ?? '',
                ]
            ]
        ];

        // Grid Date columns don't offer or display the "Localized / Fixed" radio options.
        if ($contentType !== 'grid') {
            $fieldOptions[] = [
                'title' => 'Localized',
                'desc' => '',
                'fields' => [
                    $fieldName . '[localized]' => [
                        'type' => 'dropdown',
                        'choices' => ['No', 'Yes'],
                        'value' => $savedFieldValues['localized'] ?? 0,
                    ]
                ]
            ];
        }

        return $fieldOptions;
    }

    public function preparePostData(ImportField $importField): string
    {
        $data = '';
        $offset = $importField->importer->settings['config']['offset'] ?? 0;

        if ($importField->propertyValue !== '') {
            $data = $importField->importer->parseDate($importField->propertyValue);
            $data -= $offset;
        }

        return $data;
    }
}
