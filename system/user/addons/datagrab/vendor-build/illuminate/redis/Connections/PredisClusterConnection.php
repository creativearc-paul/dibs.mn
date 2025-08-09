<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Redis\Connections;

use BoldMinded\DataGrab\Dependency\Predis\Command\ServerFlushDatabase;
class PredisClusterConnection extends PredisConnection
{
    /**
     * Flush the selected Redis database on all cluster nodes.
     *
     * @return void
     */
    public function flushdb()
    {
        $this->client->executeCommandOnNodes(tap(new ServerFlushDatabase())->setArguments(\func_get_args()));
    }
}
