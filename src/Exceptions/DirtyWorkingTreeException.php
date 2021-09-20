<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Throwable;

class DirtyWorkingTreeException extends Exception implements ExceptionInterface
{
    const MESSAGE = 'ERROR: Resolve the uncommitted/untracked changes in your working branch.';
    const CODE = 0;

    /**
     * Construct the exception.
     *
     * @param string|null $message The Exception message to throw.
     * @param int|null $code The Exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     * @link https://php.net/manual/en/exception.construct.php
     */
    public function __construct(string $message = null, int $code = null, Throwable $previous = null) {
        parent::__construct($message ?? self::MESSAGE, $code ?? self::CODE, $previous);
    }

}
