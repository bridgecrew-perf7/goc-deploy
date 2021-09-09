<?php

namespace Marcth\GocDeploy\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Throwable;

class ExceptionHandler extends Exception implements ExceptionInterface
{

    /**
     * Builds an InvalidPathException using predefined defaults.
     * @param ProcessFailedException $e
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public static function prepare(Throwable $e)
    {

        if ($e instanceof ProcessFailedException) {
            self::prepareProcessFailedException($e);
        }

        if ($e instanceof RuntimeException) { // ProcessTimedOutException
            self::prepareRuntimeException($e);
        }
    }

    /**
     * @param ProcessFailedException $e
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     */
    protected static function prepareProcessFailedException(ProcessFailedException $e)
    {
        // error: pathspec 'develops' did not match any file(s) known to git
        if ($e->getProcess()->getExitCode() == 1 &&
            str_contains($e->getProcess()->getErrorOutput(), "pathspec")) {
            throw new InvalidGitBranchException(null, null, $e);
        }

        // fatal: 'refs/heads/developg' - not a valid ref
        if ($e->getProcess()->getExitCode() == 128 &&
            str_contains($e->getProcess()->getErrorOutput(), 'not a valid ref')) {
            throw new InvalidGitReferenceException(null, null, $e);
        }

        // Fatal: not a git repository (or any of the parent directories): .git
        if ($e->getProcess()->getExitCode() == 128 &&
            str_contains($e->getProcess()->getErrorOutput(), "Fatal: not a git repository")) {
            throw new InvalidGitRepositoryException(null, null, $e);
        }

        throw new ProcessException($e->getProcess()->getErrorOutput(), $e->getProcess()->getExitCode(), $e);
    }

    /**
     * @param RuntimeException $e
     * @throws InvalidPathException
     * @throws ProcessException
     */
    protected static function prepareRuntimeException(RuntimeException $e)
    {

        if ($e instanceof ProcessTimedOutException) {
            throw new ProcessException($e->getMessage(), $e->getCode());
        }

        // The provided cwd "" does not exist.
        if (str_contains($e->getMessage(), 'The provided cwd ')) {
            throw new InvalidPathException(
                str_replace('cwd', 'working directory', $e->getMessage()),
                $e->getCode());
        }
    }
}
