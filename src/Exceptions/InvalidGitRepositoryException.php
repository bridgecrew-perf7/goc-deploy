<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InvalidGitRepositoryException extends Exception implements ExceptionInterface
{
    const MESSAGE = 'ERROR: The path "%s" is not the root of the git working repository.';
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

        $code = $code ?? self::CODE;
        parent::__construct($message, $code);
    }
}
