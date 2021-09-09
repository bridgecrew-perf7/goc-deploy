<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\ExceptionHandler;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

abstract class Repository
{

    /**
     * @param string $command
     * @param string|null $cwd
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    protected function execute(string $command, string $cwd = null): string
    {
        return trim($this->process($command, $cwd)->getOutput());
    }

    /**
     * Process is a thin wrapper around proc_* functions to easily start independent PHP processes.
     *
     * @param string $command The command to run and its arguments
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @return Process
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     * @see vendor/symfony/process/Process.php
     */
    protected function process(string $command, string $cwd): Process
    {
        $env = null;
        $input = null;
        $timeout = 60;

        $process = new Process(explode(' ', $command), $cwd, $env, $input, $timeout);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                ExceptionHandler::prepare(new ProcessFailedException($process));
            }


        } catch (RuntimeException $e) {
            ExceptionHandler::prepare($e);
        }

        return $process;
    }
}
