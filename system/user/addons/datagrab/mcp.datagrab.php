<?php

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\Setting;
use BoldMinded\DataGrab\Dependency\Litzinger\Basee\Ping;
use BoldMinded\DataGrab\Service\DataGrabLoader;
use BoldMinded\DataGrab\Model\ImportStatus;

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
class Datagrab_mcp
{
    /**
     * @var DataGrabLoader
     */
    private $loader;

    private $settings;

    function __construct()
    {
        ee()->load->model('datagrab_model', 'datagrab');
        $this->loader = new DataGrabLoader;
    }

    /**
     * @param string $filename
     */
    private function loadJavaScript(string $filename = '')
    {
        $contents = file_get_contents(PATH_THIRD . sprintf('datagrab/scripts/%s.js', $filename));
        ee()->cp->add_to_foot(sprintf('<script type="text/javascript">%s</script>', $contents));
    }

    /**
     * @param string $filename
     */
    private function loadCss(string $filename = '')
    {
        $contents = file_get_contents(PATH_THIRD . sprintf('datagrab/styles/%s.css', $filename));
        ee()->cp->add_to_head(sprintf('<style type="text/css">%s</style>', $contents));
    }

    /**
     * @param string $actionName
     * @param int $importId
     * @param array $params
     * @return string
     */
    private function getActionUrl(string $actionName, int $importId = 0, array $params = []): string
    {
        $url = ee()->functions->fetch_site_index(0, 0) .
            QUERY_MARKER . 'ACT=' . ee()->cp->fetch_action_id('Datagrab', $actionName);

        if ($importId) {
            $url = $url . AMP . 'id=' . $importId;
        }

        if (!empty($params)) {
            $url = $url . AMP . http_build_query($params);
        }

        return $url;
    }

