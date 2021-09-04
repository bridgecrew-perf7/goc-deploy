<?php

namespace Marcth\GocDeploy\Services;

use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ExceptionHandler;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

class ProcessService
{

    /**
     * The working directory or null to use the working dir of the current PHP process.
     *
     * @var string
     */
    protected $workingDirectory;

    /**
     * @param string|null $workingDirectory
     * @throws InvalidPathException
     */
    public function __construct(string $workingDirectory = null)
    {
        $this->workingDirectory = $this->fetchLocalGitRepositoryRootPath($workingDirectory ?? getcwd());
    }

    /**
     * @param string $workingTree
     * @return string
     * @throws InvalidPathException
     */
    public function fetchLocalGitRepositoryRootPath(string $workingTree): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree);
    }

    /**
     * @return string
     */
    public function fetchGitRepositoryUrl(): string
    {
        return $this->execute('git config --get remote.origin.url');
    }

    /**
     * @return $this
     */
    public function updateLocalGitMetadata(): self
    {
        $this->process('git fetch origin')->getOutput();

        return $this;
    }


    /**
     * @param string $command
     * @param string|null $cwd
     * @param array|null $env
     * @param null $input
     * @param float|int|null $timeout
     * @return String_
     */
    private function execute(string $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): string
    {
        return trim($this->process($command, $cwd, $env, $input, $timeout)->getOutput());
    }

    /**
     * Process is a thin wrapper around proc_* functions to easily start independent PHP processes.
     *
     * @param string $command The command to run and its arguments
     * @param string|null $cwd The working directory or null to use the working dir of the current PHP process
     * @param array|null $env The environment variables or null to use the same environment as the current PHP process
     * @param mixed $input The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     *
     * @throws LogicException When proc_open is not installed
     *
     * @see vendor/symfony/process/Process.php
     */
    private function process(string $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): Process
    {
        $cwd = $cwd ?? $this->workingDirectory;
        $timeout = 4;

        try {
            $process = new Process(explode(' ', $command), $cwd, $env, $input, $timeout);
            $process->run();
        } catch (RuntimeException $e) {
            ExceptionHandler::prepareException($e);
        }

        if (!$process->isSuccessful()) {
            ExceptionHandler::prepareException(new ProcessFailedException($process));
        }

        return $process;
    }

}
