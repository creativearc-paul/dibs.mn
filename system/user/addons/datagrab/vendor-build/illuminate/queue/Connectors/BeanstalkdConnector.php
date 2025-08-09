<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Queue\Connectors;

use BoldMinded\DataGrab\Dependency\Illuminate\Queue\BeanstalkdQueue;
use BoldMinded\DataGrab\Dependency\Pheanstalk\Connection;
use BoldMinded\DataGrab\Dependency\Pheanstalk\Pheanstalk;
class BeanstalkdConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new BeanstalkdQueue($this->pheanstalk($config), $config['queue'], $config['retry_after'] ?? Pheanstalk::DEFAULT_TTR, $config['block_for'] ?? 0, $config['after_commit'] ?? null);
    }
    /**
     * Create a Pheanstalk instance.
     *
     * @param  array  $config
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        return Pheanstalk::create($config['host'], $config['port'] ?? Pheanstalk::DEFAULT_PORT, $config['timeout'] ?? Connection::DEFAULT_CONNECT_TIMEOUT);
    }
}
