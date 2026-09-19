<?php
/**
 * Datagrab Type Class
 *
 * Provides the basic methods to create an import type
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 **/

abstract class AbstractDataType
{
    // @todo these should be private/protected with getters and setters
    public $settings = [];
    public $config_defaults = [];
    public $handle;
    public $titles;
    public $errors = [];
    public $isConfigMode = false;

    function display_name()
    {
        return $this->datatype_info["name"];
    }

    /**
     * @param array $values
     * @return array[]
     */
    public function settings_form(array $values = []): array
    {
        return [['This data type has no additional settings.']];
    }

    function initialise($settings)
    {
        if ($settings && isset($settings['datatype'])) {
            $this->settings = $settings['datatype'];
        }
    }

    public function display_configuration(Datagrab_model $DG, string $field_name, string $field_label, string $field_type, string $field_required = '', array $data = []): array
    {
        return [];
    }

    public function getFilename(): string
    {
        return reduce_double_slashes(str_replace(
            ['{base_url}', '{base_path}'],
            [ee()->config->item('base_url'), ee()->config->item('base_path')],
            $this->settings['filename']
        ));
    }

    public function fetch()
    {
    }

    public function next()
    {
        return false;
    }

    public function fetch_columns(): array
    {
        return [];
    }

    public function total_rows()
    {
        $count = 0;
        while ($this->next()) {
            $count++;
        }
        return $count;
    }

    public function total_rows_real()
    {
        return count($this->items) ?? 0;
    }

    public function clean_up($entries, $settings)
    {
    }

    public function get_item($items, $id): string
    {
        if (isset($items[$id])) {
            return trim(stripcslashes($items[$id]));
        }

        return '';
    }

    public function get_value($values, $field)
    {
        return isset($values["datatype"][$field]) ? $values["datatype"][$field] : '';
    }

    public function initialise_sub_item($item, $id, $config, $field)
    {
        return false;
    }

    public function get_sub_item($item, $id, $config, $field, array $column = [])
    {
        return $item;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return array_unique($this->errors);
    }

    /**
     * @param string $error
     * @return void
     */
    public function addError(string $error = '')
    {
        $this->errors[] = $error;
    }
}
