<?php

namespace BoldMinded\DataGrab\Queue\Jobs;

use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\ShouldBeUnique;
use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\ShouldQueue;
use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Queue\Job;

class DeleteItem extends AbstractJob implements ShouldQueue, ShouldBeUnique
{
    /**
     * @param Job $job
     * @param array $payload
     * @return bool
     */
    public function fire(Job $job, array $payload = []): bool
    {
        $this->job = $job;

        if (!$this->isValidImport($this->getImportId('delete'))) {
            return false;
        }

        if (!isset($payload['entryId']) || !$payload['entryId']) {
            return true;
        }

        ee()->datagrab->logger->log('DeleteItem PID: ' . getmypid());

        if (get_bool_from_string($this->settings['config']['soft_delete'] ?? 'no')) {
            $entry = ee('Model')
                ->get('ChannelEntry')
                ->filter('entry_id', $payload['entryId'])
                ->first();
            $entry->status = 'closed';
            $entry->save();

            ee()->datagrab->logger->log('Soft deleting entry #' . $payload['entryId']);
        } else {
            ee('Model')
                ->get('ChannelEntry')
                ->filter('entry_id', $payload['entryId'])
                ->delete();

            ee()->datagrab->logger->log('Deleting entry #' . $payload['entryId']);
        }

        ee()->datagrab->recordDeletedEntryIds();

        $this->job->delete();

        return true;
    }
}
