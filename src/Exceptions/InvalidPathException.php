<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Throwable;

class InvalidPathException extends Exception implements ExceptionInterface
{
    const MESSAGE = 'The directory does not exists.';
    const CODE = 0;

    /**
     * Construct the exception.
     *
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct($message = null, $code = null, Throwable $previous = null) {
        parent::__construct(
            $message ?? self::MESSAGE,
            $code ?? self::CODE,
            $previous
        );
    }
}