    public function index()
    {
        // Clear session data
        $this->getSession('settings');

        $this->loadJavaScript('datagrab');
        $this->loadCss('datagrab');

        ee()->view->cp_page_title = DATAGRAB_NAME;

        // Load helpers
        ee()->load->library('table');
        ee()->load->helper('form');
        ee()->load->library('relative_date');

        // Set data
        $data['title'] = 'DataGrab';
        $data['content'] = 'index';
        $data['types'] = ee()->datagrab->fetch_datatype_names();

        $query = ee()->db
            ->select('id, name, description, passkey, status, last_record, total_records, total_delete_records, last_delete_record, last_run, settings')
            ->where('site_id', ee()->config->item('site_id'))
            ->order_by('name ASC')
            ->get('exp_datagrab');

        ee()->javascript->set_global([
            'datagrab.fetch_queue_status' => $this->getActionUrl('fetch_queue_status'),
            'datagrab.purge_queue' => $this->getActionUrl('purge_queue'),
        ]);

        $data['saved_imports'] = [];

        /** @var \BoldMinded\DataGrab\Dependency\Illuminate\Queue\QueueManager $queue */
        $queueConnection = ee('datagrab:QueueManager')->connection('default');

        foreach ($query->result_array() as $row) {
            $id = $row['id'];
            $col = ['id' => $id];

            $importSettings = unserialize($row['settings']);
            $channel = $this->getChannel($importSettings['import']['channel'] ?? null);

            $importName = $row['name'];
            $importDescription = $row['description'];
            $importStatus = $row["status"];
            $settingsUrl = ee('CP/URL')->make('addons/settings/datagrab/save', ['id' => $row["id"]]);

            $importAlert = '';
            if (!$channel) {
                $importAlert = ' <i class="fas fa-circle-exclamation" title="The channel assigned to this import no longer exists."></i>';
            }

            $col['name'] = sprintf('<a href="%s" title="%s">%s</a>%s', $settingsUrl, $importDescription, $importName, $importAlert);

            unset($row['description']);

            $queryParams = [];

            if (isset($row['passkey']) && $row['passkey'] !== '') {
                $queryParams['passkey'] = $row['passkey'];
            }

            $importUrl = $this->getActionUrl('run_action', $id, array_merge($queryParams, ['iframe' => 'yes']));
            $directUrl = $this->getActionUrl('run_action', $id, $queryParams);

            if (ee()->datagrab->getDeleteQueueSize($id) > 0) {
                $importStatus = ImportStatus::WAITING;
            }

            $col['queue'] = '<div class="button-group button-group-xsmall">';
            $col['queue'] .= '<a class="button button--default fas fa-sync disabled" title="' . ($importStatus === ImportStatus::WAITING ? 'Continue' : 'Start') . ' import" data-status="' . $importStatus . '" data-action="dg-sync" data-id="' . $row['id'] . '" href="#"><span class="hidden">Sync</span></a>';
            $col['queue'] .= '<a class="button button--default fas fa-power-off disabled" title="Reset import" data-action="dg-reset" data-id="' . $row['id'] . '" href="' . ee('CP/URL')->make('addons/settings/datagrab/reset', ['id' => $row['id']]) . '"><span class="hidden">Reset Import</span></a>';
            $col['queue'] .= '<a class="button button--default fas fa-trash-can-xmark disabled hidden" title="Purge Queue" data-action="dg-purge" data-id="' . $row['id'] . '" href="#"><span class="hidden">Purge Queue</span></a>';
            $col['queue'] .= '</div>';

            $col['queue_size'] = '<div class="queue-size" data-id="' . $row['id'] . '">' . ee()->datagrab->getImportQueueSize($id) . '</div>';

            $col['status'] = ImportStatus::getDisplayStatus(
                $id,
                $row['status'],
                $row['last_record'] ?? 0,
                $row['total_records'] ?? 0,
                $row['error_records'] ?? 0,
                $queueConnection->size(ee()->datagrab->getImportQueueName($id)),
                $queueConnection->size(ee()->datagrab->getDeleteQueueName($id))
            );

            $col['status'] .= '<div class="import-progress"><div class="import-progress-bar" data-id="' . $id . '" data-src="' . $importUrl .'">
                <div class="progress-bar">
                    <div class="progress" style="width: '. ImportStatus::getPercentage($row['last_record'], $row['total_records']) .'%;"></div>
                </div>
            </div></div>';

            $col['last_run'] = ee()->localize->human_time($row["last_run"]);

            $col['toolbar'] = '<div class="button-group button-group-xsmall">';
            $col['toolbar'] .= '<a class="button button--default fas fa-edit" title="Edit saved import name/description" href="' . ee('CP/URL')->make('addons/settings/datagrab/save', ['id' => $row['id']]) . '"><span class="hidden">Edit</span></a>';
            $col['toolbar'] .= '<a class="button button--default fas fa-cog" title="Configure import" href="' . ee('CP/URL')->make('addons/settings/datagrab/load', ['id' => $row['id']]) . '"><span class="hidden">Configure</span></a>';
            $col['toolbar'] .= '<a class="button button--default fas fa-hashtag" title="Display URL to run import from outside Control Panel" onclick="alert(\'' . $directUrl . '\'); return false;" href="' . $directUrl . '"><span class="hidden">Import URL</span></a>';
            $col['toolbar'] .= '<a class="button button--default button--xsmall fas fa-copy" title="Clone import" href="' . ee('CP/URL')->make('addons/settings/datagrab/clone', ['id' => $row['id']]) . '"><span class="hidden">Clone Import</span></a>';
            $col['toolbar'] .= '<a class="button button--default button--xsmall fas fa-trash-alt" title="Delete saved import" href="' . ee('CP/URL')->make('addons/settings/datagrab/delete', ['id' => $id]) . '"><span class="hidden">Delete</span></a>';
            $col['toolbar'] .= '</div>';

            $data['saved_imports'][$id] = $col;
        }

        $data['form_action'] = ee('CP/URL', 'addons/settings/datagrab/settings');
        $data['license_url'] = ee('CP/URL', 'addons/settings/datagrab/license');

        $this->clearSession();

        return ee()->load->view('_wrapper', $data, true);
    }

    /**
     * @param int $channelId
     * @return null|Channel
     */
    private function getChannel(int $channelId = 0)
    {
        if ($channelId === 0) {
            $channelId = $this->settings['import']['channel'] ?? 0;
        }

        $channel = null;

        if ($channelId) {
            $channel = ee('Model')->get('Channel', (int) $channelId)->first();
        }

        return $channel;
    }

