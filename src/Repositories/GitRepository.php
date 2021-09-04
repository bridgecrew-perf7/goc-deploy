<?php

namespace Marcth\GocDeploy\Repositories;

use Marcth\GocDeploy\Exceptions\InvalidGitRepositoryException;
use Marcth\GocDeploy\Exceptions\ExceptionHandler;
use Marcth\GocDeploy\Exceptions\InvalidPathException;
use Marcth\GocDeploy\Exceptions\ProcessException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

class GitRepository
{
    /**
     * The working directory or null to use the working dir of the current PHP process.
     *
     * @var string
     */
    protected $workingTree;

    /**
     * @param string|null $workingTree
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     */
    public function __construct(string $workingTree = null)
    {
        $this->workingTree = $this->getLocalRootPath($workingTree ?? getcwd());
    }

    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws ProcessException
     * @throws InvalidPathException
     */
    public function getLocalRootPath(string $workingTree = null): string
    {
        return $this->execute('git rev-parse --show-toplevel', $workingTree ?? $this->workingTree);
    }

    /**
     * @param string|null $workingTree
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function getRemoteUrl(string $workingTree = null): string
    {
        return $this->execute('git config --get remote.origin.url', $workingTree ?? $this->workingTree);
    }

    /**
     * @param string|null $workingTree
     * @throws InvalidGitRepositoryException
     * @throws InvalidPathException
     * @throws ProcessException
     */
    public function refreshOriginMetadata(string $workingTree = null)
    {
        $this->execute('git fetch origin', $workingTree ?? $this->workingTree);
    }


    /**
     * @param string $command
     * @param string|null $cwd
     * @return string
     * @throws InvalidGitRepositoryException
     * @throws ProcessException|InvalidPathException
     */
    private function execute(string $command, string $cwd = null): string
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
     * @throws ProcessException
     * @throws InvalidPathException
     * @see vendor/symfony/process/Process.php
     */
    private function process(string $command, string $cwd = null): Process
    {
        $cwd = $cwd ?? $this->workingTree;
        $env = null;
        $input = null;
        $timeout = 4;

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
