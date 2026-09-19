<?php

namespace BoldMinded\DataGrab\Commands;

use ExpressionEngine\Cli\Cli;
use Error;
use Exception;

class CommandImportRun extends Cli {

    /**
     * name of command
     * @var string
     */
    public $name = 'Run Import';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Runs a saved DataGrab import';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Runs a saved DataGrab import';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php system/ee/eecli.php import:run --id=123';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'import_id,id:' => 'Import ID',
        'pass_key,key:' => 'Import Pass Key',
        'limit,limit:' => 'Total number of entries to limit to each worker process',
        'producer' => 'Run producer only',
        'consumer' => 'Run consumer only',
    ];

    /**
     * @var array
     */
    private $settings = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $importId = $this->option('--import_id');
        $passKey = $this->option('--pass_key');
        $producer = $this->option('--producer');
        $consumer = $this->option('--consumer');
        $limit = $this->option('--limit');

        // Fetch import settings
        $query = ee()->db->where('id', $importId)->get('datagrab');

        if ($query->num_rows() == 0) {
            $this->output->outln('<<redbg>>Could not find the requested import ID.<<reset>>');
            exit;
        }

        $row = $query->row_array();
        $this->settings = unserialize($row["settings"]);
        $importName = $row['name'];

        if ($row['passkey'] != '' && $row['passkey'] != $passKey) {
            $this->output->outln('<<redbg>>Import aborted. Passkey required, but none provided.<<reset>>');
            exit;
        }

        set_time_limit(0);
        ini_set('memory_limit', '1G');

        // Initialise
        ee()->load->library('session');
        ee()->load->add_package_path(PATH_THIRD . 'datagrab');
        ee()->load->model('datagrab_model', 'datagrab');
        ee()->lang->loadfile('datagrab');
        ee()->datagrab->initialise_types();

        $this->settings['import']['id'] = $importId;
        $this->settings['import']['passkey'] = $passKey;
        $this->settings['import']['site_id'] = $row['site_id'];

        if ($limit !== null) {
            $this->settings['import']['limit'] = (int) $limit;
        }

        try {
            $this->output->outln('<<dim>>Starting: ' . $importName .'... <<reset>>');

            $shouldProduce = true;
            $shouldConsume = true;

            if ($consumer && !$producer) {
                $shouldProduce = false;
            }

            if ($producer && !$consumer) {
                $shouldConsume = false;
            }

            $dg = ee()->datagrab
                ->setup(
                    ee()->datagrab->datatypes[$this->settings['import']['type']],
                    $this->settings,
                    $shouldProduce
                );

            if ($shouldProduce) {
                $this->output->outln('<<dim>>Queueing...<<reset>>');
                $dg->resetImport()->produceJobs();
            }

            if ($shouldConsume) {
                $this->output->outln('<<dim>>Consuming...<<reset>>');
                $dg->consumeJobs();
            }

        } catch (Error $error) { // Catch EE Core exceptions
            $this->output->outln('<<red>>' . $error->getMessage() . '<<reset>>');
        } catch (Exception $exception) { // Catch general exceptions
            $this->output->outln('<<red>>' . $exception->getMessage() . '<<reset>>');
        }

        return '';
    }
}
