<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InvalidGitReferenceException extends Exception implements ExceptionInterface
{
    const MESSAGE = 'ERROR: The reference branch "%s" is not valid.';
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
        if($previous instanceof ProcessFailedException) {
            $command = explode(" ", $previous->getProcess()->getCommandLine());
            $ref = trim(basename(array_pop($command)), "'");
        }

        if (!$message) {
            $message = sprintf(self::MESSAGE, $ref ?? null);
        }

        $code = $code ?? self::CODE;
        parent::__construct($message, $code);
    }
}
