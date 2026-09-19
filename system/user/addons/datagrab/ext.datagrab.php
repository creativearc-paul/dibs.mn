<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\License;
use BoldMinded\DataGrab\Dependency\Litzinger\Basee\Setting;

/**
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    DataGrab
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - BoldMinded, LLC
 * @link        https://boldminded.com/add-ons/datagrab
 * @license
 *
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

class Datagrab_ext
{
    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var integer
     */
    public $version = DATAGRAB_VERSION;

    /**
     * @var string
     */
    public $settings_exist = 'n';

    /**
     * @return string
     */
    public function cp_js_end(): string
    {
        $scripts = [];

        // If another extension shares the same hook
        if (ee()->extensions->last_call !== false) {
            $scripts[] = ee()->extensions->last_call;
        }

        /** @var Setting $setting */
        $setting = ee('datagrab:Setting');

        $license = new License('https://license.boldminded.com', 'datagrab', [
            'a'   => DATAGRAB_NAME,
            'api' => '1',
            'b'   => DATAGRAB_BUILD_VERSION,
            'd'   => ee()->config->item('base_url'),
            'e'   => APP_VER,
            'i'   => 2365,
            'l'   => $setting->get('license'),
            'p'   => phpversion(),
            's'   => ee()->config->item('site_id'),
            'v'   => DATAGRAB_VERSION,
        ]);

        $response = $license->validate();

        if ($response) {
            $scripts[] = $response;
        }

        return preg_replace("/\s+/", " ", implode('', $scripts));
    }
}
