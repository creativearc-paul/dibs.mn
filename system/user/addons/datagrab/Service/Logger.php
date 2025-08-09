<?php

namespace BoldMinded\DataGrab\Service;

use EE_Logger;

class Logger
{
    private $logger;
    private $logType;
    private $logFile;

    /**
     * @param EE_Logger $logger
     * @param string    $logType
     * @param string    $logFile
     */
    public function __construct(EE_Logger $logger, string $logType, string $logFile)
    {
        $this->logger = $logger;
        $this->logType = $logType;
        $this->logFile = $logFile;
    }

    /**
     * @param string $message
     * @param bool   $update
     * @return void
     */
    public function log(string $message = '', bool $update = true): void
    {
        if (!$message || $this->logType === 'off') {
            return;
        }

        switch ($this->logType) {
            case 'developer':
                $this->logger->developer('DataGrab: ' . $message, $update);
                break;
            case 'php':
                if ($this->logFile) {
                    $oldLogFile = ini_get('error_log');
                    @ini_set('error_log', $this->logFile);
                    error_log('DataGrab: ' . $message);
                    @ini_set('error_log', $oldLogFile);
                } else {
                    error_log('DataGrab: ' . $message);
                }
                break;
            default:
                $this->writeToFile($message);
        }
    }

    /**
     * @param string $message
     * @return void
     */
    private function writeToFile(string $message = ''): void
    {
        $time = date('H:i:s m/d/Y', time());
        $stream = fopen($this->logFile, 'a+');
        fwrite($stream, $time . ' ' . print_r($message, true) ."\n");
        fclose($stream);
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        switch ($this->logType) {
            case 'developer':
                // Developer log already updates duplicate messages with a new timestamp, effectively resetting it.
                break;
            case 'php':
                break;
            default:
                @unlink($this->logFile);
        }
    }
}
