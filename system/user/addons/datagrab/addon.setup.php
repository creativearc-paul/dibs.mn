<?php
// Build: {LOGIT_BUILD_VERSION}
require_once PATH_THIRD . 'datagrab/vendor-build/autoload.php';

use BoldMinded\DataGrab\Dependency\Illuminate\Events\Dispatcher;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\Worker;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\WorkerOptions;
use BoldMinded\DataGrab\Dependency\Illuminate\Database\Capsule\Manager as DatabaseCapsuleManager;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\QueueManager;
use BoldMinded\DataGrab\Dependency\Litzinger\Basee\Setting;
use BoldMinded\DataGrab\Queue\Drivers\DatabaseDriver;
use BoldMinded\DataGrab\Queue\Drivers\RedisDriver;
use BoldMinded\DataGrab\Queue\Drivers\SQSDriver;
use BoldMinded\DataGrab\Queue\Exceptions\QueueException;
use BoldMinded\DataGrab\Queue\Subscribers\QueueSubscriber;
use BoldMinded\DataGrab\Service\QueueStatus;

if (!defined('DATAGRAB_NAME')) {
    define('DATAGRAB_NAME', 'DataGrab');
    define('DATAGRAB_CLASS_NAME', 'datagrab');
    define('DATAGRAB_VERSION', '5.0.5');
    define('DATAGRAB_BUILD_VERSION', 'e9ea5e57');

    if (defined('BASE')) {
        define('DATAGRAB_URL', BASE . AMP . '?/cp/addons/settings/datagrab');
        define('DATAGRAB_PATH', '?/cp/addons/settings/datagrab');
    }
}

return [
    'author' => 'BoldMinded, LLC',
    'author_url' => 'http://boldminded.com/add-ons/datagrab',
    'name' => DATAGRAB_NAME,
    'description' => 'Easily import data into ExpressionEngine channel entries',
    'version' => DATAGRAB_VERSION,
    'namespace' => 'BoldMinded\DataGrab',
    'settings_exist' => true,

    'requires' => [
        'php'   => '7.4',
        'ee'    => '6.4'
    ],

    'services.singletons' => [
        'Setting' => function () {
            return new Setting('datagrab_settings');
        },
        'DatabaseConfig' => function () {
            return  [
                'driver' => 'mysql',
                'host' => ee('db')->hostname,
                'database' => ee('db')->database,
                'username' => ee('db')->username,
                'password' => ee('db')->password,
                'charset' => ee('db')->char_set,
                'collation' => ee('db')->dbcollat,
                'prefix' => ee('db')->dbprefix . 'datagrab_',
            ];
        },
        'DatabaseManager' => function ($provider) {
            $databaseManager = new DatabaseCapsuleManager;
            $databaseManager->addConnection($provider->make('DatabaseConfig'));

            return $databaseManager;
        },
        'QueueManager' => function ($provider) {
            $config = ee()->config->item('datagrab') ?: [];

            // @todo this is incomplete and unsupported
             if (isset($config['driver']) && $config['driver'] === 'sqs') {
                 return (new SQSDriver($provider, $config['sqs_config'] ?? []))->getQueueManager();
             }

            if (isset($config['driver']) && $config['driver'] === 'redis') {
                return (new RedisDriver($provider, ['default' => $config['redis_config'] ?? []]))->getQueueManager();
            }

            return (new DatabaseDriver($provider))->getQueueManager();
        },
        'QueueWorker' => function ($provider) {
            /** @var QueueManager $queueManager */
            $queueManager = $provider->make('QueueManager');

            $dispatcher = new Dispatcher($queueManager->getContainer());
            $dispatcher->subscribe(new QueueSubscriber);

            return new Worker(
                $queueManager,
                $dispatcher,
                new QueueException,
                function () {},
                function () {}
            );
        },
        'QueueWorkerOptions' => function () {
            // Determine how long a worker can run without timing out based on the PHP settings.
            $maxExecutionTime = (ini_get('max_execution_time') ?? 130) - 10;

            // Set minimum timeout, especially if PHP's CLI config does not have max_execution_time set.
            if ($maxExecutionTime < 30) {
                $maxExecutionTime = 30;
            }

            return new WorkerOptions(
                'default', // name
                0, // backoff
                1024, // memory
                $maxExecutionTime, // timeout
                3, // sleep
                3, // max tries
                \false, // force
                \true, // stop when empty
                0, // max jobs or limit
                $maxExecutionTime, // max time
                0 // rest
            );
        },
        'QueueStatus' => function () {
            return new QueueStatus();
        }
    ],

    'commands' => [
        'import:run' => BoldMinded\DataGrab\Commands\CommandImportRun::class,
        'import:reset' => BoldMinded\DataGrab\Commands\CommandImportReset::class,
    ]
];
