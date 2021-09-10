<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConnectionRefusedException extends Exception implements ExceptionInterface
{
    const MESSAGE = 'ERROR: Unable to access the remote repository (Connection refused).';
    const CODE = 128;

    /**
     * Construct the exception.
     *
     * @param string|null $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param ProcessFailedException|null $previous The previous throwable used for the exception chaining.
     * @link https://php.net/manual/en/exception.construct.php
     */
    public function __construct(string $message = null, $code = null, ProcessFailedException $previous = null) {
        if (!$message) {
            $message = sprintf(self::MESSAGE, ($previous instanceof ProcessFailedException) ?
                ($previous->getProcess()->getWorkingDirectory() ?? null) : 'null');
        }

        $message = $message ?? self::MESSAGE;
        $code = $code ?? self::CODE;

        parent::__construct($message, $code);
    }
}
