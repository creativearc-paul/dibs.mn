<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Console\Events;

use BoldMinded\DataGrab\Dependency\Illuminate\Console\Scheduling\Event;
class ScheduledBackgroundTaskFinished
{
    /**
     * The scheduled event that ran.
     *
     * @var \Illuminate\Console\Scheduling\Event
     */
    public $task;
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $task
     * @return void
     */
    public function __construct(Event $task)
    {
        $this->task = $task;
    }
}
