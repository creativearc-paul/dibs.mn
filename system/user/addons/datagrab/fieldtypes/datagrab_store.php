<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\Service\Importer;

/**
 * DataGrab exp-resso Store fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_store extends AbstractFieldType
{
    public function register_setting(string $fieldName): array
    {
        return [
            $fieldName => [
                'value',
                'sku',
                'width',
                'height',
                'length',
                'weight',
                'handling_surcharge',
                'free_shipping',
                'stock',
                'modifiers',
            ],
        ];
    }

    /*
     {"price":"100.00","length":"20","width":"10","height":"5","weight":"2","handling":"3.00","free_shipping":"1","modifiers":{"1":{"product_mod_id":"1","options":{"1":{"product_opt_id":"1","opt_order":"1","opt_name":"cyan","opt_price_mod":"-10.00"},"2":{"product_opt_id":"2","opt_order":"2","opt_name":"magenta","opt_price_mod":"-20.00"}},"mod_order":"0","mod_type":"var","mod_name":"Small","mod_instructions":"Turn Left"},"2":{"product_mod_id":"2","options":{"3":{"product_opt_id":"3","opt_order":"4","opt_name":"yellow","opt_price_mod":"+5.00"},"4":{"product_opt_id":"4","opt_order":"5","opt_name":"black","opt_price_mod":"+10.00"}},"mod_order":"3","mod_type":"var","mod_name":"Medium","mod_instructions":"Turn Right"}},"stock":[{"stock_options":[{"product_mod_id":"1","product_opt_id":"1"},{"product_mod_id":"2","product_opt_id":"3"}],"id":"1","sku":"cyan-yellow","track_stock":"0","stock_level":"30","min_order_qty":"1"},{"stock_options":[{"product_mod_id":"1","product_opt_id":"1"},{"product_mod_id":"2","product_opt_id":"4"}],"id":"2","sku":"cyan-black","track_stock":"0","stock_level":"20","min_order_qty":"2"},{"stock_options":[{"product_mod_id":"1","product_opt_id":"2"},{"product_mod_id":"2","product_opt_id":"3"}],"id":"3","sku":"magenta-yellow","track_stock":"0","stock_level":"10","min_order_qty":"3"},{"stock_options":[{"product_mod_id":"1","product_opt_id":"2"},{"product_mod_id":"2","product_opt_id":"4"}],"id":"4","sku":"magenta-black","track_stock":"0","stock_level":"5","min_order_qty":"4"}]}
     */

    public function display_configuration(Importer $importer, string $fieldName, string $fieldLabel, string $fieldType, bool $fieldRequired = false, array $data = []): array
    {
        $config = [];
        $config["label"] = form_label($fieldLabel);
        if ($fieldRequired) {
            $config["label"] .= ' <span class="datagrab_required">*</span>';
        }
        $config["label"] .= '<div class="datagrab_subtext">' . $fieldType . "</div>";

        // Version 2+
        $config["value"] = "<p>Price: " . NBS . form_dropdown(
                $fieldName, $data["data_fields"],
                isset($data["default_settings"]["cf"][$fieldName]) ?
                    $data["default_settings"]["cf"][$fieldName] : ''
            ) .
            "</p><p>" . "SKU: " . NBS .
            form_dropdown(
                $fieldName . "_store_sku",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_sku"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_sku"] : '')
            ) .
            "</p><p>" . "Weight: " . NBS .
            form_dropdown(
                $fieldName . "_store_weight",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_weight"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_weight"] : '')
            ) .
            "</p><p>" . "Width: " . NBS .
            form_dropdown(
                $fieldName . "_store_width",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_width"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_width"] : '')
            ) .
            "</p><p>" . "Length: " . NBS .
            form_dropdown(
                $fieldName . "_store_length",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_length"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_length"] : '')
            ) .
            "</p><p>" . "Height: " . NBS .
            form_dropdown(
                $fieldName . "_store_height",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_height"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_height"] : '')
            ) .
            "</p><p>" . "Stock level: " . NBS .
            form_dropdown(
                $fieldName . "_store_stock_level",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_stock_level"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_stock_level"] : '')
            ) .
            "</p><p>" . "Limit stock: " . NBS .
            form_dropdown(
                $fieldName . "_store_limit_stock",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_limit_stock"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_limit_stock"] : '')
            ) .
            "</p><p>" . "Handling surcharge: " . NBS .
            form_dropdown(
                $fieldName . "_store_handling_surcharge",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_handling_surcharge"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_handling_surcharge"] : '')
            ) .
            "</p><p>" . "Free shipping: " . NBS .
            form_dropdown(
                $fieldName . "_store_free_shipping",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_free_shipping"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_free_shipping"] : '')
            ) .
            "</p><p>" . "Min order qty: " . NBS .
            form_dropdown(
                $fieldName . "_store_min_order_qty",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_min_order_qty"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_min_order_qty"] : '')
            ) .
            "</p><p>" . "Modifiers: " . NBS .
            form_dropdown(
                $fieldName . "_store_modifiers",
                $data["data_fields"],
                (isset($data["default_settings"]["cf"][$fieldName . "_store_modifiers"]) ?
                    $data["default_settings"]["cf"][$fieldName . "_store_modifiers"] : '')
            );

        return $config;
    }

    public function final_post_data(Importer $importer, array $item = [], int $fieldId = 0, string $fieldName = '', array &$data = [], int $updateEntryId = 0)
    {
        $data["field_id_" . $fieldId] = "store";

        if ($updateEntryId) {
            $existing_data = array(
                "entry_id" => $updateEntryId
            );
            $this->rebuild_post_data($importer, $fieldId, $data, $existing_data);
        } else {
            // Version 2

            /*
            [store_product_field] => Array
                (
                    [price] => 12.00
                    [length] => 10
                    [width] => 10
                    [height] => 10
                    [weight] => 10
                    [handling] => 2
                    [free_shipping] => 1
                    [stock] => Array
                        (
                            [0] => Array
                                (
                                    [id] => 25
                                    [sku] => ABC123
                                    [track_stock] => 1
                                    [stock_level] => 8
                                    [min_order_qty] => 3
                                )
                        )
                )
            */

            $_POST["store_product_field"] = array(
                "price" => "",
                "stock" => array(
                    array(
                        "sku" => "",
                        "min_order_qty" => ""
                    )
                ),
                "weight" => "",
                "length" => "",
                "width" => "",
                "height" => "",
                "handling" => "",
                "free_shipping" => ""
            );
        }

        // Version 2
        if (!in_array($updateEntryId, $importer->entries)) {
            if ($importer->settings["cf"][$fieldName] != "") {
                $_POST["store_product_field"]["price"] =
                    $importer->dataType->get_item($item, $importer->settings["cf"][$fieldName]);
            }
            $price = $_POST["store_product_field"]["price"];
        } else {
            $price = $_POST["store_product_field"]["price"];
        }
        $price = preg_replace("/([^0-9\\.])/i", "", $price);
        if ($importer->settings["cf"][$fieldName . "_store_width"] != "") {
            $_POST["store_product_field"]["width"] =
                $this->_to_number($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_width"]));
        }
        if ($importer->settings["cf"][$fieldName . "_store_weight"] != "") {
            $_POST["store_product_field"]["weight"] =
                $this->_to_number($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_weight"]));
        }
        if ($importer->settings["cf"][$fieldName . "_store_height"] != "") {
            $_POST["store_product_field"]["height"] =
                $this->_to_number($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_height"]));
        }
        if ($importer->settings["cf"][$fieldName . "_store_length"] != "") {
            $_POST["store_product_field"]["length"] =
                $this->_to_number($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_length"]));
        }
        if ($importer->settings["cf"][$fieldName . "_store_handling_surcharge"] != "") {
            $_POST["store_product_field"]["handling"] =
                $this->_to_number($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_handling_surcharge"]));
        }
        if ($importer->settings["cf"][$fieldName . "_store_free_shipping"] != "") {
            $_POST["store_product_field"]["free_shipping"] =
                $importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_free_shipping"]) == "y" ? 1 : 0;
        }

        if (!in_array($updateEntryId, $importer->entries)) {
            ee()->db->where("entry_id", $updateEntryId);
            ee()->db->delete("exp_store_stock");
        }

        ee()->db->select("*");
        ee()->db->where("entry_id", $updateEntryId);
        $query = ee()->db->get("exp_store_stock");
        $count = 0;
        if ($query->num_rows()) {
            foreach ($query->result_array() as $row) {
                $_POST["store_product_field"]["stock"][$count]["id"] = $row["id"];
                $_POST["store_product_field"]["stock"][$count]["sku"] = $row["sku"];
                $count++;
            }
        }

        if ($importer->settings["cf"][$fieldName . "_store_sku"] != "") {
            $_POST["store_product_field"]["stock"][$count]["sku"] =
                $importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_sku"]);
        }

        if ($importer->settings["cf"][$fieldName . "_store_stock_level"] != "") {
            // $_POST[ "store_product_field" ][ "stock" ][$count][ "track_stock" ] = 1;
            if ($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_stock_level"]) == "") {
                $_POST["store_product_field"]["stock"][$count]["track_stock"] = 0;
            } else {
                $_POST["store_product_field"]["stock"][$count]["track_stock"] = 1;
            }
            $_POST["store_product_field"]["stock"][$count]["stock_level"] =
                $importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_stock_level"]);
        }

        if ($importer->settings["cf"][$fieldName . "_store_limit_stock"] != "") {
            $_POST["store_product_field"]["stock"][$count]["track_stock"] =
                ($importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_limit_stock"]) == "y" ? 1 : 0);
        }

        if ($importer->settings["cf"][$fieldName . "_store_min_order_qty"] != "") {
            $_POST["store_product_field"]["stock"][$count]["min_order_qty"] =
                $importer->dataType->get_item($item, $importer->settings["cf"][$fieldName . "_store_min_order_qty"]);
        }

        // Set up array to store modifiers
        $mod_order = 0;

        if ($importer->settings["cf"][$fieldName . "_store_modifiers"] != "") {
            $modifier_field = $importer->settings["cf"][$fieldName . "_store_modifiers"];

            // Initialise loop over modifiers
            if ($importer->dataType->initialise_sub_item()) {
                // Loop over modifiers
                $subitem = $importer->dataType->get_sub_item($item, $modifier_field . "/mod", $importer->settings, $fieldName);
                while ($subitem !== false) {
                    // Get 'path' of this modifier (so we can find sub elements)
                    $subitem_path = $importer->dataType->get_sub_item_path($modifier_field . "/mod");

                    // Get type of modifier (text_input, variation_single_sku, variation_multi_sku)
                    $m_option_type = $importer->dataType->get_item($item, $subitem_path . '/type');

                    // Get modifier's name/title
                    $m_option_name = $importer->dataType->get_item($item, $subitem_path . '/name');
                    $m_option_instructions = $importer->dataType->get_item($item, $subitem_path . '/instructions');

                    $modifier = array(
                        "mod_order" => $mod_order++,
                        "mod_type" => "text", // default for now
                        "mod_name" => $m_option_name,
                        "mod_instructions" => $m_option_instructions
                    );

                    // Do we need options?
                    if ($m_option_type == "variation_single_sku" || $m_option_type == "variation_multi_sku") {
                        $options_array = array();
                        // Set up and loop over options
                        $importer->dataType->initialise_sub_item();
                        $m_option = $importer->dataType->get_sub_item($item, $subitem_path . "/options/option", $importer->settings, $fieldName);
                        while ($m_option !== false) {

                            // Get option path
                            $m_option_path = $importer->dataType->get_sub_item_path($subitem_path . "/options/option");

                            $options_array[] = array(
                                "opt_order" => $mod_order++,
                                "opt_name" => $importer->dataType->get_item($item, $m_option_path . '/name'),
                                "opt_price_mod" => $importer->dataType->get_item($item, $m_option_path . '/cost')
                            );

                            $m_option = $importer->dataType->get_sub_item($item, $subitem_path . "/options/option", $importer->settings, $fieldName);
                        }
                        $modifier["options"] = $options_array;

                    }

                    switch ($m_option_type) {
                        case "text_input" :
                        {
                            $modifier["mod_type"] = "text";
                            $modifiers[] = $modifier;
                            break;
                        }
                        case "variation_single_sku" :
                        {
                            $modifier["mod_type"] = "var_single_sku";
                            $modifiers[] = $modifier;
                            break;
                        }
                        case "variation_multi_sku" :
                        {
                            $modifier["mod_type"] = "var";
                            $modifiers[] = $modifier;
                            break;
                        }
                    }

                    // Get next modifier
                    $subitem = $importer->dataType->get_sub_item($item, $modifier_field . "/mod", $importer->settings, $fieldName);
                } // End loop over modifiers

                if (isset($modifiers)) {
                    $_POST["store_product_field"]["modifiers"] = $modifiers;
                    $stock = array();
                    $stock = $this->_get_modifier_combinations($modifiers, $stock);
                    if (!empty($stock)) {
                        $_POST["store_product_field"]["stock"] = $stock;
                    }
                    // print "<pre>"; print_r( $stock ); print "</pre>";
                }
            }

            // print "<pre>"; print_r( $modifiers ); print_r( $stock_array); print_r( $item ); exit;
            /*
            [store_product_field] => Array (
                [price] => 12.00
                [length] =>
                [width] =>
                [height] =>
                [weight] =>
                [handling] =>
                [free_shipping] => 0
                [modifiers] => Array (
                    [0] => Array (
                        [mod_order] => 0
                        [mod_type] => var_single_sku
                        [mod_name] => M1
                        [mod_instructions] => M1
                        [options] => Array (
                            [0] => Array (
                                [opt_order] => 1
                                [opt_name] => One
                                [opt_price_mod] => 0
                            )
                            [1] => Array (
                                [opt_order] => 2
                                [opt_name] => Two
                                [opt_price_mod] => 2
                            )
                        )
                    )
                    [1] => Array (
                        [mod_order] => 3
                        [mod_type] => var
                        [mod_name] => M2
                        [mod_instructions] => M2
                        [options] => Array (
                            [0] => Array (
                                [opt_order] => 4
                                [opt_name] => One
                                [opt_price_mod] => 0
                            )
                            [1] => Array (
                                [opt_order] => 5
                                [opt_name] => Two
                                [opt_price_mod] => 2
                            )
                        )
                    )
                    [2] => Array (
                        [mod_order] => 6
                        [mod_type] => text
                        [mod_name] => M3
                        [mod_instructions] => M3
                        [options] => Array (
                            [0] => Array (
                                [opt_order] => 7
                                [opt_name] =>
                                [opt_price_mod] =>
                            )
                        )
                    )
                )
                [stock] => Array (
                        [0] => Array (
                            [stock_options] => Array (
                                [0] => Array (
                                    [product_mod_id] => 1
                                    [product_opt_id] => 0
                                )
                            )
                            [id] => 34
                            [sku] => A
                            [track_stock] => 0
                            [stock_level] =>
                            [min_order_qty] =>
                        )
                        [1] => Array (
                            [stock_options] => Array (
                                [0] => Array (
                                    [product_mod_id] => 1
                                    [product_opt_id] => 1
                                )

                            )
                            [id] =>
                            [sku] =>
                            [track_stock] => 0
                            [stock_level] =>
                            [min_order_qty] =>
                        )
                    )
                )
        */

        }

        $_POST["url_title"] = $data["url_title"];
        $_POST["channel_id"] = $importer->channelDefaults["channel_id"];
    }

    private function _get_modifier_combinations($modifiers, $stock)
    {
        $mod_count = 0;
        foreach ($modifiers as $mod_id => $modifier) {
            if ($modifier["mod_type"] == "var" && isset($modifier["options"])) {
                $mod_count++;
                $oldstock = $stock;
                $stock = array();
                foreach ($modifier["options"] as $opt_id => $option) {
                    if ($mod_count == 1) {
                        $stock[] = array(
                            "id" => "",
                            "sku" => "",
                            "track_stock" => 0,
                            "stock_level" => "",
                            "min_order_qty" => "",
                            "stock_options" => array(
                                array(
                                    "product_mod_id" => $mod_id,
                                    "product_opt_id" => $opt_id
                                )
                            )
                        );
                    } else {
                        foreach ($oldstock as $s) {
                            $s["stock_options"][] = array(
                                "product_mod_id" => $mod_id,
                                "product_opt_id" => $opt_id
                            );
                            $stock[] = $s;
                        }
                    }
                }
            }
        }

        return $stock;
    }

    private function _to_number($number)
    {
        return preg_replace("/([^0-9\\.])/i", "", $number);
    }

    public function rebuild_post_data(Importer $importer, int $fieldId = 0, array &$data = [], array $entryData = [])
    {
        // Version 2
        $data["field_id_" . $fieldId] = "store";
        $_POST["store_product_field"] = array(
            "price" => "",
            "stock" => array(
                array(
                    "sku" => "",
                    "min_order_qty" => ""
                )
            ),
            "weight" => "",
            "length" => "",
            "width" => "",
            "height" => "",
            "handling" => "",
            "free_shipping" => ""
        );

        ee()->db->from("exp_store_products");
        ee()->db->join("exp_store_stock", "exp_store_products.entry_id = exp_store_stock.entry_id");
        ee()->db->where("exp_store_products.entry_id", $entryData["entry_id"]);
        $query = ee()->db->get();
        if ($query->num_rows() > 0) {
            $row = $query->row_array();

            $_POST["store_product_field"] = array(
                "price" => "",
                "stock" => array(
                    array(
                        "sku" => "",
                        "min_order_qty" => ""
                    )
                ),
                "weight" => "",
                "length" => "",
                "width" => "",
                "height" => "",
                "handling" => "",
                "free_shipping" => ""
            );

            $_POST["store_product_field"] = array(
                "price" => $row["price"],
                "stock" => array(
                    array(
                        "sku" => $row["sku"],
                        "min_order_qty" => $row["min_order_qty"],
                        "track_stock" => $row["track_stock"],
                        "stock_level" => $row["stock_level"]
                    )
                ),
                "weight" => $row["weight"],
                "length" => $row["length"],
                "width" => $row["width"],
                "height" => $row["height"],
                "handling" => $row["handling"],
                "free_shipping" => $row["free_shipping"]
            );

        }
    }
}