    public function settings()
    {
        $this->getInput();

        ee()->lang->loadfile('datagrab');

        // Fetch channel names
        $query = ee()->db
            ->select('channel_id, channel_title')
            ->where('site_id', ee()->config->item('site_id'))
            ->get('exp_channels');

        $channels = [];
        foreach ($query->result_array() as $row) {
            $channels[$row['channel_id']] = $row['channel_title'];
        }

        // Get settings form for type
        ee()->datagrab->initialise_types();
        /** @var AbstractDataType $currentType */
        $currentType = ee()->datagrab->datatypes[$this->settings['import']['type']] ?? null;

        if (!$currentType) {
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/datagrab')->compile());
        }

        $dataTypeSettings = $currentType->settings_form($this->settings) ?: [];

        $sections = [
            [
                'title' => $currentType->datatype_info['name'] . ' v' . $currentType->datatype_info['version'],
                'desc' => $currentType->datatype_info['description']
            ],
            [
                'title' => 'Channel',
                'desc' => 'Select the channel to import the data into',
                'fields' => [
                    'channel' => [
                        'required' => true,
                        'type' => 'select',
                        'choices' => $channels,
                        'value' => $this->settings['import']['channel'] ?? '',
                    ],
                ]
            ]
        ];

        // Append additional settings for the requested datatype
        foreach ($dataTypeSettings as $setting) {
            $sections[] = $setting;
        }

        $data['cp_page_title'] = 'Import Settings';
        $data['sections'] = [$sections];
        $data['base_url'] = ee('CP/URL')->make('addons/settings/datagrab/check_settings')->compile();
        $data['save_btn_text'] = 'save';
        $data['save_btn_text_working'] = 'btn_saving';
        $data['form_hidden'] = [
            'datagrab_step' => 'settings',
        ];

        return [
            'body' => ee('View')->make('ee:_shared/form')->render($data),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
                ee('CP/URL', 'addons/settings/datagrab/settings')->compile() => 'Import Settings',
            ],
        ];
    }

    public function check_settings()
    {
        $this->getInput();

        $fields = [];

        try {
            ee()->datagrab->initialise_types();
            /** @var AbstractDataType $currentType */
            $currentType = ee()->datagrab->datatypes[$this->settings['import']['type']];
            $currentType->isConfigMode = true;
            $currentType->initialise($this->settings);
            $ret = $currentType->fetch();
        } catch (Error $error) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('datagrab_configuration_error'))
                ->addToBody($error->getMessage())
                ->addToBody(lang('datagrab_troubleshooting'))
                ->now();
        }

        if (!empty($currentType->getErrors())) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('datagrab_configuration_error'))
                ->addToBody($currentType->getErrors())
                ->addToBody(lang('datagrab_troubleshooting'))
                ->now();
        }

        if ($ret != -1) {
            $titles = $currentType->fetch_columns();
            if ($titles != '') {
                foreach ($titles as $value) {
                    $fields[] = array($value);
                }
            }
        }

        $sections = [
            [
                [
                    'title' => 'Fields',
                    'desc' => 'The following fields were found in your import file:',
                    'fields' => [
                        'html' => [
                            'type' => 'html',
                            'content' => implode('<br />', array_column($fields, 0)),
                        ]
                    ]
                ]
            ],
        ];

        $data['cp_page_title'] = 'Check Settings';
        $data['sections'] = $sections;
        $data['base_url'] = ee('CP/URL')->make('addons/settings/datagrab/configure_import')->compile();
        $data['save_btn_text'] = 'Configure Import';
        $data['save_btn_text_working'] = $data['save_btn_text'];
        $data['form_hidden'] = [
            'datagrab_step' => 'check_settings',
        ];

        return [
            'body' => ee('View')->make('ee:_shared/form')->render($data),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
                ee('CP/URL', 'addons/settings/datagrab/check_settings')->compile() => 'Check Settings',
            ],
        ];
    }

    /**
     * @todo This whole method needs refactored to support the new shared/form view
     *
     * @return array
     */
    public function configure_import()
    {
        $this->getInput();
        $this->loadCss('datagrab');

        ee()->load->library('table');
        ee()->load->helper('form');

        $importName = $this->getSession('name', true);

        $data['title'] = $importName ? sprintf('Configure Import: %s', $importName) : 'Configure Import';
        $data['content'] = 'configure_import';

        $channel = $this->getChannel();

        if (!$channel) {
            ee('CP/Alert')->makeBanner()
                ->asIssue()
                ->withTitle('The requested channel does not exist.')
                ->addToBody(sprintf('The channel <i>%s</i> was configured with no longer exists. You will need to delete this import configuration and re-create it.', $importName))
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'addons/settings/datagrab')->compile());
        }

        $data['channel_title'] = $channel->channel_title;
        $data['custom_fields'] = [];
        $data['unique_fields'] = [];
        $data['field_settings'] = [];
        $data['field_required'] = [];
        $data['unique_fields'][''] = '';
        $data['unique_fields']['title'] = 'Title';
        $data['unique_fields']['url_title'] = 'URL Title';
        $data['field_types'] = [];

        foreach ($channel->getAllCustomFields() as $field) {
            $data['custom_fields'][$field->field_name] = $field->field_label;
            $data['unique_fields'][$field->field_name] = $field->field_label;
            $data['field_types'][$field->field_name] = $field->field_type;
            $data['field_settings'][$field->field_name] = $field->field_settings;
            $data['field_required'][$field->field_name] = $field->field_required;
        }

        $data['category_groups'] = [];
        foreach ($channel->getCategoryGroups() as $row) {
            $data['category_groups'][$row->group_id] = $row->group_name;
        }

        try {
            // Get list of fields from the datatype
            ee()->datagrab->initialise_types();
            /** @var AbstractDataType $currentType */
            $currentType = ee()->datagrab->datatypes[$this->settings['import']['type']];
            $currentType->initialise($this->settings);
            $currentType->fetch();
        } catch (Error $error) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('datagrab_configuration_error'))
                ->addToBody($error->getMessage())
                ->addToBody(lang('datagrab_troubleshooting'))
                ->now();
        }

        $data['data_fields'][''] = '';
        $currentType->isConfigMode = true;
        $fields = $currentType->fetch_columns();
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $data['data_fields'][$key] = $value;
            }
        }

        // Get list of authors
        // @todo: filter this list by member groups
        $data['authors'] = [];

        ee()->db->select('member_id, screen_name');
        $query = ee()->db->get('exp_members');
        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data['authors'][$row['member_id']] = $row['screen_name'];
            }
        }

        $data['author_fields'] = array(
            'member_id' => 'ID',
            'username' => 'Username',
            'screen_name' => 'Screen Name',
            'email' => 'Email address'
        );

        ee()->db->select('m_field_id, m_field_label');
        ee()->db->from('exp_member_fields');
        ee()->db->order_by('m_field_order ASC');
        $query = ee()->db->get();
        if ($query->num_rows() > 0) {
            $memberFields = [];
            foreach ($query->result_array() as $row) {
                $memberFields['m_field_id_' . $row['m_field_id']] = $row['m_field_label'];
            }
            $data['author_fields']['Custom Fields'] = $memberFields;
        }

        // Get statuses
        $data['status_fields'] = array(
            'default' => 'Channel default'
        );

        foreach ($channel->Statuses as $row) {
            $data['status_fields'][$row->status] = $row->status;
        }

        $data['status_fields'] = array_merge($data['status_fields'], $data['data_fields']);

        // Allow comments - check datatype ?
        $allowComments = $currentType->datatype_info['allow_comments'] ?? false;
        $data['allow_comments'] = (bool) $allowComments;

        // Allow multiple fields?
        $data['allow_multiple_fields'] = $currentType->datatype_info['allow_multiple_fields'] ?? false;

        // Load up any custom config tables for 3rd party add-ons
        $moduleHandlers = $this->loader->fetchModuleHandlers();
        foreach ($moduleHandlers as $handler) {
            $data['cm_config'][$handler->getDisplayName()] =
                $handler
                    ->setSettings($this->settings)
                    ->displayConfiguration(ee()->datagrab, $data);
        }

        $data['all_fields'] = [];
        $data['all_fields']['title'] = 'Title';
        $data['all_fields']['exp_channel_titles.entry_id'] = 'Entry ID';
        $data['all_fields']['exp_channel_titles.url_title'] = 'URL Title';

        $all_fields = ee('Model')
            ->get('ChannelField')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->all();

        foreach ($all_fields as $field) {
            $data['all_fields']['field_id_' . $field->field_id] = $field->field_label;
        }

        // Default settings
        if (isset ($currentType->config_defaults)) {
            foreach ($currentType->config_defaults as $field => $value) {
                if (!isset($this->settings[$field])) {
                    $this->settings['config'][$field] = $value;
                }
            }
        }
        $data['default_settings'] = $this->settings;
        $data['cf_config'] = [];

        // Build configuration table for custom fields
        foreach ($data['custom_fields'] as $field_name => $field_label) {
            $fieldType = $data['field_types'][$field_name];
            $fieldRequired = $data['field_required'][$field_name];

            /** @var AbstractDataType $handler */
            $handler = $this->loader->loadFieldTypeHandler($fieldType, true);

            if ($handler) {
                $data['cf_config'][] = $handler->display_configuration(
                    ee()->datagrab, $field_name, $field_label, $fieldType, $fieldRequired, $data
                );
            }
        }

        $data['datatype_info'] = $currentType->datatype_info;
        $data['datatype_settings'] = $currentType->settings;

        // Form action URLs
        $data['form_action'] = ee('CP/URL', 'addons/settings/datagrab/save');
        $data['back_link'] = ee('CP/URL')->make('addons/settings/datagrab/settings');
        $data['form_hidden'] = [
            'datagrab_step' => 'configure_import',
        ];

        if (ee()->input->get('id')) {
            $data['id'] = ee()->input->get('id');
        }

        if (!empty($currentType->getErrors())) {
            ee('CP/Alert')->makeInline('datagrab-form')
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('datagrab_configuration_error'))
                ->addToBody($currentType->getErrors())
                ->addToBody(lang('datagrab_troubleshooting'))
                ->now();
        }

        return [
            'body' => ee()->load->view('_wrapper', $data, true),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
                ee('CP/URL', 'addons/settings/datagrab/configure_import')->compile() => 'Configure Import',
            ],
        ];
    }

    public function save()
    {
        $this->getInput();

        $id = $this->settings['import']['id'] ?? ee()->input->get_post('id', 0);

        // Set data
        if ($id == 0) {
            $data['title'] = 'Save import';
            $name = '';
            $description = '';
            $passkey = '';
        } else {
            $data['title'] = 'Update import';

            ee()->db->where('id', $id);
            $query = ee()->db->get('exp_datagrab');
            $row = $query->row_array();

            $name = $row['name'] ?? '';
            $description = $row['description'] ?? '';
            $passkey = $row['passkey'] ?? '';
        }

        $passKeyField = form_input(
                [
                    'name' => 'passkey',
                    'id' => 'passkey',
                    'value' => $passkey,
                ]
            ) . '<br />' .
            form_button(
                [
                    'id' => 'generate',
                    'name' => 'generate',
                    'content' => 'Generate random key',
                    'class' => 'button button--secondary button--small'
                ]
            );

        $sections = [
            [
                [
                    'title' => 'Name',
                    'desc' => 'A title for the import',
                    'fields' => [
                        'name' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $name,
                        ]
                    ]
                ],
                [
                    'title' => 'Description',
                    'desc' => 'A description of the import',
                    'fields' => [
                        'description' => [
                            'type' => 'textarea',
                            'value' => $description,
                        ]
                    ]
                ],
                [
                    'title' => 'Passkey',
                    'desc' => 'Add an optional passkey to increase security against saved imports being run inadvertently',
                    'fields' => [
                        'passkey' => [
                            'type' => 'html',
                            'content' => $passKeyField,
                        ]
                    ]
                ],
            ],
        ];

        $data['cp_page_title'] = 'Save Import';
        $data['sections'] = $sections;
        $data['base_url'] = ee('CP/URL')->make('addons/settings/datagrab/do_save');
        $data['save_btn_text'] = 'save';
        $data['save_btn_text_working'] = 'btn_saving';
        $data['form_hidden'] = [
            'id' => $id,
        ];

        ee()->load->library('javascript');
        ee()->javascript->output('
            var chars = "0123456789ABCDEF";
            var string_length = 32;
        $("#generate").click( function() {
            var randomstring = "";
            for (var i=0; i<string_length; i++) {
                var rnum = Math.floor(Math.random() * chars.length);
                randomstring += chars.substring(rnum,rnum+1);
            }
            $("#passkey").val(randomstring);
        });
        ');
        ee()->javascript->compile();

        // Load view
        return [
            'body' => ee('View')->make('ee:_shared/form')->render($data),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
                ee('CP/URL', 'addons/settings/datagrab/import')->compile() => 'Save Import',
            ],
            'heading' => 'Save import'
        ];
    }

    public function do_save()
    {
        $this->getInput();

        ee()->load->helper('date');

        $id = ee()->input->post('id');

        $data = [
            'name' => ee()->input->post('name'),
            'description' => ee()->input->post('description'),
            'passkey' => ee()->input->post('passkey'),
            'last_run' => now()
        ];

        if (isset($this->settings['import']['type'])) {
            $data['settings'] = serialize($this->settings);
        } else {
            // Fetch settings from database
            ee()->db->select('settings');
            ee()->db->where('id', $id);
            $query = ee()->db->get('exp_datagrab');
            $row = $query->row_array();
            $data['settings'] = $row['settings'];
            $this->settings = unserialize($data['settings']);
        }

        // Get site_id from channel label
        ee()->db->select('site_id');
        if (is_numeric($this->settings['import']['channel'])) {
            ee()->db->where('channel_id', $this->settings['import']['channel']);
        } else {
            ee()->db->where('channel_name', $this->settings['import']['channel']);
            ee()->db->where('site_id', ee()->config->item('site_id'));
        }

        $query = ee()->db->get('exp_channels');
        $channelDefaults = $query->row_array();
        $data['site_id'] = $channelDefaults['site_id'];

        if (!$id) {
            ee()->db->insert('datagrab', array_merge($data, [
                'status' => ImportStatus::NEW,
            ]));

            $id = ee()->db->insert_id();

            $this->settings['import']['id'] = $id;
            $data['settings'] = serialize($this->settings);
        }

        ee()->db->where('id', $id);
        ee()->db->update('datagrab', $data);

        ee()->session->set_flashdata('message_success', 'Import saved.');

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab'));

    }

    public function load()
    {
        if (ee()->input->get('id')) {
            /** @var CI_DB_result $query */
            $query = ee('db')->where('id', ee()->input->get('id'))->get('datagrab');
            $row = $query->row_array();
            $this->settings = unserialize($row['settings']);
            $this->settings['import']['id'] = ee()->input->get('id');
            $this->setSession('settings', serialize($this->settings));
            $this->setSession('name', $row['name']);
            $this->setSession('id', ee()->input->get('id'));
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab/configure_import'));
    }

    public function reset()
    {
        if (ee()->input->get('id')) {
            ee()->datagrab->resetImport(ee()->input->get('id'), true);
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab'));
    }

    public function clone()
    {
        if (ee()->input->get('id')) {
            $db = ee('db');

            /** @var CI_DB_result $query */
            $query = $db->where('id', ee()->input->get('id'))->get('datagrab');
            $row = $query->row_array();

            $name = $row['name'] . ' [Clone]';

            $db->insert('datagrab', [
                'name' => $name,
                'description' => $row['description'],
                'settings' => $row['settings'],
                'passkey' => $row['pass_key'],
                'site_id' => $row['site_id'],
                'total_records' => 0,
                'last_record' => 0,
                'total_delete_records' => 0,
                'last_delete_record' => 0,
                'error_records' => 0,
                'status' => ImportStatus::NEW,
                'last_run' => null,
                'last_started' => null,
            ]);

            $newId = $db->insert_id();

            $this->settings = unserialize($row['settings']);
            $this->settings['import']['id'] = $newId;
            $this->setSession('settings', serialize($this->settings));
            $this->setSession('name', $name);
            $this->setSession('id', $newId);
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab/configure_import'));
    }

    /**
     * @deprecated In favor of executing the imports via the ACT url in an iframe on the add-on index page
     */
    public function run()
    {
        if (ee()->input->get('id') != 0) {
            ee()->db->where('id', ee()->input->get('id'));
            $query = ee()->db->get('exp_datagrab');
            $row = $query->row_array();
            $this->settings = unserialize($row['settings']);
            $this->settings['import']['id'] = ee()->input->get('id');
            $this->setSession('settings', serialize($this->settings));
            $this->setSession('name', $row['name']);
            $this->setSession('id', ee()->input->get('id'));
        }

        if (ee()->input->get('batch') == 'yes') {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab/import', array('batch' => 'yes')));
        } else {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab/import', array('id' => $row['id'])));
        }
    }

    function delete()
    {
        $id = ee()->input->get('id');

        $query = ee()->db
            ->select('name')
            ->where('id', $id)
            ->get('datagrab');

        $sections = [
            [
                [
                    'title' => 'Are you sure?',
                    'desc' => 'Really really sure?',
                    'fields' => [
                        'name' => [
                            'type' => 'html',
                            'content' => $query->row('name') ?? 'Rut roh raggy!',
                        ]
                    ]
                ],
            ],
        ];

        $data['cp_page_title'] = 'Delete Import';
        $data['sections'] = $sections;
        $data['base_url'] = ee('CP/URL')->make('addons/settings/datagrab/do_delete');
        $data['save_btn_text'] = 'delete';
        $data['save_btn_text_working'] = 'btn_working';
        $data['form_hidden'] = [
            'id' => $id,
        ];

        return [
            'body' => ee('View')->make('ee:_shared/form')->render($data),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
            ],
            'heading' => 'Delete Import'
        ];
    }

    function do_delete()
    {
        $id = ee()->input->post('id');

        if ($id != '' && $id != '0') {
            ee()->db->where('id', $id);
            ee()->db->delete('exp_datagrab');
        }

        ee()->session->set_flashdata('message_success', 'Deleted');

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/datagrab'));
    }

    /*

    HELPER FUNCTIONS

    */

    /**
     * Add $data to user session
     *
     * @param string $key
     * @param string $data
     * @return void
     */
    private function setSession($key, $data)/*: void */
    {
        @session_start();

        if (!isset($_SESSION[DATAGRAB_NAME])) {
            $_SESSION[DATAGRAB_NAME] = [];
        }

        $_SESSION[DATAGRAB_NAME][$key] = $data;
    }

    /**
     * Retrieve data from session. Data is removed from session unless $keep is
     * set to true
     *
     * @param string $key
     * @param string $keep
     * @return string $data
     */
    private function getSession($key, $keep = false): string
    {
        @session_start();

        if (isset($_SESSION[DATAGRAB_NAME]) && isset($_SESSION[DATAGRAB_NAME][$key])) {
            $data = $_SESSION[DATAGRAB_NAME][$key];

            if (!$keep) {
                unset($_SESSION[DATAGRAB_NAME][$key]);
            }

            return ($data);
        }

        return '';
    }

    private function clearSession()
    {
        $_SESSION[DATAGRAB_NAME] = [];
    }

    /**
     * Handle input from forms, sessions
     *
     * Collects data from forms, query strings and sessions. Only keeps relevant data
     * for the current import data type. Stores in session to allow back-and-forth
     * through 'wizard'
     *
     */
    private function getInput()
    {
        // Grab them before they're erased and SESSION is reset
        $importName = $this->getSession('name', true);
        $importId = $this->getSession('id', true);

        // Get current settings from session
        $this->settings = unserialize($this->getSession('settings')) ?: [];
        $datagrabStep = ee()->input->get_post('datagrab_step', 'default');

        switch ($datagrabStep) {
            // Step 1: choose import type
            case 'index':
            {
                $this->settings['import']['type'] = ee()->input->get_post('type');
                break;
            }
            // Step 2: set up datatype
            case 'settings':
            {
                $this->settings['import']['channel'] = ee()->input->get_post('channel');
                // Check datatype specific settings
                if (isset($this->settings['import']['type']) && $this->settings['import']['type'] != '') {
                    ee()->datagrab->initialise_types();
                    /** @var AbstractDataType $currentType */
                    $currentType = ee()->datagrab->datatypes[$this->settings['import']['type']];
                    $dataTypeSettings = $currentType->settings;
                    foreach ($dataTypeSettings as $option => $value) {
                        if (ee()->input->get_post($option) !== false) {
                            $this->settings['datatype'][$option] = ee()->input->get_post($option);
                        }
                    }
                }
                break;
            }
            case 'configure_import':
            {
                $allowedSettings = [
                    'type',
                    'channel',
                    'update',
                    'unique',
                    'author',
                    'author_field',
                    'author_check',
                    'offset',
                    'title',
                    'title_suffix',
                    'url_title',
                    'url_title_suffix',
                    'date',
                    'expiry_date',
                    'timestamp',
                    'delete_old',
                    'soft_delete',
                    'delete_by_timestamp',
                    'delete_by_timestamp_duration',
                    'cat_default',
                    'cat_field',
                    'cat_group',
                    'cat_delimiter',
                    'cat_sub_delimiter',
                    'cat_allow_numeric_names',
                    'id',
                    'status',
                    'update_status',
                    'import_comments',
                    'comment_author',
                    'comment_email',
                    'comment_date',
                    'comment_url',
                    'comment_body',
                    'ajw_entry_id',
                    'c_groups',
                    'update_edit_date',
                ];

                // Look through permitted settings, check whether a new POST var exists, and update
                foreach ($allowedSettings as $setting) {
                    if (ee()->input->post($setting) !== false) {
                        $this->settings['config'][$setting] = ee()->input->post($setting);
                    }
                }

                if (ee()->input->post('limit') !== false) {
                    $this->settings['import']['limit'] = ee()->input->post('limit');
                }

                // Don't allow value below 1. Recommended default is 50.
                if ($this->settings['import']['limit'] < 1) {
                    $this->settings['import']['limit'] = 1;
                }

                // Hack to handle checkboxes (whose post vars are not set if unchecked)
                // todo: improve this - use hidden field?
                if (ee()->input->get('method') == 'import') {
                    $checkboxes = ['update', 'delete_old', 'soft_delete', 'import_comments'];
                    foreach ($checkboxes as $check) {
                        if (!isset($this->settings['config'][$check])) {
                            $this->settings['config'][$check] = ee()->input->post($check);
                        }
                    }
                }

                // Get category group details
                $categorySettings = [
                    'cat_default',
                    'cat_field',
                    'cat_delimiter',
                    'cat_sub_delimiter',
                    'cat_allow_numeric_names',
                ];
                $categoryGroups = ee()->input->post('c_groups');
                foreach (explode('|', $categoryGroups) as $cat_group_id) {
                    foreach ($categorySettings as $cs) {
                        $setting = $cs . '_' . $cat_group_id;
                        if (ee()->input->post($setting) !== false) {
                            $this->settings['config'][$setting] = ee()->input->post($setting);
                        }
                    }
                }

                // Check for custom field settings
                if (isset($this->settings['import']['channel']) && $this->settings['import']['channel'] != '') {
                    $this->settings['cf'] = [];
                    $channel = ee('Model')->get('Channel', $this->settings['import']['channel'])->first();

                    // Look through field types and see if they need to register any extra variables
                    foreach ($channel->getAllCustomFields() as $row) {
                        if (ee()->input->post($row->field_name) !== false) {
                            $this->settings['cf'][$row->field_name] = ee()->input->post($row->field_name);
                        }

                        $handler = $this->loader->loadFieldTypeHandler($row->field_type);

                        if ($handler) {
                            $this->settings['cf'][$row->field_name] = $handler->save_configuration(
                                ee()->datagrab,
                                $row->field_name,
                                $this->settings['cf']
                            );

                            $typeSettings = $handler->register_setting($row->field_name);

                            foreach ($typeSettings as $fld) {
                                if (ee()->input->post($fld) !== false) {
                                    $this->settings['cf'][$fld] = ee()->input->post($fld);
                                }
                            }
                        }
                    }
                }

                // Load up any custom config tables for 3rd party add-ons
                $moduleHandlers = $this->loader->fetchModuleHandlers();
                foreach ($moduleHandlers as $handler) {
                    $this->settings['cm'][$handler->getName()] = $handler->saveConfiguration(ee()->datagrab);
                }

                break;
            }
        }

        // Get saved import id
        if (ee()->input->get('id')) {
            $this->settings['import']['id'] = ee()->input->get_post('id');
        }

        // Store settings in session
        $this->setSession('settings', serialize($this->settings));
        $this->setSession('name', $importName);
        $this->setSession('id', $importId);
    }

    /**
     * @param $method
     * @param array $params
     * @return null|string
     */
    private function getAction($method, $params = [])
    {
        $cacheKey = $method . md5(serialize($params));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $action = ee('Model')->get('ee:Action')
            ->filter('class', 'reel')
            ->filter('method', $method)
            ->first();

        if (!$action) {
            return null;
        }

        $actionId = (int) $action->action_id;
        $query = '';

        if (!empty($params)) {
            $query = '&'. http_build_query($params);
        }

        $actionUrl = $this->getSiteIndex() .'?ACT='. $actionId . $query;

        $this->cache[$cacheKey] = $actionUrl;

        return $actionUrl;
    }

    /**
     * @return array
     */
    public function license()
    {
        /** @var Setting $setting */
        $setting = ee('datagrab:Setting');

        if ($license = ee('Request')->post('license')) {
            $setting->save([
                'license' => $license,
            ]);

            (new Ping('datagrab_last_ping'))->clearPingStatus();

            ee('CP/Alert')
                ->makeInline('shared-form')
                ->asSuccess()
                ->withTitle('Success')
                ->addToBody('License updated!')
                ->now();
        }

        $sections = [
            [
                [
                    'title' => 'datagrab_license_name',
                    'desc' => lang('datagrab_license_desc'),
                    'fields' => [
                        'license' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $setting->get('license'),
                        ]
                    ]
                ],
            ],
        ];

        $vars['sections'] = $sections;
        $vars['base_url'] = ee('CP/URL')->make('addons/settings/datagrab/license')->compile();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = lang('btn_saving');
        $vars['cp_page_title'] = 'License';

        // Load view
        return [
            'body' => ee('View')->make('ee:_shared/form')->render($vars),
            'breadcrumb' => [
                ee('CP/URL', 'addons/settings/datagrab')->compile() => ee()->lang->line('datagrab_module_name'),
                ee('CP/URL', 'addons/settings/datagrab/license')->compile() => 'License',
            ],
        ];
    }
}
