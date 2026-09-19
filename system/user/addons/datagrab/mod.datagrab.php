<?php

use BoldMinded\DataGrab\Service\QueueStatus;
use EllisLab\ExpressionEngine\Model\Addon\Action;

/**
 * @package     ExpressionEngine
 * @subpackage  Module
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
class Datagrab
{
    /**
     * @var string
     */
    public $return_data = '';

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var QueueStatus
     */
    private $queueStatus;

    function __construct()
    {
        ee()->load->model('datagrab_model', 'datagrab');

        $this->queueStatus = ee('datagrab:QueueStatus');
    }

    /**
     * Run an import via an action
     *
     * @param null $importId
     * @param string $passKey
     * @param string $fileName
     * @return void
     * @author BoldMinded, LLC
     */
    public function run_action($importId = null, string $passKey = '', string $fileName = '')
    {
        if (ee()->input->get('id') != '') {
            $importId = ee()->input->get('id');
        }
        if (ee()->input->get('passkey') != '') {
            $passKey = ee()->input->get('passkey');
        }
        if (!$importId) {
            ee()->datagrab->logger->log('Import aborted. No Import ID provided.');
            exit;
        }

        ee()->load->helper('url');
        ee()->load->library('javascript');
        ee()->load->model('template_model');

        // Fetch import settings
        $query = ee()->db
            ->where('id', $importId)
            ->get('datagrab');

        if ($query->num_rows() == 0) {
            ee()->datagrab->logger->log('Import aborted. Requested Import ID not found.');
            exit;
        }

        $row = $query->row_array();
        $this->settings = unserialize($row["settings"]);

        if ($row["passkey"] != '' && $row["passkey"] != $passKey) {
            ee()->datagrab->logger->log('Import aborted. Passkey required, but none provided.');
            exit;
        }

        ini_set('memory_limit', '1G');

        // Initialise
        ee()->datagrab->initialise_types();

        // Check for modifiers
        // If custom filename is passed in from the {exp:datagrab:run_saved_import id="X" filename="..."} tag
        // https://boldminded.com/support/ticket/2514
        if ($fileName) {
            $_GET['filename'] = $fileName;
            $this->settings["datatype"]["filename"] = $fileName;
        } elseif (ee()->input->get('filename') !== false) {
            if (
                ee()->input->get('filename') == 'POST' &&
                ee()->input->post('data') !== false
            ) {
                // write to cache file
                // set filename to cache file
                // clean up cache
                print tempnam("/tmp", '');
                exit;
            }

            $this->settings["datatype"]["filename"] = ee()->input->get('filename');
        }

        $this->settings['import']['id'] = $importId;
        $this->settings['import']['passkey'] = $passKey;
        $this->settings['import']['site_id'] = $row['site_id'];

        // Kick it over to the action url
        if (!ee()->input->get('ACT')) {
            /** @var Action $actionModel */
            $action = ee('Model')->get('Action')
                ->filter('class', 'Datagrab')
                ->filter('method', 'run_action')
                ->first();

            // Set these the first time, subsequent requests will already have these once we're on the ?ACT url
            $_GET['ACT'] = $action->action_id;
            $_GET['passkey'] = $passKey;
        }

        if (ee()->input->get('author_id') !== false) {
            $this->settings["config"]["author"] = ee()->input->get('author_id');
        }

        try {
            ee()->output->enable_profiler(false);

            $shouldProduce = true;
            $shouldCOnsume = true;

            if (ee()->input->get('consume') === 'yes' && !ee()->input->get('produce')) {
                $shouldProduce = false;
            }

            if (ee()->input->get('produce') === 'yes' && !ee()->input->get('consume')) {
                $shouldCOnsume = false;
            }

            // Produce and immediately consume, similar to how DG used to operate,
            // unless one or the other is explicitly defined.
            $dg = ee()->datagrab
                ->setup(
                    ee()->datagrab->datatypes[$this->settings['import']['type']],
                    $this->settings,
                    $shouldProduce
                );

            // If executing via the ACT URL outside the control panel keep refreshing
            // to keep consuming and avoid server timeouts.
            if (ee()->input->get('iframe') !== 'yes') {
                $workerOptions = ee('datagrab:QueueWorkerOptions');
                $url = $this->getRefreshUrl($importId, [
                    'passkey' => $passKey,
                    'filename' => $fileName,
                    'iframe' => 'no'
                ]);

                echo sprintf('<meta http-equiv="refresh" content="%d;url=%s">', $workerOptions->timeout + 3, $url);

                if (!AJAX_REQUEST) {
                    ee()->load->view('response-html', $this->queueStatus->fetch($importId)[$importId]);
                }
            }

            if ($shouldProduce) {
                if (ee()->input->get('restart') === 'yes') {
                    $dg->resetImport();
                }

                $dg->produceJobs();
            }

            if ($shouldCOnsume) {
                $dg->consumeJobs();
            }

            if (!$dg->isImportComplete() || !$dg->isDeleteComplete()) {
                $url = $this->getRefreshUrl($importId, [
                    'passkey' => $passKey,
                    'filename' => $fileName,
                ]);

                // Each worker has a max execution time set via it's WorkerOptions when that is reached
                // it will go into "WAITING" when it stops. So we'll immediately refresh this URL and
                // create a new worker which will pick up where the previous one left off. This is all
                // to avoid any PHP or server based timeouts.
                echo sprintf('<meta http-equiv="refresh" content="%d;url=%s">', 0, $url);
            }

        } catch (Error $error) { // Catch EE Core exceptions
            ee()->datagrab->logger->log($error->getMessage());
            ee()->datagrab->logger->log($error->getTraceAsString());
        } catch (Exception $exception) { // Catch general exceptions
            ee()->datagrab->logger->log($exception->getMessage());
            ee()->datagrab->logger->log($exception->getTraceAsString());
        }
    }

    /**
     * @param int $importId
     * @param string $passKey
     * @param string $fileName
     * @return string
     */
    private function getRefreshUrl(int $importId, array $params = [])
    {
        $queryParams = array_merge([
            'ACT' => ee()->input->get('ACT'),
            'id' => $importId,
            'consume' => 'yes',
        ], array_filter($params));

        return ee()->functions->fetch_site_index(0, 0) . '?' . http_build_query($queryParams);
    }

    /**
     * Run an import from a front end template
     *
     * @return void
     * @author BoldMinded, LLC
     */
    public function run_saved_import()
    {
        $this->run_action(
            ee()->TMPL->fetch_param('id'),
            ee()->TMPL->fetch_param('passkey'),
            ee()->TMPL->fetch_param('filename')
        );
    }

    /**
     * Public ACT request endpoint
     *
     * @return void
     */
    public function fetch_queue_status()
    {
        ee()->output->send_ajax_response([
            'response' => $this->queueStatus->fetch(ee()->input->get('id'))
        ]);

        exit;
    }

    /**
     * @param int $id
     * @return int
     */
    private function purgeQueue(int $id = 0): int
    {
        if (!$id) {
            $id = ee()->input->get('id');
        }

        $output = 0;

        if ($id) {
            ee()->datagrab->updateStatus('ABORTED', $id);

            $deleteQueue = $this->queueStatus->clear(ee()->datagrab->getDeleteQueueName($id));
            $importQueue = $this->queueStatus->clear(ee()->datagrab->getImportQueueName($id));

            return $deleteQueue + $importQueue;
        }

        return $output;
    }

    /**
     * Public ACT request endpoint
     *
     * @return void
     */
    public function purge_queue()
    {
        $id = ee()->input->get('id');

        ee()->output->send_ajax_response([
            'response' => $this->purgeQueue($id)
        ]);

        exit;
    }
}
