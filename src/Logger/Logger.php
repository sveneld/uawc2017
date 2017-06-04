<?php

namespace uawc;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Logger implements LoggerInterface
{
    use LoggerTrait;
    /**
     * @var string
     */
    private $logFile;

    /**
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = array())
    {
        file_put_contents(
            $this->logFile,
            (new \DateTimeImmutable())->format('Y-m-d H:i:s') .
            "\t" .
            $level .
            "\t" .
            $message .
            "\t" .
            json_encode($context) .
            "\t",
            FILE_APPEND
        );
    }

}