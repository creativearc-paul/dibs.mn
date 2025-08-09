<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\Service\Importer;

/**
 * DataGrab cartthrob_order_items fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_cartthrob_order_items extends AbstractFieldType
{
    private $defaultValues = [
        'title' => null,
        'quantity' => 0,
        'price' => 0,
        'price_plus_tax' => 0,
        'weight' => 0,
        'shipping' => 0,
        'no_tax' => 0,
        'no_shipping' => 0,
        'extra' => null,
    ];

    public function register_setting(string $fieldName)
    {
        return [
            $fieldName . '_cartthrob_title',
            $fieldName . '_cartthrob_quantity',
            $fieldName . '_cartthrob_price',
            $fieldName . '_cartthrob_price_plus_tax',
            $fieldName . '_cartthrob_weight',
            $fieldName . '_cartthrob_shipping',
            $fieldName . '_cartthrob_no_tax',
            $fieldName . '_cartthrob_no_shipping',
            $fieldName . '_cartthrob_extra',
        ];
    }

    public function display_configuration(Importer $importer, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $extraExample = '

<quantity>3</quantity>
<price>$100.00</price>
<extra><![CDATA[
  {
      "discount": 1,
      "price_plus_tax": "$20",
      "product_color": "Blue",
      "product_code": "WIDGET123"
  }
]]></extra>';

        $config = [];
        $config["label"] = "<p>" . form_label($fieldLabel);
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType .'<br /><br /><p>The "Extra" column value must be a valid JSON string. For example:</p><pre>' . htmlentities($extraExample) .'</pre></div>';


        $config["value"] = "Entry ID: " . NBS . form_dropdown(
                $fieldName, $data["data_fields"],
                $data["default_settings"]["cf"][$fieldName] ?? ''
            ) .
            "</p><p>" . "Title: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_title",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_title"] ?? '')
            ) .
            "</p><p>" . "Quantity: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_quantity",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_quantity"] ?? '')
            ) .
            "</p><p>" . "Price: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_price",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_price"] ?? '')
            ) .
            "</p><p>" . "Price Plus Tax: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_price_plus_tax",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_price_plus_tax"] ?? '')
            ) .
            "</p><p>" . "Weight: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_weight",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_weight"] ?? '')
            ) .
            "</p><p>" . "Shipping: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_shipping",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_shipping"] ?? '')
            ) .
            "</p><p>" . "No Tax: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_no_tax",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_no_tax"] ?? '')
            ) ."</p><p>" . "No Shipping: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_no_shipping",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_no_shipping"] ?? '')
            ) .
            "</p><p>" . "Extra: " . NBS .
            form_dropdown(
                $fieldName . "_cartthrob_extra",
                $data["data_fields"],
                ($data["default_settings"]["cf"][$fieldName . "_cartthrob_extra"] ?? '')
            ) .
            "</p>";
        return $config;
    }

    public function final_post_data(Importer $importer, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        // Initialise data
        $data["field_id_" . $fieldId] = array();
        $first_row = 0;

        // Can the current datatype handle sub-loops (eg, XML)?
        if ($importer->dataType->datatype_info["allow_subloop"]) {
            // Check this field can be a sub-loop
            $count = $first_row;
            if ($importer->dataType->initialise_sub_item()) {
                // Loop over sub items
                while ($subitem = $importer->dataType->get_sub_item(
                    $item, $importer->settings["cf"][$fieldName], $importer->settings, $fieldName)
                ) {
                    $row = array(
                        "entry_id" => $subitem
                    );
                    $data["field_id_" . $fieldId][$count++] = $row;
                }
            }

            foreach ($this->register_setting($fieldName) as $settingName) {

                $shortname = substr($settingName, strlen($fieldName . "_cartthrob_"));

                $count = $first_row;
                $data["field_id_" . $fieldId][$count][$shortname] = "";

                if ($importer->dataType->initialise_sub_item()) {

                    $subitem = $importer->dataType->get_sub_item(
                        $item, $importer->settings["cf"][$settingName], $importer->settings, $fieldName
                    );

                    $defaultValue = $this->defaultValues[$shortname] ?? null;

                    if (!$subitem && $defaultValue !== null) {
                        $data["field_id_" . $fieldId][$count++][$shortname] = $defaultValue;
                    } else {
                        while ($subitem !== false) {
                            if ($shortname === 'extra') {
                                $extra = json_decode($subitem, true);

                                foreach ($extra as $extraKey => $extraValue) {
                                    $data["field_id_" . $fieldId][$count][$extraKey] = $extraValue;
                                }
                            } else {
                                $data["field_id_" . $fieldId][$count++][$shortname] = $subitem;
                            }

                            $subitem = $importer->dataType->get_sub_item(
                                $item, $importer->settings["cf"][$fieldName], $importer->settings, $fieldName
                            );
                        }
                    }
                }
            }
        }
    }

    public function rebuild_post_data(Importer $importer, int $fieldId = 0, array &$data = [], array $entryData = [])
    {
        // @todo this needs to return not by reference
        $entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $existingData["entry_id"])->first();

        if ($entry) {
            $fieldValues = $entry->getValues();
            $orderItemsFieldValue = $fieldValues['field_id_' . $fieldId] ?? '';

            if ($orderItemsFieldValue) {
                $data['field_id_' . $fieldId] = unserialize(base64_decode($orderItemsFieldValue));
            }
        }
    }
}
