<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\ProcessException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class BaseRepository
{
    /**
     * @param string $command
     * @param string $cwd
     * @return string
     * @throws ProcessException
     */
    protected function execute(string $command, string $cwd): string
    {
        return trim($this->process($command, $cwd)->getOutput());
    }

    /**
     * Process is a thin wrapper around proc_* functions to easily start independent PHP processes.
     *
     * @param string|array $command The command to run and its arguments
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @param $command
     * @param string $cwd
     * @return Process
     *
     * @return Process
     * @see vendor/symfony/process/Process.php
     */
    protected function process($command, string $cwd): Process
    {
        $env = null;
        $input = null;
        $timeout = 120;

        if (is_string($command)) {
            $command = explode(' ', $command);
        }

        $process = new Process($command, $cwd, $env, $input, $timeout);
        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (\Exception $e) {
            throw new ProcessException($e);
        }


        return $process;
    }
}
