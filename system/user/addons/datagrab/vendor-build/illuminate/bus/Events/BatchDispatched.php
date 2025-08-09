<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Bus\Events;

use BoldMinded\DataGrab\Dependency\Illuminate\Bus\Batch;
class BatchDispatched
{
    /**
     * The batch instance.
     *
     * @var \Illuminate\Bus\Batch
     */
    public $batch;
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Bus\Batch  $batch
     * @return void
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
