<?php

namespace BoldMinded\DataGrab\Queue\Jobs;

use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\ShouldBeUnique;
use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\ShouldQueue;
use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\Job;

class ImportItem extends AbstractJob implements ShouldQueue, ShouldBeUnique
{
    /**
     * @param Job $job
     * @param array $payload
     * @return bool
     */
    public function fire(Job $job, array $payload = []): bool
    {
        $this->job = $job;

        if (!$this->isValidImport($this->getImportId())) {
            return false;
        }

        $result = ee()->datagrab->setup(
            ee()->datagrab->datatypes[$this->settings['import']['type']],
            $this->settings
        )->importItem($payload);

        if ($result === true) {
            $this->job->delete();
        }

        ee()->datagrab->logger->log('ImportItem PID: ' . getmypid());

        $this->handleOutput((int)$this->settings['import']['id']);

        return $result;
    }
}
